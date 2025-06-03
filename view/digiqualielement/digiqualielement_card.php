<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/digiqualielement/digiqualielement_card.php
 * \ingroup digiquali
 * \brief   Page to create/edit/view digiqualielement
 */

// Load DigiQuali environment
if (!file_exists('../../digiquali.inc.php')) {
    die('Include of digiquali main fails');
}
require_once __DIR__ . '/../../digiquali.inc.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/digiqualielement.class.php';
require_once __DIR__ . '/../../class/digiqualistandard.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id                  = GETPOSTINT('id');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$subaction           = GETPOST('subaction', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$element_type        = GETPOST('element_type', 'alpha');
$fkParent            = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object            = new DigiQualiElement($db);
$digiQualiStandard = new DigiQualiStandard($db);
$extrafields       = new ExtraFields($db);

// Initialize view objects
$form = new Form($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks([$object->element . 'card', $object->element . 'view', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';

// Permissions
//$permissiontoread   = $user->hasRight($object->module, $object->element, 'read');
//$permissiontoadd    = $user->hasRight($object->module, $object->element, 'write');
//$permissiontodelete = $user->hasRight($object->module, $object->element, 'delete');
$permissiontoread   = 1;
$permissiontoadd    = 1;
$permissiontodelete = 1;

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=1', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage                                                                              = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
		}
	}

    if ($action == 'add' && $permissiontoadd) {
        echo '<pre>'; print_r( $_POST ); echo '</pre>'; exit;
    }


	// Action to add record
	include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

	if ($action == 'add' && $permissiontoadd) { ?>
		<script>
			jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
			//console.log( this );
			let id = $(this).attr('value');
			jQuery( this ).closest( '.unit' ).addClass( 'active' );

			var unitActive = jQuery( this ).closest( '.unit.active' ).attr('id');
			localStorage.setItem('unitactive', unitActive );

			jQuery( this ).closest( '.unit' ).attr( 'value', id );
		</script>
		<?php
	}

	if ($action == 'view' && $permissiontoadd) {
		header('Location: ' . $backtopage);
	}

    $object->element = $object->element_type;

	// Actions builddoc, forcebuilddoc, remove_file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

	// Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    $object->element = 'digiriskelement';

	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes") {
		$object->fetch($id);
		$result = $object->delete($user);

		if ($result > 0) {
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header('Location: ' . $backurlforlist);
			exit;
		} else {
			dol_syslog($object->error, LOG_DEBUG);
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

/*
 * View
 */

if ( $object->element_type == 'groupment' ) {
    $title         = $langs->trans("Groupment");
    $titleCreate   = $langs->trans("NewGroupment");
    $titleEdit     = $langs->trans("ModifyGroupment");
} elseif ( $object->element_type == 'workunit' ) {
    $title         = $langs->trans("WorkUnit");
    $titleCreate   = $langs->trans("NewWorkUnit");
    $titleEdit     = $langs->trans("ModifyWorkUnit");
} else {
    $element_type = GETPOST('element_type', 'alpha');
    if ( $element_type == 'groupment' ) {
        $title = $langs->trans("NewGroupment");
    } else {
        $title = $langs->trans("NewWorkUnit");
    }
}

$helpUrl = 'FR:Module_DigiQuali';

saturne_header(1,'', $title, $helpUrl, '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist');

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
    }

    print load_fiche_titre($langs->trans('NewObject', dol_strtolower($langs->transnoentities(dol_ucfirst($object->element)))), '', $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

    if (empty($fkParent)) {
        $fkParent                            = getDolGlobalInt(dol_strtoupper($object->module) . '_ACTIVE_STANDARD');
        $object->fields['fk_parent']['type'] = 'integer:SaturneStandard:saturne/class/saturnestandard.class.php';
        $_POST['fk_parent']                  = $fkParent;
    } else {
        $object->fields['fk_parent']['type'] = 'integer:SaturneElement:saturne/class/saturneelement.class.php';
    }

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Create');

    print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($titleEdit, '', $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">';

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	print '<tr><td>';
	print $langs->trans("ShowInSelectOnPublicTicketInterface");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="show_in_selector" name="show_in_selector"' . (($object->show_in_selector == 0) ?  '' : ' checked=""') . '"> ';
	print '</td></tr>';

    if ($id != $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) {
        $children         = $object->fetchDigiriskElementFlat($id);
        $childrenElements = [];
        if (is_array($children) && !empty($children)) {
            foreach ($children as $key => $value) {
                $childrenElements[$key] .= $key;
            }
        }
        print '<tr><td>' . $langs->trans("ParentElement") . '</td><td>';
        print $object->selectDigiriskElementList($object->fk_parent, 'fk_parent', ['customsql' => 'element_type="groupment" AND t.rowid NOT IN (' . rtrim(implode(',', $deletedElements) . ',' . implode(',', $childrenElements), ',') . ')'], 0, 0, [], 0, 0, 'minwidth100 maxwidth300', GETPOST('id'));
        print '</td></tr>';
    }

    // Other attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

if ( ! $object->id) {
	$object->ref    = $conf->global->MAIN_INFO_SOCIETE_NOM;
	$object->label  = $langs->trans('Society');
	$object->entity = $conf->entity;
	unset($object->fields['element_type']);
}

// Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create'))) {
	$formconfirm = '';
	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}


	print $formconfirm;
	$res = $object->fetch_optionals();

	saturne_get_fiche_head($object, 'card', $title);

	$trashList = $object->fetchDigiriskElementFlat($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);

	if ($trashList < 0 || empty($trashList)) {
		$trashList = [];
	}

	// Object card
	// ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

	saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefield">';
	print $langs->trans("ShowInSelectOnPublicTicketInterface");
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="show_in_selectorshow_in_selector" name="show_in_selectorshow_in_selector"' . (($object->show_in_selector == 0) ?  '' : ' checked=""') . '" disabled> ';
	print '</td></tr>';

	print '<tr class="linked-medias digirisk-element-photo-'. $object->id .'"><td class=""><label for="photos">' . $langs->trans("Photo") . '</label></td><td class="linked-medias-list" style="display: flex; gap: 10px; height: auto;">';
	print '<span class="add-medias" '. (($object->status != $object::STATUS_VALIDATED) ? "" : "style='display:none'") . '>';
	print '<input hidden multiple class="fast-upload" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">';
	print '<label for="fast-upload-photo-default">';
	print '<div title="'. $langs->trans('AddPhotoFromComputer') .'" class="wpeo-button button-square-50">';
	print '<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>';
	print '</div>';
	print '</label>';
	print '&nbsp';
	print '<input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?>"/>';
	print '<div title="'. $langs->trans('AddPhotoFromMediaGallery') .'" class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">';
	print '<input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="'. $object->id .'" data-from-type="'. $object->element_type .'" data-from-subtype="photo" data-from-subdir="" data-photo-class="digirisk-element-photo-'. $object->id .'"/>';
	print '<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>';
	print '</div>';
	print '</span>';
	print '&nbsp';
    print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref, 'small', 5, 0, 0, 0, 50, 50, 0, 0, 0, $object->element_type . '/'. $object->ref . '/', $object, 'photo', $object->status != $object::STATUS_VALIDATED, $permissiontodelete && $object->status != $object::STATUS_VALIDATED);
	print '</td></tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	if ($object->id > 0) {
		// Buttons for actions
		print '<div class="tabsAction" >' . "\n";
		$parameters = [];
		$reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Modify
			if ($permissiontoadd) {
				print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>' . "\n";
			}

			if ($permissiontodelete && ! array_key_exists($object->id, $trashList) && $object->id != $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) {
				print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token='.newToken().'">' . $langs->trans("Delete") . '</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="' . $langs->trans("CanNotDoThis") . '">' . $langs->trans('Delete') . '</a>';
			}
		}
		print '</div>' . "\n";

		// Document Generation -- Génération des documents
		print '<div class="fichecenter"><div class="fichehalfleft elementDocument">';

		$objref    = dol_sanitizeFileName($object->ref);
		$dirFiles  = $document->element . '/' . $objref;
		$filedir   = $upload_dir . '/' . $dirFiles;
		$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $id;

		if ($document->element == 'groupmentdocument') {
			$modulepart   = 'digiriskdolibarr:GroupmentDocument';
			$defaultmodel = $conf->global->DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL;
			$title        = $langs->trans('GroupmentDocument');
		} elseif ($document->element == 'workunitdocument') {
			$modulepart   = 'digiriskdolibarr:WorkUnitDocument';
			$defaultmodel = $conf->global->DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL;
			$title        = $langs->trans('WorkUnitDocument');
		}

		if ($permissiontoadd || $permissiontoread) {
			$genallowed = 1;
		}

		print saturne_show_documents($modulepart, $dirFiles, $filedir, $urlsource, 1,1, '', 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object);


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlright  = '<a href="' . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_agenda.php', 1) . '?id=' . $object->id . '">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions    = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'digiriskelement@digiriskdolibarr', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}
}

// End of page
llxFooter();
$db->close();
