/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    js/modules/task.js
 * \ingroup digiquali
 * \brief   JavaScript tasks file
 */

'use strict';

/**
 * Init task JS
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 */
window.digiquali.task = {};

/**
 * Task init
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.init = function() {
  window.digiquali.task.event();
};

/**
 * Task event
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.event = function() {
  // Task event
  $(document).on('input', '#answer-task-label', window.digiquali.task.updateModalTaskAddButton);
  $(document).on('click', '.answer-task-create:not(.button-disable)', window.digiquali.task.createTask);
  $(document).on('click', '.answer-task-save', window.digiquali.task.updateTask);
  $(document).on('click', '.question__action .delete-task', window.digiquali.task.deleteTask);
  $(document).on('change', '.question__action-check input[type="checkbox"]', window.digiquali.task.checkTask);

  // Task timespent event
  $(document).on('click', '.answer-task-timespent-create', window.digiquali.task.createTaskTimeSpent);
  $(document).on('click', '.answer-task-timespent-update', window.digiquali.task.updateTaskTimeSpent);
  $(document).on('click', '.answer-task-timespent-delete', window.digiquali.task.deleteTaskTimeSpent);
};

/**
 * Update modal task add button state when input change value
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.updateModalTaskAddButton = function() {
  const $this   = $(this);
  const $modal  = $this.closest('#answer_task_add');
  const $button = $modal.find('.wpeo-button.answer-task-create');
  const value   = $this.val();

  if (value.length > 0) {
    $button.removeClass('button-disable');
  } else {
    $button.addClass('button-disable');
  }
};

/**
 * Adds additional data when opening a modal
 *
 * This function allows passing extra information to a modal
 * when it is opened, based on the triggering element
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @param {String} modalToOpen - The modal element to be opened
 * @param {jQuery} elementFrom - The triggering element from which data is retrieved
 *
 * @return {void}
 */
window.saturne.modal.addMoreOpenModalData = function(modalToOpen, elementFrom) {
  const token = window.saturne.toolbox.getToken();

  const $modalOptions = elementFrom.find('.modal-options');
  const fromId        = $modalOptions.data('from-id');

  let action = 'fetch_task';
  if (modalToOpen.match(/timespent_edit/)) {
    action = 'fetch_task_timespent';
  }
  if (modalToOpen === 'activity_edit' || modalToOpen === 'riskassessment_list') {
    action = 'fetch_activity';
  }
  if (modalToOpen === 'riskassessment_update') {
    action = 'fetch_riskassessment';
  }

  $.ajax({
    url: `${document.URL}&action=${action}&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      from_id: fromId,
    }),
    success: function(resp) {
      $(`#${modalToOpen}`).replaceWith($(resp).find(`#${modalToOpen}`).addClass('modal-active'));
      if (modalToOpen === 'riskassessment_update') {
        window.digiquali.riskAssessment.initializeModalUIState($(`#${modalToOpen}`));
      }
    }
  });
};

/**
 * Create task
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.createTask = function() {
  const token = window.saturne.toolbox.getToken();

  const $this    = $(this);
  const $modal   = $this.closest('#answer_task_add');
  const fromId   = $modal.data('from-id');
  const fromType = $modal.data('from-type');
  const $list    = $(document).find(`#question_task_list${fromId}`);

  const label     = $modal.find('#answer-task-label').val();
  const startDate = $modal.find('#answer-task-start-date').val();
  const endDate   = $modal.find('#answer-task-end-date').val();
  const budget    = $modal.find('#answer-task-budget').val();
  const projectId = $modal.data('project-id');

  $.ajax({
    url: `${document.URL}&action=add_task&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      objectLine_id:      fromId,
      objectLine_element: fromType,
      label:              label,
      date_start:         startDate,
      date_end:           endDate,
      budget_amount:      budget,
      fk_project:         projectId
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#answer_task_add'));
      $list.replaceWith($(resp).find(`#question_task_list${fromId}`));
    }
  });
};

/**
 * Update task
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.updateTask = function() {
  const token  = window.saturne.toolbox.getToken();

  const $this  = $(this);
  const $modal = $this.closest('#answer_task_edit');
  const $form  = $modal.find('.answer-task-content');
  const taskId = $this.data('task-id');
  const $list  = $(document).find(`#answer_task${taskId} .question__action-body`);

  const label     = $form.find('#answer-task-label').val();
  const startDate = $form.find('#answer-task-start-date').val();
  const endDate   = $form.find('#answer-task-end-date').val();
  const budget    = $form.find('#answer-task-budget').val();

  $.ajax({
    url: `${document.URL}&action=update_task&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      task_id:    taskId,
      label:      label,
      date_start: startDate,
      date_end:   endDate,
      budget:     budget
    }),
    success: function(resp) {
      $modal.removeClass('modal-active');
      $list.replaceWith($(resp).find(`#answer_task${taskId} .question__action-body`));
    }
  });
};

/**
 * Delete task
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.deleteTask = function() {
  const token = window.saturne.toolbox.getToken();

  const $this = $(this);
  const $list = $this.closest('.question__list-actions');

  const objectLineId      = $list.data('objectline-id');
  const objectLineElement = $list.data('objectline-element');
  const taskId            = $this.data('task-id');
  const message           = $this.data('message');

  if (!confirm(message)) {
    return;
  }

  $.ajax({
    url: `${document.URL}&action=delete_task&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      objectLine_id:      objectLineId,
      objectLine_element: objectLineElement,
      task_id:            taskId
    }),
    success: function(resp) {
      const questionId = $list.attr('id');
      $list.replaceWith($(resp).find(`#${questionId}`));
    }
  });
};

/**
 * Check task
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.checkTask = function() {
  const token  = window.saturne.toolbox.getToken();

  const $this  = $(this);
  const $task  = $this.closest('.question__action');
  const taskId = $task.data('task-id');

  window.saturne.loader.display($task);
  $.ajax({
    url: `${document.URL}&action=check_task&task_id=${taskId}&token=${token}`,
    type: 'POST',
    success: function(resp) {
      $task.replaceWith($(resp).find(`#answer_task${taskId}`));
    }
  });
};

/**
 * Create task time spent
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.createTaskTimeSpent = function() {
  const token = window.saturne.toolbox.getToken();

  const $this  = $(this);
  const $modal = $this.closest('#answer_task_timespent_add');
  const taskId = $modal.data('task-id');
  const $task  = $(document).find(`#answer_task${taskId}`);

  const comment  = $modal.find('#answer-task-timespent-comment').val();
  const date     = $modal.find('#answer-task-timespent-date').val();
  const duration = $modal.find('#answer-task-timespent-duration').val();

  window.saturne.loader.display($task);
  $.ajax({
    url: `${document.URL}&action=add_task_timespent&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      task_id:  taskId,
      comment:  comment,
      date:     date,
      duration: duration
    }),
    success: function(resp) {
      $task.replaceWith($(resp).find(`#answer_task${taskId}`));
    }
  });
};

/**
 * Update task timespent
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.updateTaskTimeSpent = function() {
  const token = window.saturne.toolbox.getToken();

  const $this           = $(this);
  const $modal          = $this.closest('#answer_task_timespent_edit');
  const taskTimeSpentId = $modal.data('task-timespent-id');
  const $taskTimeSpent  = $(document).find(`#answer_task_timespent_view${taskTimeSpentId}`);

  const comment  = $modal.find('#answer-task-timespent-comment').val();
  const date     = $modal.find('#answer-task-timespent-date').val();
  const duration = $modal.find('#answer-task-timespent-duration').val();

  window.saturne.loader.display($taskTimeSpent);
  $.ajax({
    url: `${document.URL}&action=update_task_timespent&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      task_timespent_id: taskTimeSpentId,
      comment:           comment,
      date:              date,
      duration:          duration
    }),
    success: function(resp) {
      $taskTimeSpent.replaceWith($(resp).find(`#answer_task_timespent_view${taskTimeSpentId}`));
    }
  });
};

/**
 * Delete task time spent
 *
 * @memberof DigiQuali_Task
 *
 * @since   20.2.0
 * @version 20.2.0
 *
 * @return {void}
 */
window.digiquali.task.deleteTaskTimeSpent = function() {
  const token = window.saturne.toolbox.getToken();

  const $this  = $(this);
  const $modal = $this.closest('#answer_task_timespent_list');
  const $list  = $modal.find('.answer-task-timespent-container');
  const taskId = $modal.data('task-id');
  const $task  = $(document).find(`#answer_task${taskId} .question__action-body`);

  const taskTimeSpentId = $this.data('task-timespent-id');
  const message         = $this.data('message');

  if (!confirm(message)) {
    return;
  }

  window.saturne.loader.display($list);
  window.saturne.loader.display($task);
  $.ajax({
    url: `${document.URL}&action=delete_task_timespent&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      task_timespent_id: taskTimeSpentId
    }),
    success: function(resp) {
      $list.replaceWith($(resp).find(`#answer_task_timespent_list[data-task-id="${taskId}"] .answer-task-timespent-container`));
      $task.replaceWith($(resp).find(`#answer_task${taskId} .question__action-body`));
    }
  });
};
