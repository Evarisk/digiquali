<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \file    view/question/question_list.php
 * \ingroup digiquali
 * \brief   List page for question
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
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// load digiquali libraries
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/answer.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['errors']);

// Get parameters
$action     = GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');                                        // The bulk action (combo box choice into lists)

// Get list parameters
$toselect                                   = [];
[$confirm, $contextpage, $optioncss, $mode] = ['', '', '', ''];
$listParameters                             = saturne_load_list_parameters(basename(dirname(__FILE__)));
foreach ($listParameters as $listParameterKey => $listParameter) {
    $$listParameterKey = $listParameter;
}

// Get pagination parameters
[$limit, $page, $offset] = [0, 0, 0];
[$sortfield, $sortorder] = ['', ''];
$paginationParameters    = saturne_load_pagination_parameters();
foreach ($paginationParameters as $paginationParameterKey => $paginationParameter) {
    $$paginationParameterKey = $paginationParameter;
}

// Initialize technical objects
$object      = new Question($db);
$answer      = new Answer($db);
$sheet       = new Sheet($db);
$extrafields = new ExtraFields($db);
if (isModEnabled('categorie')) {
    $categorie = new Categorie($db);
}

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$contextpage]); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (isModEnabled('categorie')) {
    $searchCategories = GETPOST('search_category_' . $object->element . '_list', 'array');
}

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
    reset($object->fields);   // Reset is required to avoid key() to return null
    $sortfield = 't.date_creation'; // Set here default search field. By default, date_creation
}
if (!$sortorder) {
    $sortorder = 'DESC';
}

// Initialize array of search criterias
$searchAll        = trim(GETPOST('search_all'));
$search           = [];
$search['status'] = [1,2];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha') !== '') {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
    if (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
        $search[$key . '_dtend']   = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
    }
}

// List of fields to search into when doing a "search in all"
$fieldsToSearchAll = [];
foreach ($object->fields as $key => $val) {
    if (!empty($val['searchall'])) {
        $fieldsToSearchAll['t.' . $key] = $val['label'];
    }
}

// Definition of array of fields for columns
foreach ($object->fields as $key => $val) {
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible']);
        $arrayfields['t.' . $key] = [
            'label'    => $val['label'],
            'checked'  => (($visible < 0) ? 0 : 1),
            'enabled'  => ($visible != 3 && dol_eval($val['enabled'])),
            'position' => $val['position'],
            'help'     => $val['help'] ?? '',
        ];
    }
}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields    = dol_sort_array($arrayfields, 'position');

// Permissions
$permissiontoread   = $user->hasRight($object->module, $object->element, 'read');
$permissiontoadd    = $user->hasRight($object->module, $object->element, 'write');
$permissiontodelete = $user->hasRight($object->module, $object->element, 'delete');

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = ['arrayfields' => &$arrayfields];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Selection of new fields
    require_once DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        foreach ($object->fields as $key => $val) {
            $search[$key] = '';
            if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
                $search[$key.'_dtstart'] = '';
                $search[$key.'_dtend']   = '';
            }
        }
        $searchAll            = '';
        $toselect             = [];
        $search_array_options = [];
        $searchCategories     = [];
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
        $massaction = '';
    }

    // Mass actions
    $objectclass = 'Question';
    $objectlabel = 'Question';
    $uploaddir   = $conf->digiquali->dir_output;

    $langs->tab_translate['ErrorRecordHasChildren'] = $langs->tab_translate['ErrorQuestionUsedInSheet'];
    require_once DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

    if (($massaction == 'lock' || ($action == 'lock' && $confirm == 'yes')) && $permissiontoadd) {
        if (!empty($toselect)) {
            foreach ($toselect as $toSelectedId) {
                $object->fetch($toSelectedId);
                if ($object->type == 'UniqueChoice' || $object->type == 'MultipleChoices') {
                    $answerList = $answer->fetchAll('ASC', 'position', 0, 0, ['fk_question' => $object->id]);
                }
                if (($object->type != 'UniqueChoice' && $object->type != 'MultipleChoices') || !empty($answerList)) {
                    // Set locked OK
                    $object->setLocked($user, false);
                    setEventMessages($langs->trans('LockQuestion', $object->ref), []);
                } else {
                    // Set locked KO
                    setEventMessages($langs->trans('AnswerMustBeCreated', $object->ref), [], 'errors');
                }
            }
        }
    }

    if (($massaction == 'add_questions' || ($action == 'add_questions' && $confirm == 'yes')) && $permissiontoadd) {

        $elementElements = [];
        $sql             = "SELECT fk_target FROM llx_element_element WHERE targettype = 'digiquali_question'";
        $resql           = $db->query($sql);
        if ($resql) {
            $num_prods = $db->num_rows($resql);
            $i = 0;
            while ($i < $num_prods) {
                $i++;

                $row = $db->fetch_row($resql);
                $elementElements[] = $row[0];
            }
        }
        $toselect = array_diff($toselect, $elementElements);

        if (!empty($toselect)) {
            $totalQuestions  = 0;
            $questionInArray = [];
            if (GETPOSTISSET('sheet') && GETPOST('sheet') > 0) {
                $sheet->fetch(GETPOST('sheet'));
                $sheet->fetchObjectLinked($sheet->id, $object->module . '_' . $sheet->element, null, $object->module . '_' . $object->element, 'OR', 1, 'position');
                foreach ($toselect as $selected) {
                    $object->fetch($selected);
                    if (is_array($sheet->linkedObjectsIds[$object->module . '_' . $object->element]) && !empty($sheet->linkedObjectsIds[$object->module . '_' . $object->element]) && in_array($object->id, $sheet->linkedObjectsIds[$object->module . '_' . $object->element])) {
                        $questionInArray[] = $object->getNomUrl(1, 'nolink', 1);
                    } else {
                        $totalQuestions++;
                        $object->add_object_linked($object->module . '_' . $sheet->element, GETPOST('sheet'));
                        $questionIds   = $sheet->linkedObjectsIds[$object->module . '_' . $object->element];
                        $questionIds[] = $object->id;
                        $sheet->updateQuestionsPosition($questionIds);
                    }
                }
                if (!empty($questionInArray)) {
                    setEventMessages($langs->trans('WarningQuestionLink', count($questionInArray)) . ' ', $questionInArray, 'warnings');
                }
                if ($totalQuestions > 0) {
                    setEventMessages($langs->trans('AddQuestionLink', $totalQuestions) . ' ' . $sheet->getNomUrl(1), []);
                }
            } else {
                setEventMessages($langs->transnoentities('ObjectNotFound', img_picto('', $sheet->picto, 'class="paddingrightonly"') . $langs->transnoentities(ucfirst($sheet->element))), [], 'errors');
            }
        }
    }

    // Mass actions archive
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/list_massactions.tpl.php';
}

/*
 * View
 */

$title = $langs->trans(ucfirst($object->element) . 'List');
saturne_header(0,'', $title, $helpUrl ?? '', '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist');

require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_header.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_input.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_title.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_loop_object.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_footer.tpl.php';

// End of page
llxFooter();
$db->close();
