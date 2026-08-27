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
 * \file    core/tpl/control/control_actionplan_gantt.tpl.php
 * \ingroup digiquali
 * \brief   Template page drawing the action plan of a control as a Gantt chart
 */

/**
 * The following vars must be defined :
 * Globals    : $langs
 * Variables  : $actions, $actionPlanUrl, $actionPlanFormParameters (optional), $actionPlanGranularity
 */

$granularities = [
    'day'   => $langs->trans('ActionPlanGanttDay'),
    'week'  => $langs->trans('ActionPlanGanttWeek'),
    'month' => $langs->trans('ActionPlanGanttMonth')
];
if (!array_key_exists($actionPlanGranularity, $granularities)) {
    $actionPlanGranularity = 'month';
}

// An action only reaches the chart once it is placed in time. A single date is enough : the missing end
// is read as the start, so a dated action always draws a bar rather than disappearing
$scheduled   = [];
$unscheduled = [];
foreach ($actions as $actionRow) {
    $actionTask = $actionRow['task'];
    $startDate  = !empty($actionTask->date_start) ? $actionTask->date_start : $actionTask->date_end;
    $endDate    = !empty($actionTask->date_end) ? $actionTask->date_end : $actionTask->date_start;

    if (empty($startDate) && empty($endDate)) {
        $unscheduled[] = $actionRow;
        continue;
    }

    $scheduled[] = $actionRow + ['start' => min($startDate, $endDate), 'end' => max($startDate, $endDate)];
}

print '<div class="actionplan-gantt">';

print '<div class="actionplan-gantt-toolbar">';
print '<div class="actionplan-gantt-granularity">';
print '<span class="paddingrightonly">' . $langs->trans('ActionPlanGanttGranularity') . ' :</span>';
foreach ($granularities as $granularityKey => $granularityLabel) {
    $granularityUrl = $actionPlanUrl . '?' . http_build_query(($actionPlanFormParameters ?? []) + ['view' => 'gantt', 'granularity' => $granularityKey]);
    print '<a class="' . ($actionPlanGranularity == $granularityKey ? 'active' : '') . '" href="' . dol_escape_htmltag($granularityUrl) . '">' . $granularityLabel . '</a>';
}
print '</div>';

if (!empty($scheduled)) {
    $rangeStart = min(array_column($scheduled, 'start'));
    $rangeEnd   = max(array_column($scheduled, 'end'));

    print '<div class="actionplan-gantt-period">';
    print $langs->trans('ActionPlanGanttPeriod') . ' : <strong>' . dol_print_date($rangeStart, 'day') . ' &rarr; ' . dol_print_date($rangeEnd, 'day') . '</strong>';
    print ' &middot; ' . $langs->trans('ActionPlanCount', count($scheduled));
    print '</div>';
}
print '</div>';

if (empty($scheduled)) {
    print '<div class="opacitymedium">' . $langs->trans('ActionPlanNoAction') . '</div>';
} else {
    // The columns are cut on the boundaries of the chosen granularity so the header reads as a calendar,
    // and the bars are placed on the span those columns cover
    $columns = [];
    $cursor  = $rangeStart;

    switch ($actionPlanGranularity) {
        case 'day':
            $cursor = dol_get_first_hour($cursor);
            break;
        case 'week':
            $weekStart = dol_get_first_day_week((int) dol_print_date($cursor, '%d'), (int) dol_print_date($cursor, '%m'), (int) dol_print_date($cursor, '%Y'));
            $cursor    = dol_mktime(0, 0, 0, $weekStart['first_month'], $weekStart['first_day'], $weekStart['first_year']);
            break;
        default:
            $cursor = dol_get_first_day((int) dol_print_date($cursor, '%Y'), (int) dol_print_date($cursor, '%m'));
    }

    $gridStart = $cursor;
    while ($cursor <= $rangeEnd && count($columns) < 400) {
        switch ($actionPlanGranularity) {
            case 'day':
                $next  = dol_time_plus_duree($cursor, 1, 'd');
                $label = dol_print_date($cursor, '%d/%m');
                break;
            case 'week':
                $next  = dol_time_plus_duree($cursor, 7, 'd');
                $label = $langs->trans('ActionPlanGanttWeek') . ' ' . dol_print_date($cursor, '%V');
                break;
            default:
                $next  = dol_time_plus_duree($cursor, 1, 'm');
                $label = dol_print_date($cursor, '%b %Y');
        }
        $columns[] = ['start' => $cursor, 'end' => $next, 'label' => $label];
        $cursor    = $next;
    }

    $gridEnd  = end($columns)['end'];
    $gridSpan = max(1, $gridEnd - $gridStart);
    $now      = dol_now();

    print '<div class="div-table-responsive-no-min">';
    print '<table class="actionplan-gantt-table">';

    print '<tr>';
    print '<th class="actionplan-gantt-head">' . $langs->trans('ActionPlanAction') . '</th>';
    foreach ($columns as $column) {
        print '<th>' . dol_escape_htmltag($column['label']) . '</th>';
    }
    print '</tr>';

    foreach ($scheduled as $actionRow) {
        $actionTask   = $actionRow['task'];
        $actionStatus = digiquali_get_control_action_status($actionTask);

        // A bar covering less than a full column would collapse to nothing, hence the minimum width
        $left  = max(0, min(100, ($actionRow['start'] - $gridStart) * 100 / $gridSpan));
        $width = max(1.5, min(100 - $left, ($actionRow['end'] - $actionRow['start']) * 100 / $gridSpan));

        print '<tr>';
        print '<td class="actionplan-gantt-head actionplan-gantt-label">';
        print '<span class="actionplan-gantt-ref">' . dol_escape_htmltag($actionTask->ref) . (is_object($actionRow['question']) ? ' &middot; ' . dol_escape_htmltag($actionRow['question']->ref) : '') . '</span>';
        print dol_escape_htmltag($actionTask->label);
        print '</td>';

        print '<td class="actionplan-gantt-track" colspan="' . count($columns) . '">';
        // Both offsets are computed from the dates of the action, so they cannot live in a stylesheet
        print '<span class="actionplan-gantt-bar status-' . $actionStatus . '" style="left: ' . round($left, 2) . '%; width: ' . round($width, 2) . '%;" title="' . dol_escape_htmltag($actionTask->label . ' - ' . dol_print_date($actionRow['start'], 'day') . ' > ' . dol_print_date($actionRow['end'], 'day')) . '">';
        print dol_escape_htmltag($actionTask->ref) . ' ' . (int) $actionTask->progress . '%';
        print '</span>';
        if ($now >= $gridStart && $now <= $gridEnd) {
            print '<span class="actionplan-gantt-today" style="left: ' . round(($now - $gridStart) * 100 / $gridSpan, 2) . '%;"></span>';
        }
        print '</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
}

print '<div class="actionplan-gantt-legend">';
foreach (['done' => 'ActionPlanStatusDone', 'ongoing' => 'ActionPlanStatusOngoing', 'late' => 'ActionPlanStatusLate'] as $legendStatus => $legendLabel) {
    print '<span><span class="actionplan-gantt-legend-swatch status-' . $legendStatus . '"></span>' . $langs->trans($legendLabel) . '</span>';
}
print '<span><span class="actionplan-gantt-legend-swatch" style="background: #e05353;"></span>' . $langs->trans('ActionPlanGanttToday') . '</span>';
print '</div>';

if (!empty($unscheduled)) {
    print '<div class="actionplan-gantt-unscheduled opacitymedium">';
    print $langs->trans('ActionPlanGanttNoDate') . ' : ';
    $unscheduledRefs = [];
    foreach ($unscheduled as $actionRow) {
        $unscheduledRefs[] = dol_escape_htmltag($actionRow['task']->ref);
    }
    print implode(', ', $unscheduledRefs);
    print '</div>';
}

print '</div>';
