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
 * \file    core/tpl/modal/modal_task_edit.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal task edit
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Objects  : $object, $task, $form
 */

$taskInfos = get_task_infos($task); ?>

<div class="wpeo-modal modal-answer-task-edit" id="answer_task_edit" data-task-id="<?php echo $task->id ?>">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header -->
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('TaskEdit') . ' ' . $task->getNomUrl() . ' ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $object->project->getNomUrl(); ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content -->
        <div class="modal-content answer-task-content">
            <div>
                <span class="answer-task-reference"><?php echo $taskInfos['task']['ref']; ?></span>
                <span class="answer-task-author"><?php echo $taskInfos['task']['author']; ?></span>
                <span class="answer-task-date"><i class="fas fa-calendar-alt pictofixedwidth"></i><?php echo $taskInfos['task']['date']; ?></span>
                <span class="answer-total-task-timespent"><i class="fas fa-clock pictofixedwidth"></i><?php echo $taskInfos['task']['time']; ?></span>
                <span><i class="fas fa-coins pictofixedwidth"></i><?php echo $taskInfos['task']['budget']; ?></span>
            </div>
            <div class="answer-task-content">
                <div class="answer-task-title">
                    <label>
                        <span class="title"><?php echo $langs->trans('Label'); ?></span>
                        <input type="text" id="answer-task-label" name="label" value="<?php echo $task->label; ?>">
                    </label>
                </div>
                <div class="answer-task-affected">
                    <label>
                        <span class="title"><?php echo $langs->trans('AffectedTo'); ?></span>
                        <?php echo $form->select_dolusers($taskInfos['task']['assigned_user_id'], 'answer-task-edit-assigned-user', 1); ?>
                    </label>
                </div>
                <div class="answer-task-date wpeo-gridlayout grid-3">
                    <div>
                        <label>
                            <span class="title"><?php echo $langs->trans('DateStart'); ?></span>
                            <?php print '<input type="datetime-local" id="answer-task-start-date" name="date_start" value="' . (!empty($task->date_start) ? dol_print_date($task->date_start, '%Y-%m-%dT%H:%M') : '') . '">'; ?>
                        </label>
                    </div>
                    <div>
                        <label>
                            <span class="title"><?php echo $langs->trans('Deadline'); ?></span>
                            <?php print '<input type="datetime-local" id="answer-task-end-date" name="date_end" value="' . (!empty($task->date_end) ? dol_print_date($task->date_end, '%Y-%m-%dT%H:%M') : '') . '">'; ?>
                        </label>
                    </div>
                    <div>
                        <label>
                            <span class="title"><?php echo $langs->trans('Budget'); ?></span>
                            <input type="number" id="answer-task-budget" name="budget" min="0" value="<?php echo $task->budget_amount; ?>">
                        </label>
                    </div>
                </div>
                <div class="answer-task-progress-field">
                    <label>
                        <span class="title"><?php echo $langs->trans('Progress'); ?></span>
                        <div class="answer-task-progress-control">
                            <input type="range" id="answer-task-progress" class="range" name="progress" min="0" max="100" step="1" value="<?php echo (int) $task->progress; ?>">
                            <span class="task-progress-value"><?php echo (int) $task->progress; ?> %</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        <!-- Modal-Footer -->
        <div class="modal-footer">
            <div class="wpeo-button answer-task-save button-green" data-task-id="<?php echo $task->id ?>">
                <i class="fas fa-save pictofixedwidth"></i><?php echo $langs->trans('UpdateData'); ?>
            </div>
        </div>
    </div>
</div>
