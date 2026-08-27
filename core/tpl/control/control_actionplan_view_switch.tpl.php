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
 * \file    core/tpl/control/control_actionplan_view_switch.tpl.php
 * \ingroup digiquali
 * \brief   Template page for the view switch of the action plan of a control
 */

/**
 * The following vars must be defined :
 * Globals    : $langs
 * Variables  : $actionPlanUrl, $actionPlanFormParameters (optional), $view
 */

// Both views read the same plan : the switch only says which one is drawn, the way the granularity of
// the Gantt is chosen
$actionPlanViews = [
    ''      => ['label' => $langs->trans('ActionPlanViewList'),  'icon' => 'fa-list'],
    'gantt' => ['label' => $langs->trans('ActionPlanViewGantt'), 'icon' => 'fa-stream']
];

print '<div class="actionplan-switch">';
foreach ($actionPlanViews as $viewKey => $actionPlanView) {
    $viewParameters = ($actionPlanFormParameters ?? []) + ($viewKey !== '' ? ['view' => $viewKey] : []);
    $viewUrl        = $actionPlanUrl . (empty($viewParameters) ? '' : '?' . http_build_query($viewParameters));

    print '<a class="' . (($view ?? '') == $viewKey ? 'active' : '') . '" href="' . dol_escape_htmltag($viewUrl) . '">';
    print '<i class="fas ' . $actionPlanView['icon'] . ' pictofixedwidth"></i>' . $actionPlanView['label'];
    print '</a>';
}
print '</div>';
