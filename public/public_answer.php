<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    public/public_answer.php
 * \ingroup digiquali
 * \brief   Public page to questions answer
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged
    define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website
    define('NOCSRFCHECK', '1');
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', '1');
}

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Get module parameters
$objectType   = GETPOST('object_type', 'alpha');
$documentType = GETPOST('document_type', 'alpha');

// Load Saturne libraries
if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY')) {
    require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';
}

// Load DigiQuali libraries
require_once __DIR__ . '/../class/' . $objectType . '.class.php';
require_once __DIR__ . '/../class/sheet.class.php';
require_once __DIR__ . '/../class/question.class.php';
require_once __DIR__ . '/../class/questiongroup.class.php';
require_once __DIR__ . '/../class/answer.class.php';
require_once __DIR__ . '/../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../lib/digiquali_answer.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once __DIR__ . '/../lib/digiquali_control.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $moduleNameLowerCase, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects']);

// Get parameters
$trackID   = GETPOST('track_id', 'alpha');
$entity    = GETPOST('entity');
$action    = GETPOST('action');
$subaction = GETPOST('subaction');

// Initialize technical objects
$className     = ucfirst($objectType);
$object        = new $className($db);
$className     = $className . 'Line';
$objectLine    = new $className($db);
$sheet         = new Sheet($db);
$question      = new Question($db);
$answer        = new Answer($db);
$questionGroup = new QuestionGroup($db);

if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY')) {
    $fileExists = file_exists('../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . strtolower($documentType) . '.class.php');
    if ($fileExists && GETPOSTISSET('document_type')) {
        require_once __DIR__ . '/../../' . $moduleNameLowerCase . '/class/' . $moduleNameLowerCase . 'documents/' . strtolower($documentType) . '.class.php';
    }
    if (GETPOSTISSET('document_type') && $fileExists) {
        $document = new $documentType($db);
    }
    $signatory = new SaturneSignature($db, $moduleNameLowerCase, $object->element);

    $upload_dir = $conf->$moduleNameLowerCase->multidir_output[$object->entity ?? 1];
}

$hookmanager->initHooks(['publicanswer', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

// Load object
$object->fetch(0, '', ' AND track_id = ' . "'" . $trackID . "'");

$linkableElements = saturne_get_objects_metadata();
$object->fetchObjectLinked('', '', '', 'digiquali_control');
$linkedObjectType = key($object->linkedObjects);
$linkedObject     = (is_array($object->linkedObjects[$linkedObjectType] ?? null) && !empty($object->linkedObjects[$linkedObjectType]))
    ? current($object->linkedObjects[$linkedObjectType])
    : null;

if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY')) {
    $signatory->fetch(0, '', ' AND status >= ' . SaturneSignature::STATUS_REGISTERED . ' AND object_type= ' . "'" . $object->element . "'" . ' AND fk_object = ' . "'" . $object->id . "'");
}

// Corrective actions carried by the answers. An action is a project task linked to the answer of a
// question, exactly as on the control card. The public interface only opens that door when the option
// says so, and only for a control attached to the project the actions are created into
$publicInterfaceActions = getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_ADD_ACTION') && $object->id > 0 && $object->element == 'control' && isModEnabled('projet');

$task                            = null;
$taskNextValue                   = '';
$taskForcedProjectId             = 0;
$permissionToReadTask            = 0;
$permissionToAddTask             = 0;
$permissionToDeleteTask          = 0;
$permissionToManageTaskTimeSpent = 0;

// Whoever opens the public link is anonymous until proven otherwise : sensitive content (who is on an
// action, what it costs, how long it took, the links into the back office) is only ever shown to a
// visitor who is logged in and allowed to read projects
$visitorUser = new User($db);
if (!empty($_SESSION['dol_login'])) {
    $visitorUser->fetch('', $_SESSION['dol_login'], '', 1);
    $visitorUser->getrights();
}
$taskPublicView = !($visitorUser->id > 0 && ($visitorUser->hasRight('project', 'lire') || $visitorUser->hasRight('project', 'all', 'lire')));

if ($publicInterfaceActions) {
    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

    // Load Saturne libraries
    require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';

    if (!empty($object->projectid)) {
        $object->fk_project = $object->projectid; // Need special case because projectid is only on control object
        $object->fetch_project();
    }
    $taskForcedProjectId = (int) ($object->project->id ?? 0);

    $task = new SaturneTask($db);
    list($refTaskMod) = saturne_require_objects_mod(['project/task' => getDolGlobalString('PROJECT_TASK_ADDON')]);
    $taskNextValue    = $refTaskMod->getNextValue($object->id, $object->element);

    // There is no logged in user to check rights against : the option is what opens reading the actions
    // of this control and adding one to it, and answers are only completed while the control is a draft
    $permissionToReadTask = 1;
    $permissionToAddTask  = ($object->status == $object::STATUS_DRAFT && $taskForcedProjectId > 0) ? 1 : 0;
}

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') && $signatory->id > 0) {
        // Actions add_signature, builddoc, remove_file
        require_once __DIR__ . '/../../saturne/core/tpl/actions/signature_actions.tpl.php';
    }

    // Set user for action update and insert for prevent error on public interface
    $user->id = 1;

    // Actions uploadPhoto, uploadFile, deletePhoto, deleteFile posted by the Saturne media block.
    // It replays the HTML of the response to refresh itself, so this must not redirect
    if (in_array($action, ['uploadPhoto', 'uploadFile', 'deletePhoto', 'deleteFile'])) {
        if ($object->id > 0 && $object->status == $object::STATUS_DRAFT && digiquali_answer_media_dir_is_allowed($object, $action)) {
            require_once __DIR__ . '/../core/tpl/actions/digiquali_media_block_actions.tpl.php';
        }
    }

    // Actions add_task, fetch_task, update_task, update_task_progress, check_task posted by the corrective
    // actions of a question. Deleting an action and logging time on it stay back-office matters
    if ($publicInterfaceActions && in_array($action, ['add_task', 'fetch_task', 'update_task', 'update_task_progress', 'check_task'])) {
        // An anonymous request must not reach past the control behind the track_id : the answer an action
        // is attached to, and the action an update targets, both have to belong to this control
        $taskRequestData = json_decode(file_get_contents('php://input'), true) ?: [];

        if ($action == 'add_task') {
            $answerLineClass   = get_class($objectLine);
            $answerLine        = new $answerLineClass($db);
            $taskActionAllowed = ($answerLine->fetch((int) ($taskRequestData['objectLine_id'] ?? 0)) > 0 && $answerLine->fk_control == $object->id);
        } else {
            // Opening the add modal also asks for a task, with the id of an answer line : it finds nothing
            // here, which is right, the modal it renders does not need one
            $requestedTaskId   = ($action == 'check_task' ? GETPOSTINT('task_id') : (int) ($taskRequestData['task_id'] ?? $taskRequestData['from_id'] ?? 0));
            $taskActionAllowed = in_array($requestedTaskId, digiquali_get_control_action_task_ids($object->id), true);
        }

        if ($taskActionAllowed) {
            require_once __DIR__ . '/../core/tpl/digiquali_answers_task_action.tpl.php';
        }
    }

    require_once __DIR__ . '/../core/tpl/digiquali_answers_save_action.tpl.php';
}

/*
 * View
 */

$title  = $langs->trans('PublicAnswer');
$moreJS = ['/saturne/js/includes/signature-pad.min.js'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(1,'', $title, '', '', 0, 0, $moreJS, [], '', 'page-public-card page-signature page-public-answer');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?action=save&id=' . $object->id . '&track_id=' . $trackID . '&object_type=' . $object->element . '&document_type=' . $documentType . '&entity=' . $conf->entity . '" id="saveObject" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="public_interface" value="true">';
print '<input type="hidden" name="action" value="save">';

$sheet->fetch($object->fk_sheet);
$questionsAndGroups = $sheet->fetchQuestionsAndGroups();

require_once __DIR__ . '/../lib/digiquali_answer_wizard.lib.php';
$wizardSteps = digiquali_answer_wizard_build_steps($object, $questionsAndGroups);

$substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object);
complete_substitutions_array($substitutionArray, $langs, $object);
$answerPublicInterfaceTitle = make_substitutions($langs->transnoentities($conf->global->DIGIQUALI_ANSWER_PUBLIC_INTERFACE_TITLE), $substitutionArray);
if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_SHOW_TITLE')) {
    print '<h2 class="page-title center">' . (dol_strlen($answerPublicInterfaceTitle) > 0 ? $answerPublicInterfaceTitle : $langs->transnoentities('AnswerPublicInterface')) . '</h2>';
}

print '<div class="question-answer-container question-answer-container-pwa public-card__container" data-public-interface="true">';
$publicInterface = true;

$isFrontend = true;

// Modals of the corrective actions, inside the container the JS looks them up from
if (!empty($permissionToAddTask)) {
    $form = new Form($db);
    require_once __DIR__ . '/../core/tpl/modal/modal_task_add.tpl.php';
    require_once __DIR__ . '/../core/tpl/modal/modal_task_edit.tpl.php';
}

// Intro screen of the wizard: what is being controlled
$wizardIntroHtml = '';
if (!empty($linkedObject)) {
    ob_start();
    require __DIR__ . '/../core/tpl/frontend/control_answer_public_header.tpl.php';
    $wizardIntroHtml = ob_get_clean();
}

// Summary screen of the wizard: signature then the only action that validates the object
ob_start();
if (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') && $signatory->id > 0) {
    $previousStatus        = $object->status;
    $object->status        = $object::STATUS_VALIDATED; // Special case because public answer need draft status object to complete question
    $moreParams['moreCSS'] = 'hidden';                  // Needed for prevent click on signature button action
    require_once __DIR__ . '/../../saturne/core/tpl/signature/public_signature_view.tpl.php';
    $object->status = $previousStatus;
}
if ($object->status == $object::STATUS_DRAFT) {
    print '<div class="public-card__footer">';
    print '<button type="submit" class="wpeo-button save-public-answer ' . (getDolGlobalInt('DIGIQUALI_ANSWER_PUBLIC_INTERFACE_USE_SIGNATORY') && $signatory->id > 0 ? 'signature-validate button-disable' : '') . '">' . $langs->trans('AnswerWizardFinish') . '</button>';
    print '</div>';
}
$wizardSummaryExtraHtml = ob_get_clean();

require __DIR__ . '/../core/tpl/frontend/digiquali_answer_wizard.tpl.php';
print '</div>';
print '</form>';

// Required by the media block: without it, picking a photo silently does nothing
require_once __DIR__ . '/../../saturne/core/tpl/medias/photo_editor_modal.tpl.php';

llxFooter('', 'public');
$db->close();
