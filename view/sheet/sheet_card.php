<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 *   	\file       view/sheet/sheet_card.php
 *		\ingroup    digiquali
 *		\brief      Page to create/edit/view sheet
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
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/questiongroup.class.php';
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(["other", "product", 'bills', 'orders']);

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'sheetcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
// Technical objets
$object        = new Sheet($db);
$question      = new Question($db);
$questionGroup = new QuestionGroup($db);
$extrafields   = new ExtraFields($db);
$category      = new Categorie($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('sheetcard', 'globalcard')); // Note that conf->hooks_modules contains array

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

$permissiontoread   = $user->rights->digiquali->sheet->read;
$permissiontoadd    = $user->rights->digiquali->sheet->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->digiquali->sheet->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

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

	$backurlforlist = dol_buildpath('/digiquali/view/sheet/sheet_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiquali/view/sheet/sheet_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}

    if ($action == 'addQuestionGroup') {
        $questionGroupId = GETPOST('questionGroupId');
        $targetQuestionGroupId = GETPOST('targetGroupId');
        if ($questionGroupId > 0) {
			$questionGroup->fetch($questionGroupId);

			// Linked new group to its parent (it could be the model or another group of the model)
			if ($targetQuestionGroupId == 0) {
				$targetLinkedId = $id;
				$targetElementType = $object->element;
			} else {
				$targetLinkedId = $targetQuestionGroupId;
				$targetElementType = 'questiongroup';
			}
			$questionGroup->add_object_linked('digiquali_' . $targetElementType, $targetLinkedId);

            if ($targetQuestionGroupId > 0) {
				$object->updateQuestionsAndGroupsPosition([], [], true, $targetQuestionGroupId, 'digiquali_questiongroup');
			} else {
				$object->updateQuestionsAndGroupsPosition([], [], true);
			}

            $object->call_trigger('SHEET_ADDQUESTIONGROUP', $user);
            setEventMessages($langs->trans('AddQuestionGroupLink', 1) . ' ' . $questionGroup->ref, []);
            header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
            exit;
        }
    }

	if ($action == 'addQuestion' && $permissiontoadd) {
		$questionId = GETPOST('questionId');
		if ($questionId > 0) {
			$question->fetch($questionId);

			// Add question to target group or to sheet
			$targetQuestionGroupId = GETPOST('targetGroupId');
			if ($targetQuestionGroupId == 0) {
				$targetLinkedId = $id;
				$targetElementType = $object->element;
			} else {
				$targetLinkedId = $targetQuestionGroupId;
				$targetElementType = 'questiongroup';
			}

			$question->add_object_linked('digiquali_' . $targetElementType, $targetLinkedId);

			if ($targetQuestionGroupId > 0) {
				$object->updateQuestionsAndGroupsPosition([], [], true, $targetQuestionGroupId, 'digiquali_questiongroup');
			} else {
				$object->updateQuestionsAndGroupsPosition([], [], true);
			}

            $object->call_trigger('SHEET_ADDQUESTION', $user);
			setEventMessages($langs->trans('AddQuestionLink', 1) . ' ' . $question->ref, []);
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id'));
			exit;
		} else {
			setEventMessages($langs->trans('ErrorNoQuestionSelected'), null, 'errors');
		}

	}

	if ($action == 'unlinkQuestion' && $permissiontoadd) {
		$questionId = GETPOST('questionId');
		$question->fetch($questionId);

		$question->fetchObjectLinked();

		$linkedObjectsIds = $question->linkedObjectsIds;
		if (isset($linkedObjectsIds['digiquali_questiongroup'])) {
			$sourceId = array_shift($linkedObjectsIds['digiquali_questiongroup']);
			$sourceType = 'digiquali_questiongroup';
		}
		if (isset($linkedObjectsIds['digiquali_sheet'])) {
			$sourceId = array_shift($linkedObjectsIds['digiquali_sheet']);
			$sourceType = 'digiquali_sheet';
		}

		$question->deleteObjectLinked($sourceId, $sourceType, $questionId, 'digiquali_question');

		setEventMessages($langs->trans('removeQuestionLink') . ' ' . $question->ref, array());

		header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id') . '&page_y=' . GETPOST('page_y'));
		exit;
	}

    if ($action == 'unlinkQuestionGroup' && $permissiontoadd) {
        $questionGroupId = GETPOST('questionGroupId');
        $questionGroup->fetch($questionGroupId);


		$parentGroupId = $questionGroup->getParentGroupId();
		if ($parentGroupId == 0) {
			$sourceElementType = 'sheet';
		} else {
			$sourceElementType = $questionGroup->element;
		}
		$questionGroup->deleteObjectLinked($parentGroupId, 'digiquali_' . $sourceElementType, $questionGroup->id, 'digiquali_questiongroup');

        setEventMessages($langs->trans('RemoveQuestionGroupLink') . ' ' . $questionGroup->ref, array());

        header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . GETPOST('id') . '&page_y=' . GETPOST('page_y'));
        exit;
    }

	if ($action == 'add' && $permissiontoadd && !$cancel) {
		if (is_array(GETPOST('linked_object')) && !empty(GETPOST('linked_object'))) {
			foreach (GETPOST('linked_object') as $linked_object_type) {
				$showArray[$linked_object_type] = 1;
			}
		} else {
			setEventMessages($langs->trans('NoLinkedObjectSelected'), null, 'errors');
			if (dol_strlen(GETPOST('label')) > 0) {
				header("Location: " . $_SERVER['PHP_SELF'] . '?action=create&label=' . GETPOST('label'));
				exit;
			}
		}
		$object->element_linked = json_encode($showArray);

		if (empty(GETPOST('categories', 'array'))) {
			$category->fetch($conf->global->DIGIQUALI_SHEET_DEFAULT_TAG);
			$defaultCategory[] = $category->id;
			$_POST['categories'] = $defaultCategory;
		}
	}

	if ($action == 'update' && $permissiontoadd) {
		if (is_array(GETPOST('linked_object')) && !empty(GETPOST('linked_object'))) {
			foreach (GETPOST('linked_object') as $linked_object_type) {
				$showArray[$linked_object_type] = 1;
			}
		}
		$object->element_linked = json_encode($showArray);

		if (empty(GETPOST('categories', 'array'))) {
			$category->fetch($conf->global->DIGIQUALI_SHEET_DEFAULT_TAG);
			$defaultCategory[] = $category->id;
			$_POST['categories'] = $defaultCategory;
		} else {
			$object->setCategories(GETPOST('categories', 'array'));
		}
	}

	if ($action == 'moveLine' && $permissiontoadd) {
		$newPositionsArray = json_decode(file_get_contents('php://input'), true);

		$groupId = GETPOST('groupId', 'int');
		$movedItemId = GETPOST('movedItemId', 'alphanohtml');
		$movedItemType = GETPOST('movedItemType', 'alphanohtml');

		if ($movedItemId > 0) {
			if ($movedItemType == 'question') {
				$question = new Question($object->db);
				if ($question->fetch($movedItemId)) {
					$question->element = 'digiquali_question';
					$question->updateObjectLinked($groupId, ($groupId > 0 ? 'digiquali_questiongroup' : 'digiquali_sheet'));
				}
			}
			if ($movedItemType == 'questiongroup') {
				$questionGroup = new QuestionGroup($object->db);
				if ($questionGroup->fetch($movedItemId)) {
					$question->element = 'digiquali_questiongroup';
					$question->updateObjectLinked($groupId, ($groupId > 0 ? 'digiquali_questiongroup' : 'digiquali_sheet'));
				}
			}
		}

		if ($groupId >= 0) {
			$questionPositions = [];
			foreach ($newPositionsArray['question_positions'] as $singleQuestionPosition) {
				$questionPositions[$singleQuestionPosition['position']] = $singleQuestionPosition['id'];
			}
			$questionGroupPositions = [];
			foreach ($newPositionsArray['questiongroup_positions'] as $singleQuestionGroupPosition) {
				$questionGroupPositions[$singleQuestionGroupPosition['position']] = $singleQuestionGroupPosition['id'];
			}
			if ($groupId == 0) {
				$object->updateQuestionsAndGroupsPosition($questionPositions, $questionGroupPositions);
			} else {
				$group = new QuestionGroup($object->db);
				$group->fetch($groupId);
				$group->updateQuestionsPositions($questionPositions);
				$group->updateQuestionGroupsPositions($questionGroupPositions);
			}
		}
	}

	// Action to delete
	if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
		if (!($object->id > 0)) {
			dol_print_error('', 'Error, object must be fetched before being deleted');
			exit;
		}

		if (method_exists($object, 'isErasable') && $object->isErasable() <= 0) {
			$langs->load("errors");
			$object->errors = $langs->trans('ErrorQuestionUsedInSheet',$object->ref);
			$result = 0;
		} else {
			$result = $object->delete($user);
		}

		if ($result > 0) {
			// Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');

			header("Location: ".$backurlforlist);
			exit;
		} else {
			$error++;
			if (!empty($object->errors)) {
				setEventMessages(null, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, null, 'errors');
			}
		}
		$action = '';
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes') {
		if ($object->id > 0) {
			$result = $object->createFromClone($user, $object->id);
			if ($result > 0) {
				header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Action to set status STATUS_LOCKED
	if ($action == 'confirm_lock' && $permissiontoadd) {
		$object->fetch($id);
		if ( ! $error) {
			$result = $object->setLocked($user, false);
			if ($result > 0) {
                // Need update question status because import allow STATUS_VALIDATED prohibed single add
                if ($object->import_key > 0) {
                    $object->fetchObjectLinked($id, 'digiquali_' . $object->element);
                    if (is_array($object->linkedObjects['digiquali_question']) && !empty($object->linkedObjects['digiquali_question'])) {
                        foreach ($object->linkedObjects['digiquali_question'] as $question) {
                            if ($question->status == Question::STATUS_VALIDATED) {
                                $question->setLocked($user, false);
                            }
                        }
                    }
                }
				// Set locked OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Set locked KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

    // Action to set status STATUS_ARCHIVED.
    if ($action == 'confirm_archive' && $permissiontoadd) {
        $object->fetch($id);
        if (!$error) {
            $result = $object->setArchived($user);
            if ($result > 0) {
                // Set Archived OK.
                $urltogo = str_replace('__ID__', $result, $backtopage);
                $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
                header('Location: ' . $urltogo);
                exit;
            } elseif (!empty($object->errors)) { // Set Archived KO.
                setEventMessages('', $object->errors, 'errors');
            } else {
                setEventMessages($object->error, [], 'errors');
            }
        }
    }

    if ($action == 'set_mandatory' && $permissiontoadd) {
        $questionId = GETPOST('questionId', 'int');
		$questionRef = GETPOST('questionRef', 'alpha');

        if ($questionId > 0) {
            $mandatoryArray = dol_strlen($object->mandatory_questions) > 0 ? json_decode($object->mandatory_questions, true) : [];

            if (in_array($questionId, $mandatoryArray)) {
                $mandatoryArray = array_diff($mandatoryArray, [$questionId]);
				$successMessage = $langs->trans('QuestionUnMandatorized', $questionRef);
			} else {
				$mandatoryArray[] = $questionId;
				$successMessage = $langs->trans('QuestionMandatorized', $questionRef);
			}

            $object->mandatory_questions = json_encode($mandatoryArray);
            $result = $object->update($user);

			if ($result > 0) {
				setEventMessage($successMessage);
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation.
				header('Location: ' . $urltogo . '#questionList');
			} else {
                setEventMessages('', $object->errors, 'errors');
            }
        }
    }
}

/*
 * View
 */

$title    = $langs->trans('Sheet');
$help_url = 'FR:Module_DigiQuali';
$moreJS   = ['/saturne/js/includes/hammer.min.js'];

$objectsMetadata = saturne_get_objects_metadata();

saturne_header(1,'', $title, $help_url, '', 0, 0, $moreJS);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewSheet'), '', 'object_' . $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate sheet-table">'."\n";

	//Label -- Libellé
	print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . GETPOST('label') . '">';
	print '</td></tr>';

	// Description -- Description
	print '<tr><td class=""><label class="" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', GETPOST('description'), '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

    // Type -- Type
    print '<tr><td class="fieldrequired">' . $langs->trans('Type') . '</td><td>';
    print $form::selectarray('type', $object->fields['type']['arrayofkeyval'], GETPOST('type'));
    print '</td></tr>';

    //FK Element
    $nbLinkableElements = 0;
    foreach ($objectsMetadata as $objectType => $objectMetadata) {
        if ($objectMetadata['conf'] == 0) {
            continue;
        }

        print '<tr><td class="">' . img_picto('', $objectMetadata['picto'], 'class="paddingrightonly"') . $langs->trans($objectMetadata['langs']) . '</td><td>';
        $linkedObjects = empty(GETPOST('linked_object')) ? [] : GETPOST('linked_object');
        if ($conf->global->DIGIQUALI_SHEET_UNIQUE_LINKED_ELEMENT) {
            print '<input type="radio" id="show_' . $objectType . '" name="linked_object[]" value="' . $objectType . '"' . (in_array($objectType, $linkedObjects) ? 'checked' : '') .'>';
        } else {
            print '<input type="checkbox" id="show_' . $objectType . '" name="linked_object[]" value="' . $objectType . '"' . (in_array($objectType, $linkedObjects) ? 'checked' : '') .'>';
        }
        print '</td></tr>';
        $nbLinkableElements++;
    }

    if ($nbLinkableElements == 0) {
        $noticeMessage = '<a href="' . dol_buildpath('custom/digiquali/admin/sheet.php', 1) . '">' . $langs->transnoentities('MissingConfigElementTypeMessage') . '</a>';
        print saturne_show_notice($langs->transnoentities('MissingConfigElementTypeTitle'), $noticeMessage, 'error', 'notice-infos', true);
    }

	if (!empty($conf->categorie->enabled)) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('sheet', '', 'parent', 64, 0, 1);
		print img_picto('', 'category', 'class="pictofixedwidth"').$form::multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'minwidth100imp maxwidth500 widthcentpercentminusxx');
        print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/categories/index.php?type=sheet&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddCategories') . '"></span></a>';
		print "</td></tr>";
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button wpeo-button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans('ModifySheet'), '', 'object_' . $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="conf_unique_linked_element" value="'.$conf->global->DIGIQUALI_SHEET_UNIQUE_LINKED_ELEMENT.'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit sheet-table">'."\n";

	//Ref -- Ref
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Ref") . '</td><td>';
	print $object->ref;
	print '</td></tr>';

	//Label -- Libellé
	print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td>';
	print '<input class="flat" type="text" size="36" name="label" id="label" value="' . $object->label . '">';
	print '</td></tr>';

	// Description -- Description
	print '<tr><td class=""><label class="" for="description">' . $langs->trans("Description") . '</label></td><td>';
	$doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

    // Type -- Type
    print '<tr><td class="fieldrequired">' . $langs->trans('Type') . '</td><td>';
    print $form::selectarray('type', $object->fields['type']['arrayofkeyval'], $object->type);
    print '</td></tr>';

    //FK Element
	$elementLinked = json_decode($object->element_linked);

	foreach ($objectsMetadata as $key => $element) {
		if (!empty($element['conf'])) {
			print '<tr><td class="">' . img_picto('', $element['picto'], 'class="paddingrightonly"') . $langs->trans($element['langs']) . '</td><td>';
			if ($conf->global->DIGIQUALI_SHEET_UNIQUE_LINKED_ELEMENT) {
				print '<input type="radio" id="show_' . $key . '" name="linked_object[]" value="'.$key.'"'.(($elementLinked->$key > 0) ? 'checked=checked' : '').'>';
			} else {
				print '<input type="checkbox" id="show_' . $key . '" name="linked_object[]" value="'.$key.'"'.(($elementLinked->$key > 0) ? 'checked=checked' : '').'>';
			}
			print '</td></tr>';
		}
	}

	// Tags-Categories
	if ($conf->categorie->enabled) {
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories('sheet', '', 'parent', 64, 0, 1);
		$c = new Categorie($db);
		$cats = $c->containing($object->id, 'sheet');
		$arrayselected = array();
		if (is_array($cats)) {
			foreach ($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
		}
		print img_picto('', 'category', 'class="pictofixedwidth"').$form::multiselectarray('categories', $cate_arbo, (GETPOSTISSET('categories') ? GETPOST('categories', 'array') : $arrayselected), '', 0, 'minwidth100imp maxwidth500 widthcentpercentminusxx');
        print '<a class="butActionNew" href="' . DOL_URL_ROOT . '/categories/index.php?type=sheet&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans('AddCategories') . '"></span></a>';
		print "</td></tr>";
	}

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
	if ($object->displayTree()) {
		print $object->getQuestionAndGroupsTree();
	}
    print '<div id="cardContent" class="' . ($object->displayTree() ? 'margin-for-tree' : '') . '">';
	$res = $object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);
	saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

	$formconfirm = '';

	// SetLocked confirmation
	if (($action == 'lock' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LockObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmLockObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_lock', '', 'yes', 'actionButtonLock', 350, 600);
	}

	// Clone confirmation
	if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile))) || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {
		$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmCloneObject', $langs->transnoentities('The' . ucfirst($object->element)), $object->ref), 'confirm_clone', '', 'yes', 'actionButtonClone', 350, 600);
	}

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('Delete') . ' ' . $langs->transnoentities('The' . ucfirst($object->element)), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = ['formConfirm' => $formconfirm];
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

    if ($conf->browser->layout == 'phone') {
        $onPhone = 1;
    } else {
        $onPhone = 0;
    }

	// Object card
	// ------------------------------------------------------------

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	unset($object->fields['label']); // Hide field already shown in banner

    print '<tr class="field_success_rate"><td class="titlefield fieldname_success_rate">';
    print $form->editfieldkey('SuccessScore', 'success_rate', $object->success_rate, $object, $permissiontoadd && $object->status < Sheet::STATUS_LOCKED, 'string', '', 0, 0,'id', $langs->trans('PercentageValue'));
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

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, 'sheet', 1);
		print "</td></tr>";
	}

	$elementLinked = json_decode($object->element_linked);

	//FK Element
	foreach ($objectsMetadata as $key => $element) {
		if ($elementLinked->$key > 0) {
			if (!empty($element['conf'])) {
				print '<tr><td class="">' . img_picto('', $element['picto'], 'class="paddingrightonly"') . $langs->trans($element['langs']) . '</td><td>';
				print '<input type="radio" id="show_' . $key . '" name="show_' . $key . '" checked disabled>';
				print '</td></tr>';
			}
		}
	}

    print '<tr class="linked-medias photo question-table"><td class=""><label for="photos">' . $langs->trans('Photo') . '</label></td><td class="linked-medias-list">';
    $pathPhotos = $conf->digiquali->multidir_output[$conf->entity] . '/sheet/'. $object->ref . '/photos/';
    $fileArray  = dol_dir_list($pathPhotos, 'files'); ?>
    <span class="add-medias" <?php echo ($object->status < Sheet::STATUS_LOCKED) ? '' : 'style="display:none"' ?>>
        <input hidden multiple class="fast-upload<?php echo getDolGlobalInt('SATURNE_USE_FAST_UPLOAD_IMPROVEMENT') ? '-improvement' : ''; ?>" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">
        <input type="hidden" class="fast-upload-options" data-from-subtype="photo" data-from-subdir="photos"/>
        <label for="fast-upload-photo-default">
            <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?>">
                <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
            </div>
        </label>
        <input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?>"/>
        <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?> 'open-media-gallery add-media modal-open" value="0">
            <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $object->id?>" data-from-type="sheet" data-from-subtype="photo" data-from-subdir="photos"/>
            <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
        </div>
    </span>
    <?php
    print saturne_show_medias_linked('digiquali', $pathPhotos, 'small', 0, 0, 0, 0, $onPhone ? 40 : 50, $onPhone ? 40 : 50, 0, 0, 0, 'sheet/'. $object->ref . '/photos/', $object, 'photo', $object->status < Sheet::STATUS_LOCKED, $permissiontodelete && $object->status < Sheet::STATUS_LOCKED);
    print '</td></tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print '<div id="dialog-moved-error" title="' . $langs->trans('Error') . '">';
	print '<p>';
	print $langs->trans("LineMovedOutsideParentError");
	print '</p>';
	print '</div>';

    $questionsAndGroups = $object->fetchQuestionsAndGroups();



	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">';
		$parameters = [];
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook) && $permissiontoadd) {
			// Create object depending on sheet type
			if ($object->status == $object::STATUS_LOCKED) {
				print '<a class="butAction" href="' . dol_buildpath('/custom/digiquali/view/' . $object->type . '/' . $object->type . '_card.php?action=create&fk_sheet=' . $object->id, 1) . '"><i class="fas fa-plus-circle"></i> ' . $langs->trans('New' . ucfirst($object->type)) . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLocked', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-plus-circle"></i> ' . $langs->trans('New' . ucfirst($object->type)) . '</span>';
			}

			// Modify
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit' . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</a>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-edit"></i> ' . $langs->trans('Modify') . '</span>';
			}

			// Lock
			if ($object->status == $object::STATUS_VALIDATED) {
				print '<span class="butAction" id="actionButtonLock"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
			} else {
				print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeValidated', $langs->transnoentities('The' . ucfirst($object->element)))) . '"><i class="fas fa-lock"></i> ' . $langs->trans('Lock') . '</span>';
			}

			// Clone
			print '<span class="butAction" id="actionButtonClone"><i class="fas fa-clone"></i> ' . $langs->trans('Clone') . '</span>';

            // Archive
            if ($object->status == $object::STATUS_LOCKED) {
                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_archive&token=' . newToken() . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeLockedToArchive', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '"><i class="fas fa-archive"></i> ' . $langs->trans('Archive') . '</span>';
            }

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction('<i class="fas fa-trash"></i> ' . $langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete', '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>';
	}

    if (is_array($questionsAndGroups) && !empty($questionsAndGroups)) {
        $questionCounter = count($questionsAndGroups);
    } else {
        $questionCounter = 0;
    }

// QUESTIONS LINES
    print '<div class="div-table-responsive-no-min">';
    print load_fiche_titre($langs->trans("LinkedQuestionsList", $questionCounter), '', '', 0, 'questionList');
    print '<table id="tablelines" class="centpercent noborder noshadow">'; ?>
    <script>
        $(document).ready(function(){
            $(".sheet-move-line").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
            $(".sheet-move-line").css("background-repeat","no-repeat");
            $(".sheet-move-line").css("background-position","center center");
        });
    </script>
    <?php
// Lines
    print '<thead><tr class="liste_titre">';
    print '<td class="maxwidth300 widthcentpercentminusx">' . $langs->trans('Ref') . '</td>';
    print '<td>' . $langs->trans('Label') . '</td>';
    print '<td>' . $langs->trans('Description') . '</td>';
    print '<td>' . $langs->trans('QuestionType') . '</td>';
    print '<td class="center">' . $langs->trans('Mandatory') . '</td>';
    print '<td class="center">' . $langs->trans('PhotoOk') . '</td>';
    print '<td class="center">' . $langs->trans('PhotoKo') . '</td>';
    print '<td class="center">' . $langs->trans('Status') . '</td>';
    print '<td class="center">' . $langs->trans('Action') . '</td>';
    print '<td class="center"></td>';
    print '</tr></thead>';
    print '<tbody>';

    $object->displayGroupsAndQuestions($questionsAndGroups);

	$isAddFormsVisible = true;
	$groupId = 0; // root of the model
	include DOL_DOCUMENT_ROOT . '/custom/digiquali/view/sheet/sheet_addforms.tpl.php';

	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';

	$maxEvent = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiQuali&object_type=' . $object->element);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $maxEvent, '', $morehtmlcenter);

	print '</div>';
	print dol_get_fiche_end();
}
// End of page
llxFooter();
$db->close();
