<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    view/control/control_actionplan.php
 * \ingroup digiquali
 * \brief   Tab for the action plan of a control
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
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/lib/object.lib.php';
require_once __DIR__ . '/../../../saturne/class/task/saturnetask.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/digiquali_control.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects']);

// Get parameters
$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$view   = GETPOST('view', 'aZ09');

$actionPlanGranularity = GETPOST('granularity', 'aZ09');

// Get filter parameters. The empty entry of a select posts -1, which stands for "no filter at all"
$searchQuestion = GETPOSTINT('search_question');
$searchStatus   = GETPOST('search_status', 'aZ09');
$searchVerdict  = GETPOST('search_verdict', 'alphanohtml');
$searchAssignee = GETPOSTINT('search_assignee');
$searchText     = GETPOST('search_text', 'alphanohtml');
$searchStatus   = ($searchStatus == '-1' ? '' : $searchStatus);
$searchVerdict  = ($searchVerdict == '-1' ? '' : $searchVerdict);

// Initialize technical objects
$object = new Control($db);
$task   = new SaturneTask($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['controlactionplan', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';

// The control carries its project as projectid, while fetch_project() reads fk_project
$object->fk_project = $object->projectid;
$object->fetch_project();

list($refTaskMod) = saturne_require_objects_mod(['project/task' => getDolGlobalString('PROJECT_TASK_ADDON')]);
$taskNextValue    = $refTaskMod->getNextValue($object->id, $object->element);

// Permissions
$permissiontoread = $user->hasRight('digiquali', 'control', 'read');
$permissiontoadd  = $user->hasRight('digiquali', 'control', 'write');

// Permissions for tasks management, the actions of the plan being project tasks
$permissionToReadTask            = $user->hasRight('project', 'lire') || $user->hasRight('project', 'all', 'lire');
$permissionToAddTask             = $user->hasRight('project', 'creer') || $user->hasRight('project', 'all', 'creer');
$permissionToDeleteTask          = $user->hasRight('project', 'supprimer') || $user->hasRight('project', 'all', 'supprimer');
$permissionToManageTaskTimeSpent = $user->hasRight('project', 'time');

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // The whole task lifecycle is already served by this template, the action plan speaks to it with
    // the very same requests the answers screen sends
    require_once __DIR__ . '/../../core/tpl/digiquali_answers_task_action.tpl.php';
}

/*
 * View
 */

$title   = $langs->trans('ActionPlan');
$helpUrl = '';

// The right column of the page is a table-cell that grows with its content : a wide Gantt would stretch
// the whole page instead of scrolling. This body class is what the theme provides to bound it
$moreCssOnBody = ($view == 'gantt' ? 'classforhorizontalscrolloftabs' : '');

saturne_header(0, '', $title, $helpUrl, '', 0, 0, [], [], '', $moreCssOnBody);

if ($object->id > 0) {
    saturne_get_fiche_head($object, 'actionplan', $title);
    saturne_banner_tab($object);

    $actions = digiquali_get_control_actions($object);

    // Every question and every assignee the plan holds feeds the filters : filtering on a value no
    // action carries would only ever return nothing
    $questionFilterOptions = [];
    $assigneeFilterOptions = [];
    $verdictFilterOptions  = [];
    foreach ($actions as $actionRow) {
        if (is_object($actionRow['question'])) {
            $questionFilterOptions[$actionRow['question']->id] = $actionRow['question']->ref . ' - ' . $actionRow['question']->label;
        }
        foreach ($actionRow['assignees'] as $assignee) {
            $assigneeFilterOptions[$assignee->id] = dolGetFirstLastname($assignee->firstname, $assignee->lastname);
        }
        if (is_object($actionRow['answer'])) {
            $verdictFilterOptions[$actionRow['answer']->value] = $actionRow['answer']->value;
        }
    }
    asort($questionFilterOptions);
    asort($assigneeFilterOptions);
    asort($verdictFilterOptions);

    // What the plan amounts to is counted before any filter : the figures and the progress bar speak of
    // the whole plan, the table alone narrows down
    $stats = digiquali_get_control_action_stats($actions);

    // Filters apply on the built plan rather than in SQL : an action is spread over a task, a control
    // line and an answer, so no single query holds every criteria
    $actions = digiquali_filter_control_actions($actions, [
        'question' => $searchQuestion,
        'status'   => $searchStatus,
        'verdict'  => $searchVerdict,
        'assignee' => $searchAssignee,
        'text'     => $searchText
    ]);

    $actionPlanEdit           = $permissiontoadd && !empty($permissionToAddTask) && $object->status < Control::STATUS_LOCKED;
    $actionPlanUrl            = $_SERVER['PHP_SELF'];
    $actionPlanFormParameters = ['id' => $object->id];

    if (getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) {
        $publicActionPlanUrl = dol_buildpath('custom/digiquali/public/control/public_control_actionplan.php?track_id=' . $object->track_id . '&entity=' . $conf->entity, 3);
        print '<div class="underbanner clearboth"></div>';
        print '<table class="border centpercent tableforfield"><tr><td class="titlefield">';
        print $langs->trans('PublicActionPlan') . ' <a href="' . $publicActionPlanUrl . '" target="_blank"><i class="fas fa-qrcode"></i></a>';
        print showValueWithClipboardCPButton($publicActionPlanUrl, 0, '&nbsp;');
        print '</td><td>';
        print '<a href="' . $publicActionPlanUrl . '" target="_blank">' . $langs->trans('GoToPublicActionPlanPage') . ' <i class="fa fa-external-link"></i></a>';
        print '</td></tr></table>';
    }

    require_once __DIR__ . '/../../core/tpl/control/control_actionplan_view_switch.tpl.php';

    if ($view == 'gantt') {
        require_once __DIR__ . '/../../core/tpl/control/control_actionplan_gantt.tpl.php';
    } else {
        require_once __DIR__ . '/../../core/tpl/control/control_actionplan_list.tpl.php';
    }

    if ($view != 'gantt' && $actionPlanEdit) {
        require_once __DIR__ . '/../../core/tpl/control/control_actionplan_add_modal.tpl.php';
        require_once __DIR__ . '/../../core/tpl/modal/modal_task_edit.tpl.php';
    }
}

// End of page
llxFooter();
$db->close();
