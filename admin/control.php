<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    admin/control.php
 * \ingroup digiquali
 * \brief   DigiQuali control config page
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
require_once __DIR__ . '/../class/control.class.php';
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
$object = new Control($db);

$hookmanager->initHooks(['controladmin', 'globalcard']); // Note that conf->hooks_modules contains array

$elementtype = $moduleNameLowerCase . '_' . $object->element; // Must be the $table_element of the class that manage extrafield

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

//Extrafields actions
require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

if ($action == 'update_control_reminder') {
    $reminderFrequency = GETPOST('control_reminder_frequency');
    $reminderType      = GETPOST('control_reminder_type');

    dolibarr_set_const($db, 'DIGIQUALI_CONTROL_REMINDER_FREQUENCY', $reminderFrequency, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIQUALI_CONTROL_REMINDER_TYPE', $reminderType, 'chaine', 0, '', $conf->entity);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'update_next_control_date_color') {
    $errors = [];
    $error  = 0;

    $nextControlDateFrequencies = [0, 30, 60, 90];
    foreach ($nextControlDateFrequencies as $nextControlDateFrequency) {
        $nextControlDateFrequencyValue = GETPOST('next_control_date_color_' . $nextControlDateFrequency);
        $confName                      = 'DIGIQUALI_NEXT_CONTROL_DATE_COLOR_' . $nextControlDateFrequency;
        if ($nextControlDateFrequencyValue != getDolGlobalString($confName)) {
            $result = dolibarr_set_const($db, $confName, $nextControlDateFrequencyValue, 'chaine', 0, '', $conf->entity);
            if ($result < 0) {
                $error++;
                $errors[] = $db->lasterror();
            }
        }
    }

    if ($error > 0) {
        setEventMessages('ErrorUpdateConfig', $errors, 'errors');
    } else {
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
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiquali_admin_prepare_head();
print dol_get_fiche_head($head, $object->element, $title, -1, 'digiquali_color@digiquali');

/*
 *  Numbering module
 */

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$constArray = $constArray ?? [];
require __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

/*
 * Numbering module line
 */

$object = new ControlLine($db);

require __DIR__ . '/../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

// Control reminder
print load_fiche_titre($langs->trans('ControlReminder'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_control_reminder">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Value') . '</td>';
print '</tr>';

// Enable control reminder
print '<tr class="oddeven"><td>';
print $langs->trans('ControlReminder');
print '</td><td>';
print $langs->trans('ControlReminderDescription');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIQUALI_CONTROL_REMINDER_ENABLED');
print '</td></tr>';

// Define control reminder frequency in days (ex: 30,60,90)
print '<tr class="oddeven"><td>';
print $langs->trans('ControlReminderFrequency');
print '</td><td>';
print $langs->trans('ControlReminderFrequencyDescription');
print '</td>';

print '<td class="center">';
print '<input type="text" name="control_reminder_frequency" value="' . $conf->global->DIGIQUALI_CONTROL_REMINDER_FREQUENCY . '">';
print '</td></tr>';

// Define control reminder type
print '<tr class="oddeven"><td>';
print $langs->trans('ControlReminderType');
print '</td><td>';
print $langs->trans('ControlReminderTypeDescription');
print '</td>';

print '<td class="center">';
$controlReminderType = ['browser' => 'Browser', 'email' => 'Email', 'sms' => 'SMS'];
print Form::selectarray('control_reminder_type', $controlReminderType, (!empty($conf->global->DIGIQUALI_CONTROL_REMINDER_TYPE) ? $conf->global->DIGIQUALI_CONTROL_REMINDER_TYPE : $controlReminderType[0]), 0, 0, 0, '', 1);
print '</td></tr>';

// Disable the attached documents upload on control answers (#2442)
print '<tr class="oddeven"><td>';
print $langs->trans('DisableControlAttachedFiles');
print '</td><td>';
print $langs->trans('DisableControlAttachedFilesDescription');
print '</td>';
print '<td class="center">';
print ajax_constantonoff('DIGIQUALI_CONTROL_DISABLE_ATTACHED_FILES');
print '</td></tr>';

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';
print '</table>';

// Manage next control date colors
print load_fiche_titre($langs->transnoentities('NextControlDateColorManagement'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="color_form">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update_next_control_date_color">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities('Parameters') . '</td>';
print '<td>' . $langs->transnoentities('Description') . '</td>';
print '<td>' . $langs->transnoentities('Value') . '</td>';
print '</tr>';

$nextControlDateFrequencies = [0 => '#FF3535', 30 => '#FD7E00', 60 => '#FFB700', 90 => '#C7BA10'];
foreach ($nextControlDateFrequencies as $nextControlDateFrequency => $nextControlDateFrequencyDefaultColor) {
    print '<tr class="oddeven"><td>' . $langs->transnoentities('NextControlDateColor' . $nextControlDateFrequency) . '</td><td>';
    print $langs->transnoentities('NextControlDateColor' . $nextControlDateFrequency . 'Description') . '</td><td>';
    print '<input type="color" name="next_control_date_color_' . $nextControlDateFrequency . '" value="' . getDolGlobalString('DIGIQUALI_NEXT_CONTROL_DATE_COLOR_' . $nextControlDateFrequency, $nextControlDateFrequencyDefaultColor) . '" />';
    print '<span class="marginleftonly opacitymedium">' . $langs->trans('Default') . '</span>: <strong>' . $nextControlDateFrequencyDefaultColor . '</strong>';
    print '</td></tr>';
}

print '</table>';
print '<div class="tabsAction"><input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// Extrafields control management
print load_fiche_titre($langs->trans('ExtrafieldsControlManagement'), '', '');

$textobject = dol_strtolower($langs->transnoentities('Control'));
require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

// Buttons
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create">' . $langs->trans('NewAttribute') . '</a></div>';
    print '</div>';
}

// Creation of an optional field
if ($action == 'create') {
    print load_fiche_titre($langs->trans('NewAttribute'));
    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field
if ($action == 'edit' && !empty($attrname)) {
    print load_fiche_titre($langs->trans('FieldEdition', $attrname));
    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
