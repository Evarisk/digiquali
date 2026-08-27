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
 * \file    public/control/public_control_actionplan.php
 * \ingroup digiquali
 * \brief   Public page to view the action plan of a control
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOLOGIN')) {     // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) { // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {   // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Better performance by disabling some features not used in this page
if (!defined('DISABLE_CKEDITOR')) {
    define('DISABLE_CKEDITOR', 1);
}
if (!defined('DISABLE_JQUERY_TABLEDND')) {
    define('DISABLE_JQUERY_TABLEDND', 1);
}
if (!defined('DISABLE_JS_GRAPH')) {
    define('DISABLE_JS_GRAPH', 1);
}

// Load DigiQuali environment
if (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} elseif (file_exists('../../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/digiquali_control.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects']);

// Get parameters
$trackId = GETPOST('track_id', 'alpha');
$entity  = GETPOST('entity', 'int');
$action  = GETPOST('action', 'aZ09');
$view    = GETPOST('view', 'aZ09');

// Get filter parameters. The empty entry of a select posts -1, which stands for "no filter at all"
$searchQuestion         = GETPOSTINT('search_question');
$searchStatus           = GETPOST('search_status', 'aZ09');
$searchVerdict          = GETPOST('search_verdict', 'alphanohtml');
$searchAssignee         = GETPOSTINT('search_assignee');
$searchText             = GETPOST('search_text', 'alphanohtml');
$searchStatus           = ($searchStatus == '-1' ? '' : $searchStatus);
$searchVerdict          = ($searchVerdict == '-1' ? '' : $searchVerdict);
$actionPlanGranularity  = GETPOST('granularity', 'aZ09');

// Initialize technical objects
$object = new Control($db);
$user   = new User($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['publiccontrolactionplan', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

// Load user
if (!isset($_SESSION['dol_login'])) {
    $user->loadDefaultValues();
} else {
    $user->fetch('', $_SESSION['dol_login'], '', 1);
    $user->getrights();
}

// Load entity
if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}
$conf->setEntityValues($db, $entity);

/*
 * View
 */

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

$title = $langs->transnoentities('ActionPlan');

saturne_header(0, '', $title, '', '', 0, 0, [], [], '', 'page-public-card');

if (!getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE')) {
    print '<div class="public-card__container">' . saturne_show_notice($langs->transnoentities('PublicActionPlanDisabled'), '', 'error', 'notice-infos', true) . '</div>';
} elseif (empty($trackId) || $object->fetch(0, '', " AND track_id = '" . $db->escape($trackId) . "'") <= 0) {
    print '<div class="public-card__container">' . saturne_show_notice($langs->transnoentities('ErrorRecordNotFound'), '', 'error', 'notice-infos', true) . '</div>';
} else {
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

    $actions = digiquali_filter_control_actions($actions, [
        'question' => $searchQuestion,
        'status'   => $searchStatus,
        'verdict'  => $searchVerdict,
        'assignee' => $searchAssignee,
        'text'     => $searchText
    ]);

    $actionPlanPublic         = 1;
    $actionPlanUrl            = $_SERVER['PHP_SELF'];
    $actionPlanFormParameters = ['track_id' => $trackId, 'entity' => $entity];

    print '<div class="public-card__container">';

    print '<div class="public-card__header">';
    print '<h1>' . $langs->transnoentities('ActionPlan') . '</h1>';
    print '<span class="opacitymedium">' . dol_escape_htmltag($object->ref . ' - ' . $object->label) . '</span>';
    print '</div>';

    // The two views read the same plan, the link swaps which one is drawn
    $listUrl  = $actionPlanUrl . '?' . http_build_query($actionPlanFormParameters);
    $ganttUrl = $actionPlanUrl . '?' . http_build_query($actionPlanFormParameters + ['view' => 'gantt']);

    print '<div class="actionplan-view-switch">';
    if ($view == 'gantt') {
        print '<a class="button" href="' . dol_escape_htmltag($listUrl) . '"><i class="fas fa-arrow-left pictofixedwidth"></i>' . $langs->trans('ActionPlanListView') . '</a>';
    } else {
        print '<a class="button" href="' . dol_escape_htmltag($ganttUrl) . '"><i class="fas fa-stream pictofixedwidth"></i>' . $langs->trans('ActionPlanGanttView') . '</a>';
    }
    print '</div>';

    if ($view == 'gantt') {
        require_once __DIR__ . '/../../core/tpl/control/control_actionplan_gantt.tpl.php';
    } else {
        require_once __DIR__ . '/../../core/tpl/control/control_actionplan_list.tpl.php';
    }

    print '</div>';
}

// End of page
llxFooter();
$db->close();
