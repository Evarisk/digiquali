<?php

/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
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

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/digiqualielement.class.php';
require_once __DIR__ . '/../../class/digiqualistandard.class.php';
require_once __DIR__ . '/../../class/activity.class.php';
require_once __DIR__ . '/../../class/riskassessment.class.php';
require_once __DIR__ . '/../../lib/digiquali_digiqualielement.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['companies']);

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
$object             = new DigiQualiElement($db);
$digiQualiStandard  = new DigiQualiStandard($db);
$activity           = new Activity($db);
$riskAssessment     = new Digiquali\RiskAssessment($db);
$riskAssessmentTask = new Task($db);
$extrafields        = new ExtraFields($db);

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
$permissiontoread           = 1;
$permissiontoadd            = 1;
$permissionToAddActivity    = 1;
$permissionToDeleteActivity = 1;
$permissiontodelete         = 1;

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

    require_once __DIR__ . '/../../../saturne/core/tpl/actions/component_actions.tpl.php';
    require_once __DIR__ . '/../../core/tpl/actions/digiquali_activity_actions.tpl.php';
}

/*
 * View
 */

$title   = $langs->trans(dol_ucfirst($object->element));
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(1,'', $title, $helpUrl, '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist sidebar-secondary-opened');

if (!$object->id) {
    $object->ref    = $conf->global->MAIN_INFO_SOCIETE_NOM;
    $object->label  = $langs->trans('Company');
    $object->entity = $conf->entity;
    unset($object->fields['element_type']);
}

// Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create'))) {
    saturne_get_fiche_head($object, $activity->element, $title);
    saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', '', true);

    $formConfirm = '';
    // Confirmation to delete
    if ($action == 'delete') {
        $formConfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->transnoentities('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->transnoentities('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
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

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<table class="border centpercent tableforfield">';

    if ($object->id > 0) {
        print '<tr class="linked-medias digirisk-element-photo-'. $object->id .'"><td class=""><label for="photos">' . $langs->trans("Photo") . '</label></td><td class="linked-medias-list" style="display: flex; gap: 10px; height: auto;">';
        print '<span class="add-medias" '. (($object->status != $object::STATUS_VALIDATED) ? "" : "style='display:none'") . '>';
        print '<input hidden multiple class="fast-upload" id="fast-upload-photo-default" type="file" name="userfile[]" capture="environment" accept="image/*">';
        print '<label for="fast-upload-photo-default">';
        print '<div title="'. $langs->trans('AddPhotoFromComputer') .'" class="wpeo-button button-square-50">';
        print '<i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>';
        print '</div>';
        print '</label>';
        print '&nbsp';
        print '<input type="hidden" class="favorite-photo" id="photo" name="photo" value="' . $object->photo . '"/>';
        print '<div title="'. $langs->trans('AddPhotoFromMediaGallery') .'" class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="0">';
        print '<input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="'. $object->id .'" data-from-type="'. $object->element_type .'" data-from-subtype="photo" data-from-subdir="" data-photo-class="digirisk-element-photo-'. $object->id .'"/>';
        print '<i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>';
        print '</div>';
        print '</span>';
        print '&nbsp';
        print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref, 'small', 5, 0, 0, 0, 50, 50, 0, 0, 0, $object->element_type . '/'. $object->ref . '/', $object, 'photo', $object->status != $object::STATUS_VALIDATED, $permissiontodelete && $object->status != $object::STATUS_VALIDATED);
        print '</td></tr>';
    }

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Other attributes. Fields from hook formObjectOptions and Extrafields
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    // Buttons for actions
    if ($object->id > 0) {
        print '<div class="tabsAction">';
        $parameters = [];
        $resHook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($resHook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($resHook)) {
            // Modify
            $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->transnoentities('Modify');
            if ($permissiontoadd) {
                print '<a class="butAction" href="' . dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?id=' . $object->id . '&action=edit&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id) . '">' . $displayButton . '</a>';
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->transnoentities('NotEnoughPermissions')) . '">' . $displayButton . '</span>';
            }

            // Delete
            $displayButton = $conf->browser->layout != 'classic' ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->transnoentities('Delete');
            if ($object->status != SaturneElement::STATUS_TRASHED) {
                print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete);
            }
        }
        print '</div>';
    }

    require_once __DIR__ . '/../../../saturne/core/tpl/modal/modal_badge_component.tpl.php';
    require_once __DIR__ . '/../../core/tpl/modal/activity/modal_activity_add.tpl.php';

    require_once __DIR__ . '/../../core/tpl/modal/riskassessment/modal_riskassessment_add.tpl.php';
    require_once __DIR__ . '/../../core/tpl/modal/riskassessment/modal_riskassessment_edit.tpl.php';
    require_once __DIR__ . '/../../core/tpl/modal/riskassessment/modal_riskassessment_list.tpl.php';

    require_once __DIR__ . '/../../core/tpl/modal/riskassessment/task/modal_riskassessment_task_add.tpl.php';

    $moreHtmlRight = <<<HTML
    <div class="wpeo-button modal-open">
        <input type="hidden" class="modal-options" data-modal-to-open="activity_create" data-from-id="{$id}" data-from-type="{$object->element}">
        <i class="fas fa-plus button-icon"></i>
    </div>
    HTML;

    $moreTitle    = '';
    $nbActivities = Activity::getNbActivities($object);
    if ($nbActivities > 0) {
        $moreTitle .= '<span class="opacitymedium colorblack marginleftonly">(' . $nbActivities . ')</span>';
    }
    print load_fiche_titre($langs->trans('ProcessusActivities') . $moreTitle, $moreHtmlRight, $activity->picto);

    print '<div class="activity-list-container" id="activity_list_container_' . $id . '">';
    $activities = $activity->fetchAll('', '', 0, 0, ['customsql' => 't.fk_element = ' . $id]);
    foreach ($activities as $activitySingle) {
        $activityInfos = $activitySingle->getActivityInfos();

        print '<div class="activity-container" data-object-id="' . $activitySingle->id . '">';
        print '<div class="activity-container__header">';
        print $activityInfos['ref'];
        print '</div>';

        print '<input type="hidden" id="success_message" value="' . $langs->transnoentities('Updated') . '">';
        print '<div class="activity-container__body wpeo-gridlayout grid-2">';
        foreach ($activitySingle->fields as $key => $val) {
            if (!isset($val['viewmode']) || $val['viewmode'] != 'badge') {
                continue;
            }
            // Score fields show a non-editable percentage sign, rendered via CSS ::after on .badge-percent
            $badgeClassName = (in_array($key, ['score', 'target_score'], true) && isset($activitySingle->{$key})) ? 'badge-percent' : '';
            echo saturne_get_badge_component_html([
                'id'        => 'badge_component_' . $key . '_' . $activitySingle->id,
                'field'     => $key,
                'className' => $badgeClassName,
                'iconClass' => $val['picto'] ?? '',
                'title'     => $val['label'],
                'details'   => [$activitySingle->{$key} ?? $langs->transnoentities('NotKnown')],
//                'actions'   => [
//                    [
//                        'iconClass' => 'fas fa-pen',
//                        'label'     => 'Edit',
//                        'className' => 'modal-open',
//                        'hiddenInputs' => [
//                            [
//                                'class' => 'modal-options',
//                                'data'  => [
//                                    'modal-to-open' => 'badge_component',
//                                    'from-id'       => $activitySingle->id,
//                                    'from-type'     => $activitySingle->element,
//                                    'from-field'    => $key
//                                ]
//                            ]
//                        ]
//                    ]
//                ],
            ]);
        }

        // Display the current evaluation line(s) of the activity; each line keeps its own re-evaluation history.
        $riskAssessment->displayRiskAssessmentList($activityInfos);

        print '</div>';
        print '</div>';
    }
    print '</div>';
}

// End of page
llxFooter();
$db->close();
