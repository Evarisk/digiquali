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
 * \file    view/frontend/pwa_answer.php
 * \ingroup digiquali
 * \brief   PWA mobile screen to answer the questions of a control or a survey.
 *
 * Same wizard as the public QR page, wrapped in the PWA chrome. Answers are saved on the
 * fly; "Finish" only saves and goes back to the list, validating a control stays an
 * explicit back-office action.
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
$objectType = GETPOST('object_type', 'alpha');
if (!in_array($objectType, ['control', 'survey'])) {
    $objectType = 'control';
}

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/' . $objectType . '.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/questiongroup.class.php';
require_once __DIR__ . '/../../class/answer.class.php';
require_once __DIR__ . '/../../lib/digiquali_answer.lib.php';
require_once __DIR__ . '/../../lib/digiquali_answer_wizard.lib.php';
require_once __DIR__ . '/../../lib/digiquali_pwa.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

saturne_load_langs();

// Get parameters
$id     = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$className  = ucfirst($objectType);
$object     = new $className($db);
$className  = $className . 'Line';
$objectLine = new $className($db);
$sheet      = new Sheet($db);

$hookmanager->initHooks(['pwaanswer', $objectType . 'card']);

$object->fetch($id);

$permissionToRead  = $user->rights->digiquali->$objectType->read;
$permissionToWrite = $user->rights->digiquali->$objectType->write;

if (empty($permissionToRead)) {
    accessforbidden();
}
if ($object->id <= 0) {
    accessforbidden($langs->trans('ErrorRecordNotFound'));
}

/*
 * Actions
 */

if ($action == 'save' && !empty($permissionToWrite)) {
    // Handles both the per-answer autosave and the full form submit. It never validates the
    // object: that only happens when $_POST['public_interface'] is set, which this page never sends.
    require_once __DIR__ . '/../../core/tpl/digiquali_answers_save_action.tpl.php';
}

if (!empty($permissionToWrite)) {
    // Actions uploadPhoto, uploadFile, deletePhoto, deleteFile posted by the Saturne media block.
    // It replays the HTML of the response to refresh itself, so this must not redirect.
    require __DIR__ . '/../../core/tpl/actions/digiquali_media_block_actions.tpl.php';
}

/*
 * View
 */

$title    = $langs->trans('Answers') . ' - ' . $object->ref;
$help_url = 'FR:Module_DigiQuali';
// llxHeader() does not load the Saturne assets on its own, and the media block needs both its
// JS and its CSS: without saturne.min.css the linked photos render unconstrained.
$moreJS   = ['/custom/saturne/js/saturne.min.js', '/custom/digiquali/js/digiquali.min.js'];
$moreCSS  = ['/custom/saturne/css/saturne.min.css', '/custom/digiquali/css/digiquali.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

llxHeader('', $title, $help_url, '', 0, 0, $moreJS, $moreCSS, '', 'template-pwa pwa-answer');

$pwaHeaderCenterHtml = '<div class="pwa-header-indicator"><i class="fas fa-clipboard-check"></i> ' . dol_escape_htmltag($object->ref) . '</div>';
require_once __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_header.tpl.php';

$sheet->fetch($object->fk_sheet);
$questionsAndGroups = $sheet->fetchQuestionsAndGroups();
$wizardSteps        = digiquali_answer_wizard_build_steps($object, $questionsAndGroups);

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?action=save&id=' . $object->id . '&object_type=' . $object->element . '" id="saveObject" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

print '<div class="question-answer-container question-answer-container-pwa">';

// Intro screen: what is being answered
ob_start();
require __DIR__ . '/../../core/tpl/frontend/pwa_answer_object_header.tpl.php';
$wizardIntroHtml = ob_get_clean();

// Summary screen: save everything and go back to the list
ob_start();
if ($object->status == $object::STATUS_DRAFT && !empty($permissionToWrite)) {
    print '<div class="answer-wizard__summary-actions">';
    print '<button type="submit" class="answer-wizard__button answer-wizard__button--primary">' . $langs->trans('AnswerWizardFinish') . '</button>';
    print '</div>';
}
$wizardSummaryExtraHtml = ob_get_clean();

$isFrontend       = true;
$wizardExtraClass = 'answer-wizard--pwa';
require __DIR__ . '/../../core/tpl/frontend/digiquali_answer_wizard.tpl.php';

print '</div>';
print '</form>';

// Required by the media block: without it, picking a photo silently does nothing
require_once __DIR__ . '/../../../saturne/core/tpl/medias/photo_editor_modal.tpl.php';

llxFooter();
$db->close();
