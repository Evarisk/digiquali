<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 *   	\file       view/question_group/question_group_card.php
 *		\ingroup    digiquali
 *		\brief      Page to create/edit/view question_group
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
	die('Include of digiquali main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once '../../class/questiongroup.class.php';
require_once '../../class/question.class.php';
require_once '../../class/sheet.class.php';
require_once '../../class/answer.class.php';
require_once '../../lib/digiquali_questiongroup.lib.php';
require_once '../../lib/digiquali_answer.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user, $langs;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'question_groupcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$sheetId             = GETPOST('sheet_id', 'int');

// Initialize objects
// Technical objets
$object         = new QuestionGroup($db);
$question       = new Question($db);
$answer         = new Answer($db);
$sheet          = new Sheet($db);
$extrafields    = new ExtraFields($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('question_groupcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$searchAll = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread   = $user->rights->digiquali->questiongroup->read;
$permissiontoadd    = $user->rights->digiquali->questiongroup->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->digiquali->questiongroup->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

// Security check - Protection if external user
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiquali/view/questiongroup/questiongroup_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiquali/view/questiongroup/questiongroup_card.php', 1).'?id='.($id > 0 ? $id : '__ID__') . ($sheetId ? '&sheet_id=' . $sheetId : '');
		}
	}

	if ($cancel && $action != 'update') {
		$backtopage .= '#answerList';
	}

    if ($action == 'add_question') {
        $questionIds = GETPOST('questionId', 'array');
        if (is_array($questionIds) && !empty($questionIds)) {
            foreach ($questionIds as $questionId) {
                $object->addQuestion($questionId);
            }
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id  . ($sheetId ? '&sheet_id=' . $sheetId : ''));
    }

	if ($action == 'moveLine' && $permissiontoadd) {
		$idsArray = json_decode(file_get_contents('php://input'), true);
		if (is_array($idsArray['order']) && !empty($idsArray['order'])) {
			$ids = array_values($idsArray['order']);
			$reIndexedIds = array_combine(range(1, count($ids)), array_values($ids));
		}
		$object->updateQuestionPosition($reIndexedIds);
	}

    if ($action == 'removeQuestion') {
        $questionId = GETPOST('questionId', 'int');
        if ($questionId > 0) {
            $question->fetch($questionId);
            $question->element = 'digiquali_'.$question->element;
            $question->deleteObjectLinked('', '', $object->id, $object->table_element);

            setEventMessages($langs->trans('RemoveQuestionFromGroup') . ' ' . $question->ref, array());
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id  . ($sheetId ? '&sheet_id=' . $sheetId : ''));
    }

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// TODO remove in the future if PR dolibarr accepted (missing call to setEventMessages on update when using validateField())
	setEventMessages($object->error, $object->errors, 'errors');

    // Actions confirm_lock, confirm_archive
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/object_workflow_actions.tpl.php';
}

/*
 * View
 */

$title    = $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_DigiQuali';

saturne_header(0,'', $title, $help_url);
if ($sheetId > 0) {
    $sheet->fetch($sheetId);
    print $sheet->getQuestionAndGroupsTree($object->element, $object->id);
}

print '<div id="cardContent" '. ($sheetId > 0 ? 'class="margin-for-tree"' : '') .'>';

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewQuestionGroup'), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="createQuestionGroupForm" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="sheet_id" value="' . $sheetId . '">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate question_group-table">'."\n";

	// Label -- Libellé
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.GETPOST('label').'">';
	print '</td></tr>';

	// Description -- Description
	print '<tr><td class=""><label class="" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', GETPOST('description'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Categories
	if (!empty($conf->categorie->enabled)) {
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$categoryArborescence = $form->select_all_categories('questiongroup', '', 'parent', 64, 0, 1);
		print img_picto('', 'category', 'class="pictofixedwidth"').$form::multiselectarray('categories', $categoryArborescence, GETPOST('categories', 'array'), '', 0, 'minwidth100imp maxwidth500 widthcentpercentminusxx');
        print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/categories/index.php?type=questiongroup&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddCategories') . '"></span></a>';
		print "</td></tr>";
	}

	// Success score
	print '<tr><td class="fieldrequired">'.$langs->trans("SuccessScoreWithUnit").'</td><td>';
	print '<input class="flat" type="number" step="0.01" min="0" max="100" size="3" name="success_rate" id="success_rate" value="'.GETPOST('success_rate').'">';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button wpeo-button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print ' &nbsp; <input type="button" id ="actionButtonCancelCreate" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

	dol_set_focus('input[name="label"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("ModifyQuestionGroup"), '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit question_group-table">'."\n";

	// Ref -- Ref
	print '<tr><td class="fieldrequired">' . $langs->trans("Ref") . '</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="fieldrequired minwidth400">'.$langs->trans("Label").'</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="'.$object->label.'">';
	print '</td></tr>';

	//Description -- Description
	print '<tr><td><label class="" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Success score
	print '<tr><td class="fieldrequired">'.$langs->trans("SuccessScoreWithUnit").'</td><td>';
	print '<input class="flat" type="number" step="0.01" min="0" max="100" size="3" name="success_rate" id="success_rate" value="'.$object->success_rate.'">';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save wpeo-button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);
	saturne_banner_tab($object);

	$formconfirm = '';

	// Lock confirmation
	if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Clone confirmation
	if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		// Define confirmation messages
		$formquestion_groupclone = [
			['type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans('NewLabelForClone', $langs->transnoentities('The' . ucfirst($object->element))), 'value' => $langs->trans('CopyOf') . ' ' . $object->ref, 'size' => 24],
		];
		$formconfirm .= $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref), 'confirm_clone', $formquestion_groupclone, 'yes', 'actionButtonClone', 350, 600);
	}

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('Delete') . ' ' . $langs->transnoentities('The'  . ucfirst($object->element)), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = ['formConfirm' => $formconfirm];
	$reshook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	//Description -- Description
	print '<tr><td class="titlefield">';
	print $langs->trans("Description");
	print '</td>';
	print '<td>';
    print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, 'questiongroup', 1);
		print "</td></tr>";
	}

	// Success score
	print '<tr><td class="titlefield">';
	print $langs->trans("SuccessScore");
	print '</td>';
	print '<td>';
	print $object->success_rate . ' %';
	print '</td></tr>';


	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	// Buttons for actions
	if ($action != 'presend') {
		print '<div class="tabsAction">';
		$parameters = [];
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook) && $permissiontoadd) {
			// Modify
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id  . ($sheetId ? '&sheet_id=' . $sheetId : '') . '&action=edit' . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</span>';
			}

			// Lock
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<span class="butAction" id="actionButtonLock"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', $langs->transnoentities('The' . ucfirst($object->element)))) . '"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
			}

			// Archive
			if ($object->status == $object::STATUS_LOCKED) {
				print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id  . ($sheetId ? '&sheet_id=' . $sheetId : '') . '&action=confirm_archive&token=' . newToken() . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</span>';
			}

			// Clone
			print '<span class="butAction" id="actionButtonClone"><i class="fas fa-clone"></i> ' . $langs->trans('Clone') . '</span>';

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction('<i class="fas fa-trash"></i> ' . $langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id  . ($sheetId ? '&sheet_id=' . $sheetId : '') . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>';
	}

    // QUESTIONS LINES
    print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
    print load_fiche_titre($langs->trans("QuestionList"), '', '', 0, 'questionList');
    print '<table id="tablelines" class="centpercent noborder noshadow">';
    global $forceall, $forcetoshowtitlelines;

    if (empty($forceall)) $forceall = 0;

    // Define colspan for the button 'Add'
    $colspan = 3;
    ?>
    <script>
        $(document).ready(function(){
            $(".move-line").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
            $(".move-line").css("background-repeat","no-repeat");
            $(".move-line").css("background-position","center center");
        });
    </script>
    <?php
    // Lines
    print '<thead><tr class="liste_titre">';
    print '<td>' . $langs->trans('Ref') . '</td>';
    print '<td>' . $langs->trans('Description') . '</td>';
    print '<td class="right" colspan="2">'. $langs->trans('Action') .'</td>';
    print '<td class="center"></td>';
    print '</tr></thead>';

    $questionsLinked = $object->fetchQuestionsOrderedByPosition();
    $alreadyAdded = [];

    if (is_array($questionsLinked) && !empty($questionsLinked)) {
        foreach ($questionsLinked as $questionLinked) {
                $alreadyAdded[] = $questionLinked->id;
                print '<tr id="' . $questionLinked->id . '" class="line-row oddeven">';
                print '<td>';
                print img_picto('', $questionLinked->picto, 'class="pictofixedwidth"') . $questionLinked->ref;
                print '</td>';

                print '<td>';
                print $questionLinked->description;
                print '</td>';

                print '<td class="center">';
                if ($object->status < Question::STATUS_LOCKED) {
                    print '<td class="move-line ui-sortable-handle">';
                    print '</td>';
                    print '<td class="center">';
                    $url = $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=removeQuestion&questionId=' . $questionLinked->id . '&token=' . newToken();
                    print '<a class="reposition delete-question" id="" href="' . $url . '">' . img_picto($langs->trans('Delete'), 'delete') . '</a>';
                } else {
                    print '</td>';
                    print '<td>';
                }

                print '</td>';
                print '</tr>';
            }
    }

    if ($object->status < QuestionGroup::STATUS_LOCKED) {
        print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="add_question">';
        print '<input type="hidden" name="id" value="' . $id . '">';
        print '<input type="hidden" name="sheet_id" value="' . $sheetId . '">';

        print '<tr>';

        print '<td>-</td>';

        print '<td>';
        if (!empty($alreadyAdded)) {
            $filter = ['customsql' => 't.rowid NOT IN (' . implode(',', $alreadyAdded) . ')'];
        } else {
            $filter = [];
        }
        $questionList = saturne_fetch_all_object_type('Question', '', '', 0, 0, $filter);
        $questionArray = [];
        if (is_array($questionList) && !empty($questionList)) {
            foreach($questionList as $questionId => $questionSingle) {
                $questionArray[$questionId] = img_picto('', $questionSingle->picto) . ' ' . $questionSingle->ref . ' - ' . $questionSingle->label;
            }
        }

        print $form->multiselectArray('questionId', $questionArray, GETPOST('questionId'), 0, 0, '', 0, 450, '', '', $langs->transnoentities('SelectMultipleQuestion'));

        print '<td class="center">';
        print '<input type="submit" class="button wpeo-button" value="' . $langs->trans("Add") . '">';
        print '</td>';
        print '<td>';
        print '</td>';
        print '</tr>';

        print '</table>';
        print '</form>';
        print '</div>';
    }
	print dol_get_fiche_end();

	print '<div class="fichecenter"><div class="fichehalfright">';

	$maxEvent = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiQuali&object_type=' . $object->element);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element . '@' . $object->module, '', 1, '', $MAXEVENT, '', $morehtmlcenter);

	print '</div></div>';
}
print '</div>';

// End of page
llxFooter();
$db->close();
