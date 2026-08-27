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
 * \file    core/tpl/control/control_actionplan_list.tpl.php
 * \ingroup digiquali
 * \brief   Template page for the action plan of a control, shared by its tab and its public interface
 */

/**
 * The following vars must be defined :
 * Globals    : $conf, $db, $langs, $user
 * Objects    : $object (Control)
 * Variables  : $actions, $stats, $actionPlanUrl, $questionFilterOptions, $assigneeFilterOptions,
 *              $verdictFilterOptions, $searchQuestion, $searchStatus, $searchVerdict, $searchAssignee,
 *              $searchText, $actionPlanEdit (optional), $actionPlanPublic (optional)
 */

$actionPlanEdit   = !empty($actionPlanEdit);
$actionPlanPublic = !empty($actionPlanPublic);

$statusLabels = [
    'done'    => $langs->transnoentities('ActionPlanStatusDone'),
    'ongoing' => $langs->transnoentities('ActionPlanStatusOngoing'),
    'late'    => $langs->transnoentities('ActionPlanStatusLate')
];

print '<div class="control-actionplan">';

print load_fiche_titre($langs->trans('ActionPlanLinkedToControl'), '', '');

// What the plan amounts to, before any filter narrows the table down
print '<div class="actionplan-kpi">';
foreach ([
    'total'   => ['label' => $langs->trans('ActionPlanTotalActions'), 'css' => ''],
    'done'    => ['label' => $statusLabels['done'],                   'css' => 'kpi-done'],
    'ongoing' => ['label' => $statusLabels['ongoing'],                'css' => 'kpi-ongoing'],
    'late'    => ['label' => $statusLabels['late'],                   'css' => 'kpi-late']
] as $kpiKey => $kpi) {
    print '<div class="actionplan-kpi-cell ' . $kpi['css'] . '">';
    print '<span class="actionplan-kpi-value">' . $stats[$kpiKey] . '</span>';
    print '<span class="actionplan-kpi-label">' . $kpi['label'] . '</span>';
    print '</div>';
}
print '</div>';

// Filters. A GET form keeps a filtered plan shareable and reloadable, which a POST one would not
print '<form method="GET" action="' . dol_escape_htmltag($actionPlanUrl) . '" class="actionplan-filters">';
foreach ($actionPlanFormParameters ?? [] as $parameterKey => $parameterValue) {
    print '<input type="hidden" name="' . dol_escape_htmltag($parameterKey) . '" value="' . dol_escape_htmltag($parameterValue) . '">';
}

print $form->selectarray('search_question', $questionFilterOptions, $searchQuestion, $langs->trans('ActionPlanAllQuestions'), 0, 0, '', 0, 0, 0, '', 'maxwidth200', 1);
print $form->selectarray('search_status', $statusLabels, $searchStatus, $langs->trans('ActionPlanAllStatus'), 0, 0, '', 0, 0, 0, '', 'maxwidth150', 1);
print $form->selectarray('search_verdict', $verdictFilterOptions, $searchVerdict, $langs->trans('ActionPlanAllVerdicts'), 0, 0, '', 0, 0, 0, '', 'maxwidth150', 1);
print $form->selectarray('search_assignee', $assigneeFilterOptions, $searchAssignee, $langs->trans('ActionPlanAllAssignees'), 0, 0, '', 0, 0, 0, '', 'maxwidth200', 1);
print '<input type="text" name="search_text" class="actionplan-search" value="' . dol_escape_htmltag($searchText) . '" placeholder="' . dol_escape_htmltag($langs->trans('Search')) . '">';

// Icon buttons, the way a list filter row does it : the only button worth weight here is the one
// creating an action
print '<button type="submit" class="actionplan-filter-button" title="' . dol_escape_htmltag($langs->trans('Search')) . '"><i class="fas fa-search"></i></button>';

$actionPlanFiltered = ($searchQuestion > 0 || $searchStatus !== '' || $searchVerdict !== '' || $searchAssignee > 0 || $searchText !== '');
if ($actionPlanFiltered) {
    $actionPlanResetUrl = $actionPlanUrl . (empty($actionPlanFormParameters) ? '' : '?' . http_build_query($actionPlanFormParameters));
    print '<a class="actionplan-filter-button actionplan-filter-reset" href="' . dol_escape_htmltag($actionPlanResetUrl) . '" title="' . dol_escape_htmltag($langs->trans('RemoveFilter')) . '"><i class="fas fa-eraser"></i></a>';
}

if ($actionPlanEdit) {
    // The saturne modal opener reads what to open from a .modal-options child, not from the trigger itself
    print '<span class="wpeo-button button-blue actionplan-add modal-open">';
    print '<input type="hidden" class="modal-options" data-modal-to-open="actionplan_add">';
    print '<i class="fas fa-plus pictofixedwidth"></i>' . $langs->trans('ActionPlanNewAction');
    print '</span>';
}
print '</form>';

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable nobottomiftotal noborder liste centpercent">';

print '<tr class="liste_titre">';
print '<th class="liste_titre">' . $langs->trans('Ref') . '</th>';
print '<th class="liste_titre">' . $langs->trans('ActionPlanLinkedQuestion') . '</th>';
print '<th class="liste_titre">' . $langs->trans('ActionPlanAction') . '</th>';
print '<th class="liste_titre">' . $langs->trans('ActionPlanAssignee') . '</th>';
print '<th class="liste_titre center">' . $langs->trans('Deadline') . '</th>';
// The budget is a figure of the house : it stays out of the interface anyone holding the link can open
if (!$actionPlanPublic) {
    print '<th class="liste_titre right">' . $langs->trans('Budget') . '</th>';
}
print '<th class="liste_titre center">' . $langs->trans('Status') . '</th>';
print '<th class="liste_titre center">' . $langs->trans('ActionPlanVerdict') . '</th>';
if ($actionPlanEdit) {
    print '<th class="liste_titre center">' . $langs->trans('ActionPlanRowActions') . '</th>';
}
print '</tr>';

$actionPlanColumnCount = 7 + ($actionPlanPublic ? 0 : 1) + ($actionPlanEdit ? 1 : 0);

if (empty($actions)) {
    print '<tr class="oddeven"><td colspan="' . $actionPlanColumnCount . '"><span class="opacitymedium">' . $langs->trans('ActionPlanNoAction') . '</span></td></tr>';
}

foreach ($actions as $actionRow) {
    $actionTask   = $actionRow['task'];
    $actionStatus = digiquali_get_control_action_status($actionTask);

    // The row carries the id the saturne task modal refreshes after an edit, which spares the page a reload
    print '<tr class="oddeven" id="answer_task' . $actionTask->id . '">';

    print '<td class="nowraponall">' . ($actionPlanPublic ? dol_escape_htmltag($actionTask->ref) : $actionTask->getNomUrl(1)) . '</td>';

    print '<td class="tdoverflowmax200">';
    if (is_object($actionRow['question'])) {
        print ($actionPlanPublic ? dol_escape_htmltag($actionRow['question']->ref) : $actionRow['question']->getNomUrl(1));
        print '<br><span class="opacitymedium">' . dol_escape_htmltag($actionRow['question']->label) . '</span>';
    }
    print '</td>';

    print '<td>';
    print dol_escape_htmltag($actionTask->label);
    if (!empty($actionTask->description)) {
        print '<br><span class="opacitymedium">' . dol_escape_htmltag(dol_trunc($actionTask->description, 120)) . '</span>';
    }
    print '</td>';

    print '<td class="tdoverflowmax200">';
    foreach ($actionRow['assignees'] as $assignee) {
        $assigneeName = dolGetFirstLastname($assignee->firstname, $assignee->lastname);
        print '<span class="actionplan-assignee">';
        print '<span class="actionplan-assignee-initials">' . dol_escape_htmltag(dol_strtoupper(dol_substr($assignee->firstname, 0, 1) . dol_substr($assignee->lastname, 0, 1))) . '</span>';
        print ($actionPlanPublic ? dol_escape_htmltag($assigneeName) : $assignee->getNomUrl(0));
        print '</span>';
    }
    print '</td>';

    print '<td class="center nowraponall' . ($actionStatus == 'late' ? ' actionplan-deadline-late' : '') . '">';
    print !empty($actionTask->date_end) ? dol_print_date($actionTask->date_end, 'day') : '';
    print '</td>';

    if (!$actionPlanPublic) {
        print '<td class="right nowraponall">';
        print (is_numeric($actionTask->budget_amount) ? price($actionTask->budget_amount, 0, $langs, 1, -1, -1, $conf->currency) : '');
        print '</td>';
    }

    print '<td class="center"><span class="actionplan-status status-' . $actionStatus . '">' . $statusLabels[$actionStatus] . '</span></td>';

    print '<td class="center">';
    if (is_object($actionRow['answer'])) {
        // The colour belongs to the answer as it was set up in the sheet, so it cannot live in a stylesheet
        $answerColor = !empty($actionRow['answer']->color) ? $actionRow['answer']->color : '#999999';
        print '<span class="actionplan-verdict" style="background-color: ' . dol_escape_htmltag($answerColor) . ';">' . dol_escape_htmltag($actionRow['answer']->value) . '</span>';
    }
    print '</td>';

    if ($actionPlanEdit) {
        print '<td class="center nowraponall">';
        // data-from-module makes the opener fetch the task first, which is what fills the edit modal
        print '<span class="actionplan-action modal-open" title="' . dol_escape_htmltag($langs->trans('Modify')) . '">';
        print '<input type="hidden" class="modal-options" data-modal-to-open="answer_task_edit" data-from-id="' . $actionTask->id . '" data-from-module="' . dol_escape_htmltag($object->module) . '">';
        print '<i class="fas fa-pencil-alt"></i></span>';
        print '<a class="actionplan-action" href="' . dol_escape_htmltag(DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $actionTask->id) . '" title="' . dol_escape_htmltag($langs->trans('ActionPlanOpenTask')) . '"><i class="fas fa-eye"></i></a>';
        print '<span class="actionplan-action actionplan-action-delete" data-task-id="' . $actionTask->id . '" data-line-id="' . $actionRow['line']->id . '" data-message="' . dol_escape_htmltag($langs->trans('ActionPlanConfirmDeleteAction')) . '" title="' . dol_escape_htmltag($langs->trans('Delete')) . '"><i class="fas fa-trash"></i></span>';
        print '</td>';
    }

    print '</tr>';
}

print '</table>';
print '</div>';

// How far the plan has gone, on every action it holds and not only on the filtered ones
print '<div class="actionplan-progress">';
print '<span class="actionplan-progress-label">' . $langs->trans('ControlActionPlanGlobalProgress') . '</span>';
print '<span class="actionplan-progress-bar"><span class="actionplan-progress-done" style="width: ' . $stats['progress'] . '%;"></span></span>';
print '<span class="actionplan-progress-value">' . $stats['progress'] . '% (' . $stats['done'] . '/' . $stats['total'] . ')</span>';
print '</div>';

print '</div>';
