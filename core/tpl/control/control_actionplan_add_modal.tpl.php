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
 * \file    core/tpl/control/control_actionplan_add_modal.tpl.php
 * \ingroup digiquali
 * \brief   Template page for the modal creating an action of the action plan of a control
 */

/**
 * The following vars must be defined :
 * Globals   : $db, $langs
 * Objects   : $object (Control), $form
 * Variables : $taskNextValue
 */

// An action is carried by the answer to a question : the question the user picks is what the action
// gets attached to, so the plan can only be fed from the questions the control actually answers
$actionPlanLine      = new ControlLine($db);
$actionPlanLines     = $actionPlanLine->fetchAll('', '', 0, 0, ['customsql' => 't.fk_control = ' . $object->id . ' AND t.status > 0']);
$actionPlanQuestions = [];

if (is_array($actionPlanLines)) {
    $actionPlanQuestion = new Question($db);
    foreach ($actionPlanLines as $line) {
        if ($actionPlanQuestion->fetch($line->fk_question) > 0) {
            $actionPlanQuestions[$line->id] = $actionPlanQuestion->ref . ' - ' . $actionPlanQuestion->label;
        }
    }
} ?>

<div class="wpeo-modal modal-actionplan-add" id="actionplan_add" data-project-id="<?php echo $object->project->id; ?>">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header -->
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('ActionPlanNewAction') . ' ' . $taskNextValue . ' - ' . $langs->trans('Project') . ' ' . $object->project->getNomUrl(); ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content -->
        <div class="modal-content">
            <div class="actionplan-add-container">
                <label>
                    <span class="title"><?php echo $langs->trans('ActionPlanLinkedQuestion'); ?></span>
                    <?php echo $form->selectarray('actionplan-line', $actionPlanQuestions, '', $langs->trans('ActionPlanSelectQuestion'), 0, 0, '', 0, 0, 0, '', 'minwidth300', 1); ?>
                </label>
                <label>
                    <span class="title"><?php echo $langs->trans('Label'); ?></span>
                    <input type="text" id="actionplan-label" name="label">
                </label>
                <label>
                    <span class="title"><?php echo $langs->trans('AffectedTo'); ?></span>
                    <?php echo $form->select_dolusers('', 'actionplan-assigned-user', 1); ?>
                </label>
                <div class="wpeo-gridlayout grid-3">
                    <div>
                        <label>
                            <span class="title"><?php echo $langs->trans('DateStart'); ?></span>
                            <input type="datetime-local" id="actionplan-start-date" name="date_start" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M'); ?>">
                        </label>
                    </div>
                    <div>
                        <label>
                            <span class="title"><?php echo $langs->trans('Deadline'); ?></span>
                            <input type="datetime-local" id="actionplan-end-date" name="date_end">
                        </label>
                    </div>
                    <div>
                        <label>
                            <span class="title"><?php echo $langs->trans('Budget'); ?></span>
                            <input type="number" id="actionplan-budget" name="budget" min="0">
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal-Footer -->
        <div class="modal-footer">
            <div class="wpeo-button actionplan-create button-disable">
                <i class="fas fa-plus pictofixedwidth"></i><?php echo $langs->trans('Add'); ?>
            </div>
        </div>
    </div>
</div>
