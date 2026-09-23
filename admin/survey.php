<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    admin/survey.php
 * \ingroup digiquali
 * \brief   DigiQuali survey config page
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
require_once __DIR__ . '/../class/survey.class.php';
require_once __DIR__ . '/../lib/digiquali.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');
$attrname   = GETPOST('attrname', 'alpha');

// List of supported format type extrafield label
$tmptype2label = ExtraFields::$type2label;
$type2label    = [''];
foreach ($tmptype2label as $key => $val) {
    $type2label[$key] = $langs->transnoentitiesnoconv($val);
}

// Initialize objects
$object = new Survey($db);

$hookmanager->initHooks(['surveyadmin', 'globalcard']); // Note that conf->hooks_modules contains array

$elementtype = $moduleNameLowerCase . '_' . $object->element; // Must be the $table_element of the class that manage extrafield

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Extrafields actions
require_once DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

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

/*
 * Numbering module
 */

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$constArray = $constArray ?? [];
require __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

/*
 * Numbering module line
 */

$object = new SurveyLine($db);

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

// Extrafields survey management
print load_fiche_titre($langs->trans('ExtrafieldsSurveyManagement'), '', '');

$textobject = dol_strtolower($langs->transnoentities('Survey'));
require_once DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

// Buttons
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create">' . $langs->trans('NewAttribute') . '</a></div>';
    print '</div>';
}

// Creation of an optional field
if ($action == 'create') {
    print load_fiche_titre($langs->trans('NewAttribute'));
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field
if ($action == 'edit' && !empty($attrname)) {
    print load_fiche_titre($langs->trans('FieldEdition', $attrname));
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
