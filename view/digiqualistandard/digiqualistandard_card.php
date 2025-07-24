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
 * \file    view/digiqualistandard/digiqualistandard_card.php
 * \ingroup digiquali
 * \brief   Page to digiqualistandard informations and dashboard
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Get module parameters
$moduleName          = GETPOST('module_name', 'aZ') ?: 'saturne';
$moduleNameLowerCase = dol_strtolower($moduleName);

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/digiqualistandard.class.php';
require_once __DIR__ . '/../../lib/digiquali_digiqualistandard.lib.php';

// Global variables definitions
global $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id        = GETPOSTISSET('id') ? GETPOSTINT('id') : getDolGlobalInt('DIGIQUALI_ACTIVE_STANDARD');
$action    = GETPOST('action', 'alpha');
$subaction = GETPOST('subaction', 'alpha');

// Initialize technical objects
$object    = new DigiQualiStandard($db);
$dashboard = new SaturneDashboard($db, $object->module);

$hookmanager->initHooks([$object->element . 'card', $object->module . 'view', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';

// Permissions
//$permissiontoread = $user->hasRight($object->module, $object->element, 'read');
$permissiontoread = 1;

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

if (empty($resHook)) {
    // Actions adddashboardinfo, closedashboardinfo, generate_csv
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/dashboard_actions.tpl.php';
}

/*
 * View
 */

$title   = $langs->trans('Informations');
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(1,'', $title, $helpUrl, '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist sidebar-secondary-opened');

// Part to show record
if ($object->id > 0) {
    print '<div>';
    saturne_get_fiche_head($object, 'card', $title);
    saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', '', true);

    print '<div class="fichecenter"><br>';

    //$dashboard->show_dashboard($moreParams);

    print '</div>';

    print dol_get_fiche_end();
    print '</div>';
}

// End of page
llxFooter();
$db->close();
