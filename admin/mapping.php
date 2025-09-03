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
 * \file    admin/mapping.php
 * \ingroup digiquali
 * \brief   DigiQuali mapping config page
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../lib/digiquali.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Initialize view objects
$formProjects = new FormProjets($db);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$hookmanager->initHooks(['mappingadmin', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'update_mapping_project') {
    $mappingProjectId = GETPOSTINT('mappingProjectId');

    if ($mappingProjectId > 0 && $mappingProjectId != getDolGlobalInt('DIGIQUALI_MAPPING_PROJECT')) {
        dolibarr_set_const($db, 'DIGIQUALI_MAPPING_PROJECT', $mappingProjectId, 'integer', 0, '', $conf->entity);

        setEventMessages('SavedConfig', []);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = digiquali_admin_prepare_head();
print dol_get_fiche_head($head, $object->element, $title, -1, 'digiquali_color@digiquali');

print load_fiche_titre($langs->trans("TasksManagement"), '', '');

// Project
if (isModEnabled('project')) {
    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update_mapping_project">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Name') . '</td>';
    print '<td>' . $langs->trans('SelectProject') . '</td>';
    print '<td class="center">' . $langs->trans('Action') . '</td>';
    print '</tr>';

    print '<tr class="oddeven"><td><label for="DUProject">' . $langs->trans('MappingProject') . '</label></td><td>';
    $formProjects->select_projects(-1,  getDolGlobalInt('DIGIQUALI_MAPPING_PROJECT'), 'mappingProjectId', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
    print '<a href="' . DOL_URL_ROOT . '/projet/card.php?leftmenu=projects&action=create&status=1&usage_opportunity=0&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans('AddProject') . '"></span></a>';
    print '</td><td class="center">';
    print '<button type="submit" class="butAction reposition">' . $langs->trans('Save') . '</button>';
    print '</td></tr>';

    print '</table>';
    print '</form>';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
