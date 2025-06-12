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
 * \file    view/digiqualielement/digiqualielement_view.php
 * \ingroup digiquali
 * \brief   Page to view digiquali element
 */

if (!defined('NOSCANPOSTFORINJECTION')) {
    define('NOSCANPOSTFORINJECTION', '1'); // Do not check anti CSRF attack test
}

// Load DigiQuali environment
if (!file_exists('../../digiquali.inc.php')) {
    die('Include of digiquali main fails');
}
require_once __DIR__ . '/../../digiquali.inc.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/digiqualielement.class.php';
require_once __DIR__ . '/../../class/digiqualistandard.class.php';
require_once __DIR__ . '/../../class/activity.class.php';
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
$elementType         = GETPOST('element_type', 'alpha');
$fkParent            = GETPOSTISSET('fk_parent') ? GETPOSTINT('fk_parent') : getDolGlobalInt('DIGIQUALI_ACTIVE_STANDARD');
$fkStandard          = GETPOSTISSET('fk_standard') ? GETPOSTINT('fk_standard') : getDolGlobalInt('DIGIQUALI_ACTIVE_STANDARD');

// Initialize technical objects
$object            = new DigiQualiElement($db);
$digiQualiStandard = new DigiQualiStandard($db);
$activity          = new Activity($db);
$extrafields       = new ExtraFields($db);

// Initialize view objects
$form = new Form($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks([$object->element . 'card', $object->module. 'view', 'globalcard']); // Note that conf->hooks_modules contains array

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
                $backtopage = dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
            }
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

    require_once __DIR__ . '/../../../saturne/core/tpl/actions/component_actions.tpl.php';
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

if ( ! $object->id) {
	$object->ref    = $conf->global->MAIN_INFO_SOCIETE_NOM;
	$object->label  = $langs->trans('Society');
	$object->entity = $conf->entity;
	unset($object->fields['element_type']);
}

// Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create'))) {
    saturne_get_fiche_head($object, 'card', $title);
    saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', '', true, []);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<table class="border centpercent tableforfield">';

    print '</table>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    $activity->fetch(2);

    //$activity->source       = 'Processus direction';
    //$activity->source_from  = 'La direction';
//    $activity->input_data   = 'Besoin de lentreprise en terme de fonction.<br>Système de management SST';
//    $activity->output_data  = 'Rôles et responsabilités distribués<br>Constitution de la CSSCT';
//    $activity->score        = 50;
//    $activity->target_score = 70;

    require_once __DIR__ . '/../../../saturne/core/tpl/modal/modal_badge_component.tpl.php';

    print '<div class="wpeo-gridlayout grid-2">';

    echo saturne_get_badge_component_html();

    foreach ($activity->fields as $key => $val) {
        if (!isset($val['viewmode']) && $val['viewmode'] != 'badge') {
            continue;
        }
        echo saturne_get_badge_component_html([
            'id'        => 'badge_component_' . $key . '_' . $activity->id,
            'iconClass' => 'fas fa-user',
            'title'     => $val['label'],
            'details'   => [$activity->{$key} ?? $langs->transnoentities('NotKnown')],
            'actions'   => [
                [
                    'iconClass' => 'fas fa-pen',
                    'label'     => 'Edit',
                    'className' => 'modal-open',
                    'hiddenInputs' => [
                        [
                            'class' => 'modal-options',
                            'data'  => [
                                'modal-to-open' => 'badge_component',
                                'from-id'       => $activity->id,
                                'from-type'     => $activity->element,
                                'from-field'    => $key
                            ]
                        ]
                    ]
                ]
            ],
        ]);
    }
    print '</div>';
}

// End of page
llxFooter();
$db->close();
