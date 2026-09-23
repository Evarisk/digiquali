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
 * \file    core/tpl/answers/answers_task_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers task view
 */

/**
 * The following vars must be defined:
 * Global    : $langs, $object
 * Objects   : $objectLine
 * Variables : $permissionToAddTask, $permissionToDeleteTask, $permissionToManageTaskTimeSpent
 * Optional  : $taskPublicView, set by the public interface when the visitor is not a logged in user
 *             allowed to read projects. What it hides is only ever shown to someone who is.
 */ ?>

<div class="question__list-actions" id="question_task_list<?php echo $objectLine->id ?>" data-objectline-id="<?php echo $objectLine->id ?>" data-objectline-element="<?php echo $objectLine->element ?>">
    <?php
    // Who is on the action, what it costs and the links into the back office are only shown to a visitor
    // who is logged in and allowed to read projects. Anyone else gets the action itself, in plain text
    $taskViewPublic = !empty($taskPublicView);

    foreach ($objectLine->linkedObjects['project_task'] ?? [] as $task) :
        $taskInfos = get_task_infos($task); ?>
        <div class="question__action" id="answer_task<?php echo $task->id ?>" data-task-id="<?php echo $task->id; ?>">
            <div class="question__action-check">
                <label>
                    <input type="checkbox" <?php echo ($taskInfos['task']['progress'] == 100 ? 'checked' : ''); ?> <?php echo (empty($permissionToAddTask) ? 'disabled' : ''); ?>/>
                </label>
            </div>
            <div class="question__action-body">
                <div class="question__action-metas">
                    <span class="question__action-metas-ref"><?php echo $taskViewPublic ? dol_escape_htmltag($task->ref) : $taskInfos['task']['ref']; ?></span>
                    <?php if (!$taskViewPublic) : ?>
                        <span class="question__action-metas-author"><?php echo $taskInfos['task']['author']; ?></span>
                        <?php if (!empty($taskInfos['task']['assigned'])) : ?>
                            <span class="question__action-metas-assigned"><i class="fas fa-user-check pictofixedwidth"></i><?php echo implode(', ', $taskInfos['task']['assigned']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="question__action-metas-date"><i class="fas fa-calendar-alt pictofixedwidth"></i><?php echo $taskInfos['task']['date']; ?></span>
                    <?php if (!$taskViewPublic) : ?>
                        <div class="modal-open">
                            <?php if (!empty($permissionToManageTaskTimeSpent)) : ?>
                                <input type="hidden" class="modal-options" data-modal-to-open="answer_task_timespent_list" data-from-id="<?php echo $task->id; ?>" data-from-module="<?php echo $object->module; ?>">
                            <?php endif; ?>
                            <span class="question__action-metas-time"><i class="fas fa-clock pictofixedwidth"></i><?php echo $taskInfos['task']['time']; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($permissionToManageTaskTimeSpent)) : ?>
                        <div class="modal-open">
                            <input type="hidden" class="modal-options" data-modal-to-open="answer_task_timespent_add" data-from-id="<?php echo $task->id; ?>" data-from-module="<?php echo $object->module; ?>">
                            <i class="fas fa-plus"></i>
                        </div>
                    <?php endif; ?>
                    <?php if (!$taskViewPublic) : // Like the public action plan, an anonymous visitor is not told what the action costs ?>
                        <span class="question__action-metas-budget"><i class="fas fa-coins pictofixedwidth"></i><?php echo $taskInfos['task']['budget']; ?></span>
                    <?php endif; ?>
                    <span class="question__action-metas-progress">
                        <i class="fas fa-tasks pictofixedwidth"></i>
                        <?php if (!empty($permissionToAddTask)) : ?>
                            <input type="range" class="range task-progress-slider" min="0" max="100" step="1" value="<?php echo (int) $taskInfos['task']['progress']; ?>" data-task-id="<?php echo $task->id; ?>">
                        <?php endif; ?>
                        <span class="task-progress-value"><?php echo (int) $taskInfos['task']['progress']; ?> %</span>
                    </span>
                </div>
                <div class="question__action-content"><?php echo $taskInfos['task']['label']; ?></div>
            </div>
            <div class="question__action-buttons">
                <?php if (!empty($permissionToAddTask)) : ?>
                    <div class="wpeo-button button-square-40 button-transparent modal-open">
                        <input type="hidden" class="modal-options" data-modal-to-open="answer_task_edit" data-from-id="<?php echo $task->id; ?>" data-from-module="<?php echo $object->module; ?>">
                        <i class="fas fa-pencil-alt button-icon"></i>
                    </div>
                <?php endif;
                if (!empty($permissionToDeleteTask)) : ?>
                    <div class="wpeo-button button-square-40 button-transparent delete-task" data-message="<?php echo $langs->transnoentities('DeleteTask') . ' ' . $task->ref; ?>" data-task-id="<?php echo $task->id; ?>">
                        <i class="fas fa-trash button-icon"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
