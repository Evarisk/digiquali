<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/survey/survey_card.php
 * \ingroup digiquali
 * \brief   Page to create/edit/view survey
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

// Load Saturne libraries
dol_include_once('/saturne/class/saturnesignature.class.php');

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/survey.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/questiongroup.class.php';
require_once __DIR__ . '/../../class/answer.class.php';
require_once __DIR__ . '/../../class/digiqualidocuments/surveydocument.class.php';
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../../lib/digiquali_survey.lib.php';
require_once __DIR__ . '/../../lib/digiquali_answer.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'surveycard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize objects
// Technical objets
$object        = new Survey($db);
$objectLine    = new SurveyLine($db);
$document      = new SurveyDocument($db);
$signatory     = new SaturneSignature($db, 'digiquali');
$sheet         = new Sheet($db);
$question      = new Question($db);
$answer        = new Answer($db);
$questionGroup = new QuestionGroup($db);
$extrafields   = new ExtraFields($db);
$category      = new Categorie($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(['surveycard', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$searchAll = GETPOST('search_all', 'alpha');
$search    = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha')) {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
}

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

$objectsMetadata = saturne_get_objects_metadata();

$upload_dir = $conf->digiquali->multidir_output[$object->entity ?? 1];

// Security check - Protection if external user
$permissionToRead   = $user->rights->digiquali->survey->read;
$permissiontoadd    = $user->rights->digiquali->survey->write;
$permissiontodelete = $user->rights->digiquali->survey->delete || ($permissiontoadd && isset($object->status) && $object->status == Survey::STATUS_DRAFT);
saturne_check_access($permissionToRead);

// A collect session closes by itself once its planned closing date is past (see lib/digiquali_survey.lib.php)
if ($object->id > 0 && digiquali_survey_session_close_if_due($object)) {
    setEventMessages($langs->trans('SurveySessionClosedAuto', dol_print_date($object->date_end, 'dayhour', 'tzuserrel')), []);
}

// A collect session is the template of its responses: nobody answers it, nor validates, signs or reopens it as a survey
$isSession = $object->id > 0 && digiquali_survey_is_session($object);

/*
 * Actions
 */

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('/digiquali/view/survey/survey_list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/digiquali/view/survey/survey_card.php', 1) . '?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
            }
        }
    }

    // Action clone object
    if ($action == 'confirm_clone' && $confirm == 'yes') {
        $options['answers']    = GETPOST('clone_answers');
        $options['attendants'] = GETPOST('clone_attendants');
        $options['photos']     = GETPOST('clone_photos');
        if ($object->id > 0) {
            $result = $object->createFromClone($user, $object->id, $options);
            if ($result > 0) {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $result);
                exit();
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
                $action = '';
            }
        }
    }

    if ($action == 'add' && !$cancel) {
        $linkedObjectSelected = 0;
        foreach ($objectsMetadata as $objectType => $objectMetadata) {
            if (!empty(GETPOST($objectMetadata['post_name'])) && GETPOST($objectMetadata['post_name']) > 0) {
                $linkedObjectSelected++;
            }
        }

        if (GETPOST('fk_sheet') > 0) {
            if ($linkedObjectSelected == 0) {
                setEventMessages($langs->trans('NeedObjectToLink'), [], 'errors');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create&fk_sheet=' . GETPOST('fk_sheet'));
                exit;
            }
        } else {
            setEventMessages($langs->trans('NeedFkSheet'), [], 'errors');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?action=create');
            exit;
        }
    }

    // The answer of a collect session closed (or due to close) is no longer sent, as its answers are no longer saved
    if ($action == 'confirm_validate' && $confirm == 'yes' && $object->id > 0 && digiquali_survey_response_session_closed($object)) {
        setEventMessages($langs->trans('SurveySessionClosedNotSaved'), [], 'errors');
        $action = '';
    }

    // A session is closed by its own button, the validation of a survey would ask for the answers it never gets
    if (in_array($action, ['confirm_validate', 'confirm_setdraft'], true) && $isSession) {
        setEventMessages($langs->trans('SurveySessionTemplateNoWorkflow'), [], 'errors');
        $action = '';
    }

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    require_once DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

    // Actions set_thirdparty, set_project
    require_once dol_buildpath('/saturne/core/tpl/actions/banner_actions.tpl.php');

    // A collect session : one link gives each respondent a survey of his own (see lib/digiquali_survey.lib.php)
    if ($action == 'open_session' && $permissiontoadd && $object->id > 0) {
        $result = digiquali_survey_open_session($object);
        if ($result > 0) {
            setEventMessages($langs->trans('SurveySessionOpened'), []);
        } elseif ($result == -2) {
            setEventMessages($langs->trans('SurveySessionOpenHasAnswers'), [], 'errors');
        } else {
            setEventMessages($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))), [], 'errors');
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        exit;
    }

    // Closing a session validates it : the mandatory questions are those of the responses, not of the session
    if ($action == 'close_session' && $permissiontoadd && $object->id > 0 && digiquali_survey_is_session($object)) {
        // validate() gives the session its definitive reference, a closed session kept its (PROV) one
        if ($object->status == Survey::STATUS_DRAFT && $object->validate($user) > 0) {
            setEventMessages($langs->trans('SurveySessionClosed'), []);
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        exit;
    }

    if ($action == 'reopen_session' && $permissiontoadd && $object->id > 0 && digiquali_survey_is_session($object)) {
        if ($object->status == Survey::STATUS_VALIDATED && $object->setStatusCommon($user, Survey::STATUS_DRAFT, 0, 'SURVEY_UNVALIDATE') > 0) {
            // A planned closing date already past would close it again at once: it is dropped
            if (!empty($object->date_end) && $object->date_end <= dol_now()) {
                $db->query('UPDATE ' . $db->prefix() . $object->table_element . ' SET date_end = NULL WHERE rowid = ' . ((int) $object->id));
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        exit;
    }

    // Planned closing date and mode of an open session
    if ($action == 'set_session_options' && $permissiontoadd && $object->id > 0 && digiquali_survey_is_session($object) && $object->status == Survey::STATUS_DRAFT) {
        $dateEnd = dol_mktime(GETPOSTINT('date_endhour'), GETPOSTINT('date_endmin'), 0, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'), 'tzuserrel');
        if (!empty($dateEnd) && $dateEnd <= dol_now()) {
            setEventMessages($langs->trans('SurveySessionDateEndInPast'), [], 'errors');
        } else {
            $object->date_end = !empty($dateEnd) ? $dateEnd : null;
            // The mode is what the welcome page promised the respondents : it no longer changes once one has started,
            // nor once there are invitees, whose personal links are nominative
            if (empty(digiquali_survey_session_get_responses($object)) && empty(digiquali_survey_session_get_invitees($object))) {
                $object->nominative = GETPOSTINT('nominative') > 0 ? 1 : 0;
            }
            if ($object->update($user) > 0) {
                setEventMessages($langs->trans('RecordSaved'), []);
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        exit;
    }

    // Invitees of a nominative session: each one gets a personal link by e-mail, and those who have not answered can be
    // reminded. Only while the collect is open: a closed session neither invites nor reminds
    if (in_array($action, ['add_invitees', 'remove_invitee', 'confirm_send_invitations', 'confirm_remind_invitees'], true) && $permissiontoadd && $object->id > 0 && digiquali_survey_is_session($object)) {
        if ($object->status != Survey::STATUS_DRAFT || empty($object->nominative)) {
            setEventMessages($langs->trans($object->status != Survey::STATUS_DRAFT ? 'SurveySessionClosedNoInvitation' : 'SurveySessionInviteesNominativeOnly'), [], 'errors');
        } elseif ($action == 'add_invitees') {
            // One invitee typed in the fields, and as many as pasted lines "Name ; e-mail"
            $lines = GETPOST('invitee_lines', 'alphawithlgt');
            if (GETPOST('invitee_email', 'alphanohtml') !== '' || GETPOST('invitee_name', 'alphanohtml') !== '') {
                $lines = trim(GETPOST('invitee_name', 'alphanohtml') . ' ; ' . GETPOST('invitee_email', 'alphanohtml'), ' ;') . "\n" . $lines;
            }
            $parsed = digiquali_survey_session_parse_invitees($lines);
            $added  = !empty($parsed['people']) ? digiquali_survey_session_add_invitees($object, $parsed['people']) : ['added' => 0, 'duplicates' => [], 'overflow' => 0];
            if ($added === false) {
                setEventMessages($langs->trans('ErrorFailedToSaveFile'), [], 'errors');
            } else {
                if ($added['added'] > 0) {
                    setEventMessages($langs->trans('SurveySessionInviteesAdded', $added['added']), []);
                }
                $warnings = [];
                foreach ($parsed['errors'] as $badLine) {
                    $warnings[] = $langs->trans('SurveySessionInviteeBadLine', dol_escape_htmltag($badLine));
                }
                foreach ($added['duplicates'] as $duplicate) {
                    $warnings[] = $langs->trans('SurveySessionInviteeDuplicate', dol_escape_htmltag($duplicate));
                }
                if ($added['overflow'] > 0) {
                    $warnings[] = $langs->trans('SurveySessionInviteesTooMany', digiquali_survey_session_invitees_max(), $added['overflow']);
                }
                if (!empty($warnings)) {
                    setEventMessages('', $warnings, 'warnings');
                } elseif ($added['added'] == 0) {
                    setEventMessages($langs->trans('SurveySessionInviteesNone'), [], 'warnings');
                }
            }
        } elseif ($action == 'remove_invitee') {
            $removed = digiquali_survey_session_remove_invitee($object, GETPOSTINT('invitee_id'));
            if ($removed !== null) {
                setEventMessages($langs->trans('SurveySessionInviteeRemoved', dol_escape_htmltag($removed['name'])), []);
            }
        } elseif ($confirm == 'yes') {
            $sheet->fetch($object->fk_sheet);
            $isReminder = $action == 'confirm_remind_invitees';
            $sending    = digiquali_survey_session_send_invitations($object, $sheet, $user, $isReminder);
            if ($sending['sent'] > 0) {
                setEventMessages($langs->trans($isReminder ? 'SurveySessionRemindersSent' : 'SurveySessionInvitationsSent', $sending['sent']), []);
            } elseif (empty($sending['errors']) && empty($sending['skipped'])) {
                setEventMessages($langs->trans($isReminder ? 'SurveySessionRemindNone' : 'SurveySessionSendInvitationsNone'), [], 'warnings');
            }
            // Who was left out and why : reminded less than a day ago
            if (!empty($sending['skipped'])) {
                setEventMessages($langs->trans('SurveySessionRemindSkippedRecent', $sending['skipped'], dol_print_date($sending['next_reminder'], 'dayhour', 'tzuserrel')), [], 'warnings');
            }
            if (!empty($sending['errors'])) {
                setEventMessages('', array_map('dol_escape_htmltag', $sending['errors']), 'errors');
            }
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#survey-session-invitees');
        exit;
    }

    if ($action == 'set_categories' && $permissiontoadd) {
        if ($object->fetch($id) > 0) {
            $result = $object->setCategories(GETPOST('categories', 'array'));
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
            exit();
        }
    }

    if ($action == 'show_only_questions_with_no_answer') {
        // saturne.utils.toggleSetting() posts {<data-toggle-key>: value}, so the key is the action name
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data[$action])) {
            $tabParam['DIGIQUALI_' . dol_strtoupper($action)] = $data[$action];
            dol_set_user_param($db, $conf, $user, $tabParam);
        }
    }

    require_once __DIR__ . '/../../core/tpl/digiquali_answers_save_action.tpl.php';

    // Actions uploadPhoto, deletePhoto posted by the Saturne media block of the answer photos.
    // It replays the HTML of the response to refresh itself, so this must not redirect
    if (in_array($action, ['uploadPhoto', 'deletePhoto']) && $permissiontoadd && $object->status == Survey::STATUS_DRAFT && !$isSession) {
        require __DIR__ . '/../../core/tpl/actions/digiquali_media_block_actions.tpl.php';
    }

    // Actions builddoc, forcebuilddoc, remove_file
    require_once dol_buildpath('/saturne/core/tpl/documents/documents_action.tpl.php');

    // Action to generate pdf from odt file
    require_once dol_buildpath('/saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php');

    // Actions confirm_lock, confirm_archive
    require_once dol_buildpath('/saturne/core/tpl/actions/object_workflow_actions.tpl.php');

    // Actions to send emails
    $triggersendname = 'SURVEY_SENTBYMAIL';
    $autocopy        = 'MAIN_MAIL_AUTOCOPY_AUDIT_TO';
    $trackid         = 'survey' . $object->id;
    require_once DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$title   = $langs->trans(ucfirst($object->element));
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(1,'', $title, $helpUrl);

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
        exit;
    }

    print load_fiche_titre($langs->trans('New' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form method="POST" id="createObjectForm" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="'. $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    if (!empty(GETPOST('fk_sheet'))) {
        $sheet->fetch(GETPOSTINT('fk_sheet'));
    }

    //FK SHEET
    print '<tr><td class="fieldrequired">' . $langs->trans('Sheet') . '</td><td>';
    print img_picto('', $sheet->picto, 'class="pictofixedwidth"') . $sheet->selectSheetList(GETPOST('fk_sheet') ?: $sheet->id, 'fk_sheet', 's.type = \'' . $object->element . '\' AND s.status = ' . Sheet::STATUS_LOCKED);
    print '<a class="butActionNew" href="' . dol_buildpath('/digiquali/view/sheet/sheet_card.php', 1) . '?action=create" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddSheet') . '"></span></a>';
    print '</td></tr>';

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Categories
    if (isModEnabled('categorie')) {
        print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
        $categoriesArborescence = $form->select_all_categories($object->element, '', 'parent', 64, 0, 1);
        print img_picto('', 'category', 'class="pictofixedwidth"').$form::multiselectarray('categories', $categoriesArborescence, GETPOST('categories', 'array'), '', 0, 'minwidth100imp maxwidth500 widthcentpercentminusxx');
        print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/categories/index.php?type=' . $object->element . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddCategories') . '"></span></a>';
        print '</td></tr>';
    }

    print '</table>';
    print '<hr>';

    print '<table class="border centpercent tableforfieldcreate object-table linked-objects">';

    print '<div class="fields-content">';

    foreach($objectsMetadata as $objectType => $objectMetadata) {
        if (!empty($objectMetadata['conf'] && preg_match('/"' . $objectType . '":1/', $sheet->element_linked))) {
            $objectArray    = [];
            $objectPostName = $objectMetadata['post_name'];
            $objectPost     = GETPOST($objectPostName) ?: (GETPOST('fromtype') == $objectMetadata['link_name'] ? GETPOST('fromid') : '');

            $objectFilter = [];
            if ((dol_strlen($objectMetadata['fk_parent']) > 0 && GETPOST($objectMetadata['parent_post']) > 0)) {
                $objectFilter = [
                    'customsql' => $objectMetadata['fk_parent'] . ' = ' . GETPOST($objectMetadata['parent_post'])
                ];
            } elseif (!empty($objectMetadata['filter'])) {
                $objectFilter = ['customsql' => $objectMetadata['filter']];
            }

            $objectList = saturne_fetch_all_object_type($objectMetadata['class_name'], '', '', 0, 0, $objectFilter);
            if (is_array($objectList) && !empty($objectList)) {
                foreach($objectList as $objectSingle) {
                    $objectName = '';
                    $nameField = $objectMetadata['name_field'];
                    if (strstr($nameField, ',')) {
                        $nameFields = explode(', ', $nameField);
                        if (is_array($nameFields) && !empty($nameFields)) {
                            foreach($nameFields as $subnameField) {
                                $objectName .= $objectSingle->$subnameField . ' ';
                            }
                        }
                    } else {
                        $objectName = $objectSingle->$nameField;
                    }
                    $objectArray[$objectSingle->id] = $objectName;
                }
            }

            print '<tr><td class="titlefieldcreate">' . $langs->transnoentities($objectMetadata['langs']) . '</td><td>';
            print img_picto('', $objectMetadata['picto'], 'class="pictofixedwidth"');
            print $form::selectarray($objectPostName, $objectArray, $objectPost, $langs->trans('Select') . ' ' . strtolower($langs->trans($objectMetadata['langs'])), 0, 0, '', 0, 0, dol_strlen(GETPOST('fromtype')) > 0 && GETPOST('fromtype') != $objectMetadata['link_name'], '', 'maxwidth500 widthcentpercentminusxx');
            print '<a class="butActionNew" href="' . dol_buildpath($objectMetadata['create_url'], 1) . '?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('Create') . ' ' . strtolower($langs->trans($objectMetadata['langs'])) . '"></span></a>';
            print '</td></tr>';
        }
    }

    print '</div>';

    // Other attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create', 'Cancel', [], 0, 'wpeo-button');

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $object->fetch_optionals();

    saturne_get_fiche_head($object, 'card', $title);
    saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

    $sheet->fetch($object->fk_sheet);
    $questions = $sheet->fetchAllQuestions();

    $questionCounter = 0;
    if (!empty($questions)) {
        $questionCounter = count($questions);
    }

    $answerCounter = 0;
    if (is_array($object->lines) && !empty($object->lines)) {
        foreach($object->lines as $objectLine) {
            if (dol_strlen($objectLine->answer) > 0) {
                $answerCounter++;
            }
        }
    }

    $formConfirm = '';

    // Validate confirmation
    if (($action == 'validate' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $questionConfirmInfo = $langs->trans('YouAnswered') . ' ' . $answerCounter . ' ' . $langs->trans('question(s)')  . ' ' . $langs->trans('On') . ' ' . $questionCounter . '.';
        if ($questionCounter - $answerCounter != 0) {
            $questionConfirmInfo .= '<br><b>' . $langs->trans('BewareQuestionsAnswered', $questionCounter - $answerCounter) . '</b>';
        }

        [$numberOfAnweredQuestions, $numberOfQuestions, $correctPoints, $totalPoints, $atLeastOneIncorrectSubGroup] = $object->calculatePoints();
        $correctAnswersRate = 0;
        if ($totalPoints > 0) {
            $correctAnswersRate = round($correctPoints / $totalPoints * 100, 2);
        }
        $isPassed = $object->isCorrectFromPoints($correctPoints, $totalPoints) && !$atLeastOneIncorrectSubGroup;

        $questionConfirmInfo .= '<br><br><table style="width: 100%; font-weight: bold;">';
        $questionConfirmInfo .= '<tr><td style="padding-bottom: 5px;">' . $langs->trans('SuccessScore') . '</td><td style="padding-bottom: 5px;">: ' . $object->success_rate . ' %</td></tr>';
        $questionConfirmInfo .= '<tr><td style="padding-bottom: 5px;">' . $langs->trans('ObtainedScore') . '</td><td style="padding-bottom: 5px;">: ' . $correctAnswersRate . ' %</td></tr>';
        $questionConfirmInfo .= '<tr><td style="padding-bottom: 5px; color: ' . ($isPassed ? '#28a745' : '#dc3545') . ';">' . $langs->trans('Result') . '</td><td style="padding-bottom: 5px; color: ' . ($isPassed ? '#28a745' : '#dc3545') . ';">: ' . ($isPassed ? 'OK' : 'KO') . '</td></tr>';
        $questionConfirmInfo .= '</table>';

        $questionConfirmInfo .= '<br><br><b>' . $langs->trans('ConfirmValidateSurvey') . '</b>';
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ValidateObject', $langs->transnoentities('The' . ucfirst($object->element))), $questionConfirmInfo, 'confirm_validate', '', 'yes', 'actionButtonValidate', 350, 600);
    }

    // Draft confirmation
    if (($action == 'draft' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&object_type=' . $object->element, $langs->trans('ReOpenObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmReOpenObject', $langs->transnoentities('The' . ucfirst($object->element)), $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_setdraft', '', 'yes', 'actionButtonInProgress', 350, 600);
    }

    // Lock confirmation
    if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
    }

    // Clone confirmation
    if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
        // Define confirmation messages
        $formQuestionClone = [
            ['type' => 'checkbox', 'name' => 'clone_answers',    'label' => $langs->trans('CloneAnswers'),    'value' => 1],
            ['type' => 'checkbox', 'name' => 'clone_attendants', 'label' => $langs->trans('CloneAttendants'), 'value' => 1],
            ['type' => 'checkbox', 'name' => 'clone_photos',     'label' => $langs->trans('ClonePhotos'),     'value' => 1]
        ];

        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref), 'confirm_clone', $formQuestionClone, 'yes', 'actionButtonClone', 350, 600);
    }

    // Delete confirmation
    if ($action == 'delete') {
        $formConfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
    }

    // Sending confirmation: the personal links to the invitees not invited yet, or the reminder to those who have not answered
    if (in_array($action, ['send_invitations', 'remind_invitees'], true) && $permissiontoadd && digiquali_survey_is_session($object)) {
        $isReminder    = $action == 'remind_invitees';
        $inviteeCounts = digiquali_survey_session_count_invitees(digiquali_survey_session_get_invitees($object), digiquali_survey_session_get_responses($object));
        $recipients    = $isReminder ? $inviteeCounts['remindable'] : $inviteeCounts['new'];
        $confirmText   = $langs->trans($isReminder ? 'SurveySessionConfirmRemind' : 'SurveySessionConfirmSendInvitations', $recipients);
        // Those reminded less than a day ago are left out : said before sending
        if ($isReminder && $inviteeCounts['reminded_recently'] > 0) {
            $confirmText .= '<br><br>' . $langs->trans('SurveySessionRemindSkippedRecent', $inviteeCounts['reminded_recently'], dol_print_date($inviteeCounts['next_reminder'], 'dayhour', 'tzuserrel'));
        }
        $formConfirm  .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans($isReminder ? 'SurveySessionRemindNonRespondents' : 'SurveySessionSendInvitations', $recipients), $confirmText, 'confirm_' . $action, '', 'yes', 1);
    }

    // Call Hook formConfirm
    $parameters = ['formConfirm' => $formConfirm];
    $resHook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($resHook)) {
        $formConfirm .= $hookmanager->resPrint;
    } elseif ($resHook > 0) {
        $formConfirm = $hookmanager->resPrint;
    }

    // Print form confirm
    print $formConfirm;

    if ($conf->browser->layout == 'phone') {
        $onPhone = 1;
    } else {
        $onPhone = 0;
    }

    print '<div class="fichecenter object-infos' . ($onPhone ? ' hidden' : '') . '">';
    print '<div class="fichehalfleft">';
    print '<table class="border centpercent tableforfield">';

    // Common attributes
    unset($object->fields['projectid']); // Hide field already shown in banner

    // A collect session is never answered itself : its card gives the link of the session, with its QR code to print or
    // project, instead of the public links of the survey. The link opens the session to anyone : it is only shown to the
    // users who may change the survey
    $isSession = digiquali_survey_is_session($object);
    if ($isSession && getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE') && $permissiontoadd) {
        $sessionUrl = digiquali_survey_session_url($object);
        print '<tr><td class="titlefield">' . $langs->trans('SurveySessionLink');
        print showValueWithClipboardCPButton($sessionUrl, 0, '&nbsp;');
        print '</td><td>';
        print '<a href="' . dol_escape_htmltag($sessionUrl) . '" target="_blank">' . $langs->trans('SurveySessionGoToPage') . ' <i class="fa fa-external-link"></i></a>';
        print '<div class="survey-qrcode">' . digiquali_qrcode_img($sessionUrl, 160);
        print '<a class="survey-qrcode__download" href="' . digiquali_qrcode_data_uri($sessionUrl) . '" download="qrcode-' . dol_sanitizeFileName($object->ref) . '.svg"><i class="fas fa-download"></i> ' . $langs->trans('SurveyQrCodeDownload') . '</a>';
        print '</div>';
        print '</td></tr>';
    }

    if (!$isSession && getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) {
        $publicInterfaceUrl = dol_buildpath('/digiquali/public/survey/public_survey.php', 3) . '?track_id=' . $object->track_id . '&entity=' . $conf->entity;
        print '<tr><td class="titlefield">' . $langs->trans('PublicInterface') . ' <a href="' . $publicInterfaceUrl . '" target="_blank"><i class="fas fa-qrcode"></i></a>';
        print showValueWithClipboardCPButton($publicInterfaceUrl, 0, '&nbsp;');
        print '</td>';
        print '<td>' . saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/survey/' . $object->ref . '/qrcode/', 'small', 1, 0, 0, 0, 80, 80, 0, 0, 0, 'survey/'. $object->ref . '/qrcode/', $object, '', 0, 0) . '</td></tr>';

        // Answer public interface
        $publicAnswerUrl = dol_buildpath('/digiquali/public/public_answer.php', 3) . '?track_id=' . $object->track_id . '&object_type=' . $object->element . '&document_type=SurveyDocument&entity=' . $conf->entity;
        print '<tr><td class="titlefield">' . $langs->trans('PublicAnswer') . ' <a href="' . $publicAnswerUrl . '" target="_blank"><i class="fas fa-qrcode"></i></a>';
        print showValueWithClipboardCPButton($publicAnswerUrl, 0, '&nbsp;');
        print '</td><td>';
        print '<a href="' . $publicAnswerUrl . '" target="_blank">' . $langs->trans('GoToPublicAnswerPage') . ' <i class="fa fa-external-link"></a>';
        print '</td></tr>';
    }

    // The respondent of a nominative collect session, by the name he gave
    if (dol_strlen((string) $object->respondent_name) > 0) {
        print '<tr><td class="titlefield">' . $langs->trans('SurveyRespondent') . '</td><td>' . dol_escape_htmltag($object->respondent_name);
        print dol_strlen((string) $object->respondent_email) > 0 ? ' - ' . dol_print_email($object->respondent_email, 0, 0, 1) : '';
        print '</td></tr>';
    }

    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Categories
    if (isModEnabled('categorie')) {
        print '<tr><td class="valignmiddle">' . $langs->trans('Categories') . '</td>';
        if ($action != 'categories') {
            print '<td style="display: flex;">' . ($object->status < Survey::STATUS_LOCKED ? '<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=categories&token=' . newToken() . '&id=' . $object->id . '">' . img_edit($langs->trans('Modify')) . '</a>' : '<img src="" alt="">');
            print $form->showCategories($object->id, 'survey', 1) . '</td>';
        }
        if ($permissiontoadd && $action == 'categories') {
            $categoriesArborescence = $form->select_all_categories('survey', '', 'parent', 64, 0, 1);
            if (is_array($categoriesArborescence) && !empty($categoriesArborescence)) {
                print '<td>';
                print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post">';
                print '<input type="hidden" name="token" value="'.newToken().'">';
                print '<input type="hidden" name="action" value="set_categories">';

                $cats          = $category->containing($object->id, 'survey');
                $arraySelected = [];
                if (is_array($cats)) {
                    foreach ($cats as $cat) {
                        $arraySelected[] = $cat->id;
                    }
                }
                print img_picto('', 'category') . $form::multiselectarray('categories', $categoriesArborescence, (GETPOSTISSET('categories') ? GETPOST('categories', 'array') : $arraySelected), '', 0, 'minwidth100imp quatrevingtpercent widthcentpercentminusxx');
                print '<input type="submit" class="button button-edit small" value="'.$langs->trans('Save').'">';
                print '</form>';
                print '</td>';
            }
        }
        print '</tr>';
    }

    $object->fetchObjectLinked('', '', $object->id, 'digiquali_survey');
    $linkedObjectType = key($object->linkedObjects);
    foreach($objectsMetadata as $objectMetadata) {
        if (empty($objectMetadata['conf']) || $objectMetadata['link_name'] != $linkedObjectType) {
            continue;
        }

        $linkedObject = $object->linkedObjects[$objectMetadata['link_name']][key($object->linkedObjects[$objectMetadata['link_name']])];

        print '<tr><td class="titlefield">';
        print $langs->trans($objectMetadata['langs']);
        print '</td><td>';
        print $linkedObject->getNomUrl(1);
        print property_exists($linkedObject, $objectMetadata['label_field']) ? '<span class="opacitymedium">' . ' - ' . dol_trunc($linkedObject->{$objectMetadata['label_field']}) . '</span>' : '';
        print '<td></tr>';
    }

    print '<tr class="linked-medias photo question-table"><td class=""><label for="photos">' . $langs->trans('Photo') . '</label></td><td class="linked-medias-list">';
    $pathPhotos = $conf->digiquali->multidir_output[$conf->entity] . '/survey/'. $object->ref . '/photos/';
    $fileArray  = dol_dir_list($pathPhotos, 'files');
    ?>
    <span class="add-medias" <?php echo ($object->status < Survey::STATUS_LOCKED) ? '' : 'style="display:none"' ?>>
        <input hidden multiple class="fast-upload<?php echo getDolGlobalInt('SATURNE_USE_FAST_UPLOAD_IMPROVEMENT') ? '-improvement' : ''; ?>" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">
        <input type="hidden" class="fast-upload-options" data-from-subtype="photo" data-from-subdir="photos"/>
        <label for="fast-upload-photo-default">
            <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?>">
                <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
            </div>
        </label>
        <input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?>"/>
        <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?> 'open-media-gallery add-media modal-open" value="0">
            <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $object->id?>" data-from-type="survey" data-from-subtype="photo" data-from-subdir="photos"/>
            <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
        </div>
    </span>
    <?php
    print saturne_show_medias_linked('digiquali', $pathPhotos, 'small', 0, 0, 0, 0, $onPhone ? 40 : 50, $onPhone ? 40 : 50, 0, 0, 0, 'survey/' . $object->ref . '/photos/', $object, 'photo', $object->status < Survey::STATUS_LOCKED, $permissiontodelete && $object->status < Survey::STATUS_LOCKED);
    print '</td></tr>';

    print '<tr class="field_success_rate"><td class="titlefield fieldname_success_rate">';
    print $form->editfieldkey('SuccessScore', 'success_rate', $object->success_rate, $object, $permissiontoadd && $object->status < Survey::STATUS_LOCKED, 'string', '', 0, 0,'id', $langs->trans('PercentageValue'));
    print '</td><td class="valuefield fieldname_success_rate">';
    if ($action == 'editsuccess_rate') {
        print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="setsuccess_rate">';
        print '<table class="nobordernopadding centpercent">';
        print '<tbody><tr><td><input type="number" id="success_rate" name="success_rate" step="0.01" min="0" max="100" onkeyup=window.saturne.utils.enforceMinMax(this) value="' . $object->success_rate . '">';
        print '</td><td class="left"><input type="submit" class="smallpaddingimp button" name="modify" value="' . $langs->trans('Modify') . '"><input type="submit" class="smallpaddingimp button button-cancel" name="cancel" value="' . $langs->trans('Cancel') . '"></td></tr></tbody></table>';
        print '</form>';
    } else {
        print (!empty($object->success_rate) ? price2num($object->success_rate, 2) : 0) . ' %';
    }
    print '</td></tr>';

    [$numberOfAnweredQuestions, $numberOfQuestions, $correctPoints, $totalPoints, $atLeastOneIncorrectSubGroup] = $object->calculatePoints();
    $correctAnswersRate = 0;
    if ($totalPoints > 0) {
        $correctAnswersRate = round($correctPoints / $totalPoints * 100, 2);
    }
    print '<tr class="field_obtained_score"><td class="titlefield fieldname_obtained_score">';
    print $langs->trans('ObtainedScore');
    print '</td><td class="valuefield fieldname_obtained_score">';
    print '<span id="survey-obtained-score">' . $correctAnswersRate . ' % (' . $correctPoints . ' / ' . $totalPoints . ' points)</span>';
    print '</td></tr>';

    // Other attributes. Fields from hook formObjectOptions and Extrafields
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    // @TODO pas opti
    $cantValidateSurvey = 0;
    $mandatoryArray     = json_decode($sheet->mandatory_questions, true);
    if (is_array($mandatoryArray) && !empty($mandatoryArray) && is_array($questions) && !empty($questions)) {
        foreach ($questions as $resultQuestion) {
            if (in_array($resultQuestion->id, $mandatoryArray)) {
                $resultAnswer   = $objectLine->fetchFromParentWithQuestion($object->id, $resultQuestion->id);
                if (($resultAnswer > 0 && is_array($resultAnswer)) || !empty($objectLine)) {
                    $itemSurveyDet = !empty($resultAnswer) ? array_shift($resultAnswer) : $objectLine;
                    if ($resultQuestion > 0) {
                        if (empty($itemSurveyDet->comment) && empty($itemSurveyDet->answer)) {
                            $cantValidateSurvey++;
                        }
                    }
                }
            }
        }
    }

    print '<div class="clearboth"></div>';

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?action=save&id=' . $object->id . '" id="saveObject" enctype="multipart/form-data">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="save">';

    // Buttons for actions
    if ($action != 'presend') {
        print '<div class="tabsAction">';
        $parameters = [];
        $resHook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($resHook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($resHook)) {
            // A collect session is neither answered, nor validated, reopened or signed as a survey: it is closed and
            // reopened by the buttons of the session, further on
            if (!$isSession) {
                // Save question answer
                $displayButton = $onPhone ? '<i class="fas fa-save fa-2x"></i>' : '<i class="fas fa-save"></i>' . ' ' . $langs->trans('Save');
                if ($object->status == Survey::STATUS_DRAFT) {
                    print '<span class="butActionRefused" id="saveButton" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=save' . '">' . $displayButton . ' <i class="fas fa-circle" style="color: red; display: none; ' . ($onPhone ? 'vertical-align: top;' : '') . '"></i></span>';
                } else {
                    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
                }

                // Validate
                $displayButton = $onPhone ? '<i class="fas fa-check fa-2x"></i>' : '<i class="fas fa-check"></i>' . ' ' . $langs->trans('Validate');
                if ($object->status == Survey::STATUS_DRAFT && empty($cantValidateSurvey)) {
                    print '<span class="validateButton butAction" id="actionButtonValidate">' . $displayButton . '</span>';
                } elseif ($cantValidateSurvey > 0) {
                    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('QuestionMustBeAnswered', $cantValidateSurvey)) . '">' . $displayButton . '</span>';
                } elseif ($object->status < Survey::STATUS_DRAFT) {
                    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
                }

                // ReOpen
                $displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('ReOpenDoli');
                if ($object->status == Survey::STATUS_VALIDATED) {
                    print '<span class="butAction" id="actionButtonInProgress">' . $displayButton . '</span>';
                } elseif ($object->status > Survey::STATUS_VALIDATED) {
                    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
                }

                // Sign
                $displayButton = $onPhone ? '<i class="fas fa-signature fa-2x"></i>' : '<i class="fas fa-signature"></i>' . ' ' . $langs->trans('Sign');
                if ($object->status == Survey::STATUS_VALIDATED && !$signatory->checkSignatoriesSignatures($object->id, $object->element)) {
                    print '<a class="butAction" id="actionButtonSign" href="' . dol_buildpath('/saturne/view/saturne_attendants.php', 3) . '?id=' . $object->id . '&module_name=DigiQuali&object_type=' . $object->element . '&document_type=SurveyDocument&attendant_table_mode=simple">' . $displayButton . '</a>';
                } else {
                    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidatedToSign', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
                }
            }

            // Lock
            $displayButton = $onPhone ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->trans('Lock');
            if ($object->status == Survey::STATUS_VALIDATED && $signatory->checkSignatoriesSignatures($object->id, $object->element)) {
                print '<span class="butAction" id="actionButtonLock">' . $displayButton . '</span>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('AllSignatoriesMustHaveSigned', $langs->transnoentities('The' . ucfirst($object->element)))) . '">' . $displayButton . '</span>';
            }

            // Send email
            $displayButton = $onPhone ? '<i class="fas fa-envelope fa-2x"></i>' : '<i class="fas fa-envelope"></i>' . ' ' . $langs->trans('SendMail') . ' ';
            if ($object->status != Survey::STATUS_LOCKED) {
                // dol_most_recent_file() returns null while no document has been generated yet
                $fileParams = dol_most_recent_file($upload_dir . '/' . $object->element . 'document' . '/' . $object->ref);
                if (!empty($fileParams) && file_exists($fileParams['fullname']) && !strstr($fileParams['name'], 'specimen')) {
                    $forceBuildDoc = 0;
                } else {
                    $forceBuildDoc = 1;
                }
                print dolGetButtonAction($displayButton, '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&forcebuilddoc=' . $forceBuildDoc . '&mode=init#formmailbeforetitle', '', $object->status != Survey::STATUS_LOCKED);
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToSendEmail', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

            // Archive
            $displayButton = $onPhone ?  '<i class="fas fa-archive fa-2x"></i>' : '<i class="fas fa-archive"></i>' . ' ' . $langs->trans('Archive');
            if ($object->status == Survey::STATUS_LOCKED && !empty(dol_dir_list($upload_dir . '/'. $object->element . 'document/' . dol_sanitizeFileName($object->ref)))) {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_archive&token=' . newToken() . '">' . $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
            }

            // Clone
            $displayButton = $onPhone ? '<i class="fas fa-clone fa-2x"></i>' : '<i class="fas fa-clone"></i>' . ' ' . $langs->trans('ToClone');
            print '<span class="butAction" id="actionButtonClone">' . $displayButton . '</span>';

            // Collect session : one link, one survey per respondent
            if ($permissiontoadd) {
                $isSession = digiquali_survey_is_session($object);
                if (!$isSession && $object->status == Survey::STATUS_DRAFT) {
                    $displayButton = $onPhone ? '<i class="fas fa-users fa-2x"></i>' : '<i class="fas fa-users"></i>' . ' ' . $langs->trans('SurveySessionOpen');
                    // The session is the template of its responses: a survey already answered cannot become one
                    if ($answerCounter > 0) {
                        print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('SurveySessionOpenHasAnswers')) . '">' . $displayButton . '</span>';
                    } else {
                        print '<a class="butAction classfortooltip" title="' . dol_escape_htmltag($langs->trans('SurveySessionOpenHelp')) . '" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=open_session&token=' . newToken() . '">' . $displayButton . '</a>';
                    }
                } elseif ($isSession && $object->status == Survey::STATUS_DRAFT) {
                    $displayButton = $onPhone ? '<i class="fas fa-lock fa-2x"></i>' : '<i class="fas fa-lock"></i>' . ' ' . $langs->trans('SurveySessionClose');
                    print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=close_session&token=' . newToken() . '">' . $displayButton . '</a>';
                } elseif ($isSession && $object->status == Survey::STATUS_VALIDATED) {
                    $displayButton = $onPhone ? '<i class="fas fa-lock-open fa-2x"></i>' : '<i class="fas fa-lock-open"></i>' . ' ' . $langs->trans('SurveySessionReopen');
                    print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=reopen_session&token=' . newToken() . '">' . $displayButton . '</a>';
                }
            }

            // Delete (need delete permission, or if draft, just need create/modify permission)
            $displayButton = $onPhone ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->trans('Delete');
            print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete || ($object->status == Survey::STATUS_DRAFT && $permissiontoadd));
        }
        print '</div>';
    }

    // QUESTION LINES
    print '<div class="div-table-responsive-no-min questionLines" style="overflow-x: unset !important;">';

    // The questions of a collect session are shown for reference, read only: every respondent answers a survey of his
    // own, opened by the link of the session. Shown as a validated survey: no field, no photo nor task to add, and no
    // correction, which would mark as wrong the answers nobody gave
    if ($isSession) {
        print load_fiche_titre($langs->transnoentities('LinkedQuestionsList', $questionCounter), '', '');
        print saturne_show_notice($langs->transnoentities('SurveySessionTemplateTitle'), $langs->transnoentities('SurveySessionTemplateReadOnly'), 'info', 'notice-survey-session-template', true, false);
        print '<div id="tablelines" class="question-answer-container">';
        $sessionStatus       = $object->status;
        $sessionAddTaskRight = $permissionToAddTask ?? 0;
        $object->status      = Survey::STATUS_VALIDATED;
        $permissionToAddTask = 0;
        $object->displayAnswers($objectLine, $sheet->fetchQuestionsAndGroups(), false);
        $object->status      = $sessionStatus;
        $permissionToAddTask = $sessionAddTaskRight;
        print '</div>';
    }

    if (!$isSession) :
 ?>

    <div class="progress-info">
        <span class="badge badge-info" style="margin-right: 10px;"><?php print $answerCounter . '/' . $questionCounter; ?></span>
        <div class="progress-bar" style="margin-right: 10px;">
            <div class="progress progress-bar-success" style="width:<?php print ($questionCounter > 0 ? ($answerCounter/$questionCounter) * 100 : 0) . '%'; ?>;" title="<?php print ($questionCounter > 0 ? $answerCounter . '/' . $questionCounter : 0); ?>"></div>
        </div>
        <?php if ($answerCounter != $questionCounter) {
            // The user preference does not exist until the toggle has been used at least once
            $showOnlyWithNoAnswer = !empty($user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER);

            // The generic handler of control.js binds on data-toggle-action, not on a class
            $toggleAttributes  = 'data-toggle-action="show_only_questions_with_no_answer"';
            $toggleAttributes .= ' data-toggle-key="show_only_questions_with_no_answer"';
            $toggleAttributes .= ' data-update-targets=".progress-info,.question-answer-container"';
            $toggleAttributes .= ' class="show-only-questions-with-no-answer marginrightonly"';

            print img_picto($langs->trans($showOnlyWithNoAnswer ? 'Enabled' : 'Disabled'), $showOnlyWithNoAnswer ? 'switch_on' : 'switch_off', $toggleAttributes);
            print $form->textwithpicto($showOnlyWithNoAnswer ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>', $langs->trans('ShowOnlyQuestionsWithNoAnswer'), 1, 'help', 'marginrightonly');
        } else {
            $user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER = 0;
        } ?>
    </div>

    <?php if (empty($user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER) || $answerCounter != $questionCounter) {
        print load_fiche_titre($langs->transnoentities('LinkedQuestionsList', $questionCounter), '', '');
        print '<div id="tablelines" class="question-answer-container">';
        $questionsAndGroups = $sheet->fetchQuestionsAndGroups();
        $object->displayAnswers($objectLine, $questionsAndGroups, false);
        print '</div>';
    }
    endif;

    print '</div>';
    print '</form>';
    print dol_get_fiche_end();

    // Responses of a collect session, each one a survey of its own
    if (digiquali_survey_is_session($object)) {
        $sessionResponses = digiquali_survey_session_get_responses($object);
        $surveyIsScored   = digiquali_survey_is_scored($object);
        $sessionInvitees  = digiquali_survey_session_get_invitees($object);
        $isNominative     = !empty($object->nominative);
        $showRespondent   = $isNominative || !empty(array_filter(array_column($sessionResponses, 'respondent_name')));

        $sessionButtons  = '<a class="butAction" href="' . dol_buildpath('/digiquali/view/survey/survey_session_summary.php', 1) . '?id=' . $object->id . '"><i class="fas fa-chart-bar"></i> ' . $langs->trans('SurveySessionSummary') . '</a>';
        $sessionButtons .= '<a class="butAction" href="' . dol_buildpath('/digiquali/view/survey/survey_answers_export.php', 1) . '?session_id=' . $object->id . '&token=' . newToken() . '"><i class="fas fa-file-csv"></i> ' . $langs->trans('SurveyAnswersExportCsv') . '</a>';

        print '<div class="survey-session">';
        print load_fiche_titre($langs->trans('SurveySessionResponses', count($sessionResponses)), $sessionButtons, 'fontawesome_fa-users_fas');
        print '<div class="survey-session__infos opacitymedium">';
        print '<span>' . $langs->trans($object->status == Survey::STATUS_DRAFT ? 'SurveySessionOpenSince' : 'SurveySessionClosedOn', dol_print_date($object->status == Survey::STATUS_DRAFT ? $object->date_creation : $object->tms, 'dayhour', 'tzuserrel')) . '</span>';
        if ($object->status == Survey::STATUS_DRAFT && !empty($object->date_end)) {
            print '<span>' . $langs->trans('SurveySessionClosesOn', dol_print_date($object->date_end, 'dayhour', 'tzuserrel')) . '</span>';
        }
        print '<span>' . $langs->trans($isNominative ? 'SurveySessionModeNominative' : 'SurveySessionModeAnonymous') . '</span>';
        print '</div>';

        // While the collect is open: its planned closing date, and whether the respondents give their name
        if ($permissiontoadd && $object->status == Survey::STATUS_DRAFT) {
            $modeLocked = !empty($sessionResponses) || !empty($sessionInvitees);
            print '<form method="POST" class="survey-session__options" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="set_session_options">';
            print '<div class="survey-session__option">';
            print '<span class="survey-session__option-label">' . $form->textwithpicto($langs->trans('SurveySessionDateEnd'), $langs->trans('SurveySessionDateEndHelp')) . '</span>';
            print $form->selectDate(!empty($object->date_end) ? $object->date_end : -1, 'date_end', 1, 1, 1, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzuserrel');
            print '</div>';
            print '<fieldset class="survey-session__option survey-session__mode"' . ($modeLocked ? ' disabled' : '') . '>';
            print '<legend class="survey-session__option-label">' . $langs->trans('SurveySessionMode') . '</legend>';
            foreach ([0 => 'SurveySessionModeAnonymousHelp', 1 => 'SurveySessionModeNominativeHelp'] as $modeValue => $modeLabel) {
                print '<label class="survey-session__mode-choice"><input type="radio" name="nominative" value="' . $modeValue . '"' . ((int) $isNominative == $modeValue ? ' checked' : '') . '> ' . $langs->trans($modeLabel) . '</label>';
            }
            if ($modeLocked) {
                print '<span class="survey-session__mode-locked">' . $langs->trans(!empty($sessionResponses) ? 'SurveySessionModeLocked' : 'SurveySessionModeLockedInvitees') . '</span>';
            }
            print '</fieldset>';
            print '<input type="submit" class="button button-save" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
            print '</form>';
        }

        // Scrolls by itself on a phone instead of widening the page
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td>' . $langs->trans('Ref') . '</td>' . ($showRespondent ? '<td>' . $langs->trans('SurveyRespondent') . '</td>' : '') . '<td>' . $langs->trans('DateCreation') . '</td><td>' . $langs->trans('Status') . '</td>' . ($surveyIsScored ? '<td class="right">' . $langs->trans('SuccessScore') . '</td>' : '') . '</tr>';
        if (empty($sessionResponses)) {
            print '<tr><td colspan="' . (3 + ($showRespondent ? 1 : 0) + ($surveyIsScored ? 1 : 0)) . '"><span class="opacitymedium">' . $langs->trans('SurveySessionNoResponse') . '</span></td></tr>';
        }
        foreach ($sessionResponses as $sessionResponse) {
            print '<tr class="oddeven"><td>' . $sessionResponse->getNomUrl(1) . '</td>';
            if ($showRespondent) {
                print '<td>' . dol_escape_htmltag((string) $sessionResponse->respondent_name);
                print dol_strlen((string) $sessionResponse->respondent_email) > 0 ? ' <span class="opacitymedium">' . dol_escape_htmltag($sessionResponse->respondent_email) . '</span>' : '';
                print '</td>';
            }
            print '<td>' . dol_print_date($sessionResponse->date_creation, 'dayhour', 'tzuserrel') . '</td>';
            print '<td>' . $sessionResponse->getLibStatut(5) . '</td>';
            // The success rate of the responses only for a scored survey : they all share the sheet of the session
            if ($surveyIsScored) {
                print '<td class="right">' . ($sessionResponse->success_rate !== null && $sessionResponse->success_rate !== '' ? price2num($sessionResponse->success_rate, 2) . ' %' : '') . '</td>';
            }
            print '</tr>';
        }
        print '</table>';
        print '</div>';

        // Invitees of a nominative session: personal links by e-mail, follow-up and reminders
        require __DIR__ . '/../../core/tpl/survey/survey_session_invitees.tpl.php';

        print '</div>';
    }

    if ($action != 'presend') {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        // Documents
        $objRef    = dol_sanitizeFileName($object->ref);
        $dirFiles  = $object->element . 'document/' . $objRef;
        $fileDir   = $upload_dir . '/' . $dirFiles;
        $urlSource = $_SERVER['PHP_SELF'] . '?id=' . $object->id;

        print saturne_show_documents('digiquali:' . ucfirst($object->element) . 'Document', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, $conf->global->DIGIQUALI_SURVEYDOCUMENT_DEFAULT_MODEL, 1, 0, 0, 0, '', '', '', $langs->defaultlang, '', $object, 0, 'remove_file', (($object->status > Survey::STATUS_DRAFT) ? 1 : 0), $langs->trans('ObjectMustBeValidatedToGenerate', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))));

        print '</div><div class="fichehalfright">';

        $moreHtmlCenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiQuali&object_type=' . $object->element);

        // List of actions on element
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formActions = new FormActions($db);
        $formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10, '', $moreHtmlCenter);

        print '</div></div>';
    }

    //Select mail models is same action as presend
    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    //@todo a check
    // Presend form
    $modelmail    = $object->element;
    $defaulttopic = 'InformationMessage';
    $diroutput    = $conf->digiquali->dir_output;
    $trackid      = $object->element . $object->id;

    require_once DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
}

// Photo editor modal (required by saturne_render_media_block)
require_once dol_buildpath('/saturne/core/tpl/medias/photo_editor_modal.tpl.php');

// End of page
llxFooter();
$db->close();
