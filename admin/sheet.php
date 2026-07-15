<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/sheet.php
 * \ingroup digiquali
 * \brief   DigiQuali sheet config page.
 */

// Load DigiQuali environment.
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries.
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load DigiQuali libraries.
require_once __DIR__ . '/../lib/digiquali.lib.php';
require_once __DIR__ . '/../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../class/sheet.class.php';

// Global variables definitions.
global $conf, $db, $langs, $user;

// Load translation files required by the page.
saturne_load_langs(['admin', 'accountancy']);

// Get parameters
$action      = GETPOST('action', 'alpha');
$backtopage  = GETPOST('backtopage', 'alpha');
$value       = GETPOST('value', 'alpha');
$attrname    = GETPOST('attrname', 'alpha');

// Initialize technical objects.
$object = new Sheet($db);
$tags   = new Categorie($db);

// Initialize view objects.
$form      = new Form($db);
$formOther = new FormOther($db);

// List of supported format.
$tmptype2label = ExtraFields::$type2label;
$type2label    = [''];
foreach ($tmptype2label as $key => $val) {
    $type2label[$key] = $langs->transnoentitiesnoconv($val);
}

$elementtype = 'digiquali_sheet'; // Must be the $table_element of the class that manage extrafield.
$error = 0; // Error counter.

$hookmanager->initHooks([$object->element . 'admin', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user.
$permissiontoread = $user->rights->digiquali->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Actions set_mod, update_mask
require_once __DIR__ . '/../../saturne/core/tpl/actions/admin_conf_actions.tpl.php';

// Extrafields actions.
require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';

// Generate default categories
if ($action == 'generateCategories') {
	$tags->label = $langs->transnoentities('Quality');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('HealthSecurity');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('Environment');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Safety');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Regulatory');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('DesignOffice');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('Suppliers');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('CommercialDigiQuali');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('Production');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Methods');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Accounting');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('Logistics');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->trans('ComputerScience');
	$tags->type  = 'sheet';
	$tags->create($user);

	$tags->label = $langs->transnoentities('Default');
	$tags->type  = 'sheet';
	$tags->create($user);

	dolibarr_set_const($db, 'DIGIQUALI_SHEET_DEFAULT_TAG', $tags->id, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIQUALI_SHEET_TAGS_SET', 1, 'integer', 0, '', $conf->entity);
}

if ($action == 'generate_main_categories') {
    $tagParentID = saturne_create_category($langs->transnoentities('Control'), 'sheet');

    saturne_create_category($langs->transnoentities('Quality'), 'sheet', $tagParentID, 'pictogram_Quality_64px.png');

    $tagID = saturne_create_category($langs->transnoentities('HealthSecurity'), 'sheet', $tagParentID, 'pictogram_HealthSecurity_64px.png');
    saturne_create_category($langs->transnoentities('FirstAidKits'), 'sheet', $tagID, 'pictogram_FirstAidKits_64px.png');

    $tagID = saturne_create_category($langs->transnoentities('Materials'), 'sheet', $tagParentID, 'pictogram_Materials_64px.png');
    saturne_create_category($langs->transnoentities('Mask'), 'sheet', $tagID, 'pictogram_Mask_64px.png');

    $tagID = saturne_create_category($langs->transnoentities('Vehicles'), 'sheet', $tagParentID, 'pictogram_Vehicles_64px.png');
    saturne_create_category($langs->transnoentities('Car'), 'sheet', $tagID, 'pictogram_Car_64px.png');
    saturne_create_category($langs->transnoentities('IndustrialVehicles'), 'sheet', $tagID, 'pictogram_IndustrialVehicles_64px.png');

    dolibarr_set_const($db, 'DIGIQUALI_SHEET_MAIN_CATEGORY', $tagParentID, 'integer', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIQUALI_SHEET_MAIN_CATEGORIES_SET', 1, 'integer', 0, '', $conf->entity);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'set_main_category') {
    $categoryID = GETPOST('main_category');
    dolibarr_set_const($db, 'DIGIQUALI_SHEET_MAIN_CATEGORY', $categoryID, 'integer', 0, '', $conf->entity);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */
$title    = $langs->trans('ModuleSetup', $moduleName);
$help_url = 'FR:Module_DigiQuali';

saturne_header(0,'', $title, $help_url);

// Subheader.
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header.
$head = digiquali_admin_prepare_head();
print dol_get_fiche_head($head, $object->element, $title, -1, 'digiquali_color@digiquali');

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$constArray[$moduleNameLowerCase] = [
	'UniqueLinkedElement' => [
		'name'        => 'UniqueLinkedElement',
		'description' => 'UniqueLinkedElementDescription',
		'code'        => 'DIGIQUALI_SHEET_UNIQUE_LINKED_ELEMENT',
	],
	'SheetLinkedObjectSelect2' => [
		'name'        => 'SheetLinkedObjectSelect2',
		'description' => 'SheetLinkedObjectSelect2Description',
		'code'        => 'DIGIQUALI_SHEET_LINKED_OBJECT_SELECT2',
	],
];

//@todo add only wanted keys
$objectsMetadata                  = saturne_get_objects_metadata();
$constArray[$moduleNameLowerCase] = array_merge($constArray[$moduleNameLowerCase], $objectsMetadata);

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

// Generate categories.
print load_fiche_titre($langs->trans('SheetCategories'), '', '', 0, 'sheetCategories');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '<td class="center">' . $langs->trans('ShortInfo') . '</td>';
print '</tr>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generateCategories">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

print '<tr><td>' . $langs->trans('GenerateCategories') . '</td>';
print '<td class="center">';
print $conf->global->DIGIQUALI_SHEET_TAGS_SET ? $langs->trans('AlreadyGenerated') : $langs->trans('NotCreated');
print '</td>';
print '<td class="center">';
print $conf->global->DIGIQUALI_SHEET_TAGS_SET ? '<a type="" class=" butActionRefused" value="">'.$langs->trans('Create') . '</a>' : '<input type="submit" class="button" value="'. $langs->trans('Create') .'">' ;
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->trans('CategoriesGeneration'));
print '</td></tr>';
print '</form>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generate_main_categories">';

print '<tr class="oddeven"><td>' . $langs->transnoentities('GenerateMainSheetCategories') . '</td>';
print '<td class="center">';
print getDolGlobalInt('DIGIQUALI_SHEET_MAIN_CATEGORIES_SET') ? $langs->transnoentities('AlreadyGenerated') : $langs->transnoentities('NotCreated');
print '</td><td class="center">';
print getDolGlobalInt('DIGIQUALI_SHEET_MAIN_CATEGORIES_SET') ? '<a type="" class=" butActionRefused" value="">' . $langs->transnoentities('Create') . '</a>' : '<input type="submit" class="button" value="' . $langs->transnoentities('Create') . '">';
print '</td><td class="center">';
print $form->textwithpicto('', $langs->trans('MainSheetCategoriesDescription'));
print '</td></tr>';
print '</form>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_main_category">';

// Set default main category
print '<tr class="oddeven"><td>' . $langs->transnoentities('SheetMainCategory') . '</td>';
print '<td class="center">';
print $formOther->select_categories('sheet', getDolGlobalInt('DIGIQUALI_SHEET_MAIN_CATEGORY'), 'main_category');
print '</td><td class="center">';
print '<div><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</td><td class="center">';
print $form->textwithpicto('', $langs->trans('SheetMainCategoryDescription'));
print '</td></tr>';
print '</form>';
print '</table>';

// Extrafields sheet management.
print load_fiche_titre($langs->trans('ExtrafieldsSheetManagement'), '', '');

require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

// Buttons.
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create">' . $langs->trans('NewAttribute') . '</a></div>';
    print '</div>';
}

// Creation of an optional field.
if ($action == 'create') {
    print load_fiche_titre($langs->trans('NewAttribute'));
    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field.
if ($action == 'edit' && !empty($attrname)) {
    print load_fiche_titre($langs->trans('FieldEdition', $attrname));
    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

// Page end.
print dol_get_fiche_end();
llxFooter();
$db->close();
