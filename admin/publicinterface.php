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
 * \file    admin/publicinterface.php
 * \ingroup digiquali
 * \brief   DigiQuali publicinterface config page
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
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../lib/digiquali.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize objects

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['publicinterfaceadmin', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'set_public_interface_title') {
    $answerPublicInterfaceTitle = GETPOST('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_TITLE', 'none');
    dolibarr_set_const($db, 'DIGIQUALI_ANSWER_PUBLIC_INTERFACE_TITLE', $answerPublicInterfaceTitle, 'chaine', 0, '', $conf->entity);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
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
print dol_get_fiche_head($head, 'publicinterface', $title, -1, 'digiquali_color@digiquali');

print load_fiche_titre($langs->trans('Config'), '', '');

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' .newToken(). '">';
print '<input type="hidden" name="action" value="set_public_interface_title">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

$substitutionArray = getCommonSubstitutionArray($langs);
complete_substitutions_array($substitutionArray, $langs);

// Substitution array/string
$helpForSubstitution = '';
if (is_array($substitutionArray) && count($substitutionArray)) {
    $helpForSubstitution .= $langs->trans('AvailableVariables') . ' : <br>';
}
foreach ($substitutionArray as $key => $val) {
    if ($key != '__OBJECT_ELEMENT_REF__') {
        $helpForSubstitution .= $key . ' -> '. $langs->trans(dol_string_nohtmltag(dolGetFirstLineOfText($val))) . '<br>';
    } else {
        $helpForSubstitution .= $key . ' -> '. $langs->transnoentities('AnswerPublicInterfaceSubstitution') . '<br>';
    }
}

// Public answer title
$answerPublicInterfaceTitle = $langs->transnoentities($conf->global->DIGIQUALI_ANSWER_PUBLIC_INTERFACE_TITLE) ?: $langs->transnoentities('AnswerPublicInterface');
print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->transnoentities('AnswerPublicInterfaceTitle'), $helpForSubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody');
print '</td><td>';
$dolEditor = new DolEditor('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_TITLE', $answerPublicInterfaceTitle, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$dolEditor->Create();
print '</td></tr>';

print '</table>';
print $form->buttonsSaveCancel('Save', '');
print '</form>';

$constArray['digiquali'] = [
    'AnswerPublicInterfaceUseSignatory' => [
        'name'        => 'AnswerPublicInterfaceUseSignatory',
        'description' => 'AnswerPublicInterfaceUseSignatoryDescription',
        'code'        => 'DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY',
    ],
    'AnswerPublicInterfaceShowTitle' => [
        'name'        => 'AnswerPublicInterfaceShowTitle',
        'description' => 'AnswerPublicInterfaceShowTitleDescription',
        'code'        => 'DIGIQUALI_ANSWER_PUBLIC_INTERFACE_SHOW_TITLE',
    ],
    'AnswerPublicInterfaceAddAction' => [
        'name'        => 'AnswerPublicInterfaceAddAction',
        'description' => 'AnswerPublicInterfaceAddActionDescription',
        'code'        => 'DIGIQUALI_ANSWER_PUBLIC_INTERFACE_ADD_ACTION',
    ]
];
require __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
