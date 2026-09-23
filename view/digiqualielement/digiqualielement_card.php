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
 * \brief   Page to create/edit/view digiquali element
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/digiqualielement.class.php';
require_once __DIR__ . '/../../class/digiqualistandard.class.php';
require_once __DIR__ . '/../../lib/digiquali_digiqualielement.lib.php';

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
$elementType         = GETPOSTINT('element_type');
$fkStandard          = GETPOSTISSET('fk_standard') ? GETPOSTINT('fk_standard') : getDolGlobalInt('DIGIQUALI_ACTIVE_STANDARD');

// Initialize technical objects
$object            = new DigiQualiElement($db);
$digiQualiStandard = new DigiQualiStandard($db);
$extrafields       = new ExtraFields($db);

// Initialize view objects
$form = new Form($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks([$object->element . 'card', $object->module . 'view', 'globalcard']); // Note that conf->hooks_modules contains array

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
    $backurlforlist = dol_buildpath($object->module . '/view/' . $digiQualiStandard->element . '/' . $digiQualiStandard->element . '_card.php?id=' . $fkStandard, 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_view.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
            }
        }
    }

    if ($action == 'update' && $permissiontoadd) {
        $fkParent = GETPOSTINT('fk_parent');
        if ($fkParent == -1) {
            $_POST['fk_parent'] = 0;
        }
    }

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    require_once DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

//	if ($action == 'add' && $permissiontoadd) { ?>
<!--		<script>-->
<!--			jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );-->
<!--			//console.log( this );-->
<!--			let id = $(this).attr('value');-->
<!--			jQuery( this ).closest( '.unit' ).addClass( 'active' );-->
<!---->
<!--			var unitActive = jQuery( this ).closest( '.unit.active' ).attr('id');-->
<!--			localStorage.setItem('unitactive', unitActive );-->
<!---->
<!--			jQuery( this ).closest( '.unit' ).attr( 'value', id );-->
<!--		</script>-->
<!--		--><?php
//	}

//    $object->element = $object->element_type;
//
//	// Actions builddoc, forcebuilddoc, remove_file
//	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';
//
//	// Action to generate pdf from odt file
//    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';
}

/*
 * View
 */

// Process infos and activities are merged on digiqualielement_view.php, this page only handles create/edit forms
if ($action != 'edit' && $action != 'create') {
    header('Location: ' . dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_view.php', 1) . '?id=' . $object->id);
    exit;
}

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

saturne_header(1,'', $title, $helpUrl, '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist sidebar-secondary-opened');

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
    }

    print load_fiche_titre($langs->trans('NewObject', dol_strtolower($langs->transnoentities(dol_ucfirst($object->element)))), '', $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="fk_standard" value="' . $fkStandard . '">';
    print '<input type="hidden" name="fk_element" value="' . GETPOSTINT('fk_element') . '">';
    print '<input type="hidden" name="element_type" value="' . $elementType . '">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldcreate">';

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
    print load_fiche_titre($langs->trans('ModifyObject', dol_strtolower($langs->transnoentities(dol_ucfirst($object->element)))), '', $object->picto);

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

    $object->fields['fk_element']['visible'] = 1;

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

//    if ($id != $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) {
//        $children         = $object->fetchDigiriskElementFlat($id);
//        $childrenElements = [];
//        if (is_array($children) && !empty($children)) {
//            foreach ($children as $key => $value) {
//                $childrenElements[$key] .= $key;
//            }
//        }
//        print '<tr><td>' . $langs->trans("ParentElement") . '</td><td>';
//        print $object->selectDigiriskElementList($object->fk_parent, 'fk_parent', ['customsql' => 'element_type="groupment" AND t.rowid NOT IN (' . rtrim(implode(',', $deletedElements) . ',' . implode(',', $childrenElements), ',') . ')'], 0, 0, [], 0, 0, 'minwidth100 maxwidth300', GETPOST('id'));
//        print '</td></tr>';
//    }

    // Other attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// End of page
llxFooter();
$db->close();
