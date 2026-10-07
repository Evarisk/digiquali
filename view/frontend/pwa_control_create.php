<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/frontend/pwa_control_create.php
 * \ingroup digiquali
 * \brief   PWA mobile screen to start a control: pick a sheet, confirm the controlled object, then answer.
 *
 * Opened from the public control page of an object, which preselects the object and the sheet, project and tags
 * of its last control, or from the control list of the PWA. The control is created as a draft and the PWA answer
 * screen takes over: nothing goes through the back office.
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
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../lib/digiquali_control.lib.php';

global $conf, $db, $langs, $user;

saturne_load_langs();

// Get parameters
$action     = GETPOST('action', 'aZ09');
$fromType   = GETPOST('fromtype', 'aZ');
$fromId     = GETPOSTINT('fromid');
$fkSheet    = GETPOSTINT('fk_sheet');
$projectId  = GETPOSTINT('projectid');
$search     = GETPOST('search', 'alphanohtml');
$backtopage = GETPOST('backtopage', 'alpha');

// The public page passes the tags of the last control as a list, the form posts them as an array
$categories = GETPOST('categories', 'array');
if (empty($categories)) {
    $categories = explode(',', GETPOST('categories', 'intcomma'));
}
$categories = array_values(array_filter(array_map('intval', $categories)));

// Only a page of this site can be gone back to
if (!preg_match('/^\/[^\/]/', $backtopage)) {
    $backtopage = '';
}

// Initialize technical objects
$sheet = new Sheet($db);

if (!$user->hasRight('digiquali', 'control', 'write')) {
    accessforbidden();
}

$objectsMetadata = saturne_get_objects_metadata();

// Object to control, when the page is opened from it
$fixedObjectType = '';
$fixedObject     = null;
foreach ($objectsMetadata as $objectType => $objectMetadata) {
    if (!empty($fromType) && $objectMetadata['link_name'] == $fromType) {
        $fixedObjectType = $objectType;
        break;
    }
}
if (!empty($fixedObjectType) && $fromId > 0) {
    if (!empty($objectsMetadata[$fixedObjectType]['class_path'])) {
        require_once DOL_DOCUMENT_ROOT . '/' . $objectsMetadata[$fixedObjectType]['class_path'];
    }
    $fixedObject = new $objectsMetadata[$fixedObjectType]['class_name']($db);
    if ($fixedObject->fetch($fromId) <= 0) {
        $fixedObject = null;
    }
}
if (empty($fixedObject)) {
    $fixedObjectType = '';
}

// A sheet that cannot control this object sends back to the choice of the sheet
if ($fkSheet > 0) {
    if ($sheet->fetch($fkSheet) <= 0 || $sheet->type != 'control' || $sheet->status != Sheet::STATUS_LOCKED || (!empty($fixedObjectType) && !preg_match('/"' . preg_quote($fixedObjectType, '/') . '":1/', $sheet->element_linked))) {
        $fkSheet = 0;
    }
}

// Object types the sheet controls
$sheetObjectTypes = [];
if ($fkSheet > 0) {
    foreach (json_decode($sheet->element_linked, true) ?: [] as $objectType => $isLinked) {
        if (!empty($isLinked) && !empty($objectsMetadata[$objectType])) {
            $sheetObjectTypes[] = $objectType;
        }
    }
}

// Parameters kept from one screen to the other
$createParameters = array_filter([
    'fromtype'   => $fromType,
    'fromid'     => $fromId,
    'projectid'  => $projectId,
    'categories' => implode(',', $categories),
    'backtopage' => $backtopage
]);

/*
 * Actions
 */

if ($action == 'add' && $fkSheet > 0) {
    // Control::create() links the objects posted under their post name
    if (!empty($fixedObject)) {
        $_POST[$objectsMetadata[$fixedObjectType]['post_name']] = $fixedObject->id;
    }

    $controlledObjects = 0;
    foreach ($sheetObjectTypes as $objectType) {
        if (GETPOSTINT($objectsMetadata[$objectType]['post_name']) > 0) {
            $controlledObjects++;
        }
    }

    if ($controlledObjects == 0) {
        setEventMessages($langs->trans('NeedObjectToControl'), [], 'errors');
    } else {
        $object = new Control($db);

        $object->fk_sheet           = $sheet->id;
        $object->label              = $sheet->label;
        $object->fk_user_controller = $user->id;
        $object->projectid          = $projectId > 0 ? $projectId : ($sheet->fk_project > 0 ? $sheet->fk_project : null);
        $object->status             = Control::STATUS_DRAFT;

        $result = $object->create($user);
        if ($result > 0) {
            if (isModEnabled('categorie') && !empty($categories)) {
                $object->setCategories($categories);
            }

            header('Location: ' . dol_buildpath('/custom/digiquali/view/frontend/pwa_answer.php', 1) . '?id=' . $object->id . '&object_type=control&source=pwa' . (!empty($backtopage) ? '&backtopage=' . urlencode($backtopage) : ''));
            exit;
        }
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

/*
 * View
 */

$title    = $langs->trans('NewControl');
$help_url = 'FR:Module_DigiQuali';
$moreJS   = ['/custom/saturne/js/saturne.min.js', '/custom/digiquali/js/digiquali.min.js'];
$moreCSS  = ['/custom/saturne/css/saturne.min.css', '/custom/digiquali/css/digiquali.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

llxHeader('', $title, $help_url, '', 0, 0, $moreJS, $moreCSS, '', 'template-pwa pwa-control-create-page');

// Installed as an app there is no browser back button, so the header carries the way out
$pwaHeaderBackUrl    = $backtopage ?: dol_buildpath('/custom/digiquali/view/frontend/pwa_controls.php', 1) . '?source=pwa';
$pwaHeaderCenterHtml = '<div class="pwa-header-indicator"><i class="fas fa-plus"></i> ' . $langs->trans('NewControl') . '</div>';
require_once __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_header.tpl.php';

if ($fkSheet > 0) {
    require __DIR__ . '/../../core/tpl/frontend/pwa_control_create_form.tpl.php';
} else {
    require __DIR__ . '/../../core/tpl/frontend/pwa_control_create_sheet.tpl.php';
}

llxFooter();
$db->close();
