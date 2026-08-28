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
 * \file    js/modules/actionplan.js
 * \ingroup digiquali
 * \brief   JavaScript action plan file
 */

'use strict';

/**
 * Init action plan JS
 *
 * @since   23.0.0
 * @version 23.0.0
 */
window.digiquali.actionPlan = {};

/**
 * Action plan init
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiquali.actionPlan.init = function() {
  window.digiquali.actionPlan.event();
  window.digiquali.actionPlan.centerGantt();
};

/**
 * Bring the Gantt to where the plan stands today
 *
 * A plan spanning years scrolls sideways : opened on its first period, the finer granularities show
 * nothing but empty columns. The chart opens centered on the day marker, or on its first action when
 * the period is entirely past or ahead
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiquali.actionPlan.centerGantt = function() {
  const $container = $('.actionplan-gantt .div-table-responsive-no-min');
  if (!$container.length || $container.get(0).scrollWidth <= $container.get(0).clientWidth) {
    return;
  }

  const $today  = $container.find('.actionplan-gantt-today').first();
  const $target = $today.length ? $today : $container.find('.actionplan-gantt-bar').first();
  if (!$target.length) {
    return;
  }

  const targetLeft = $target.offset().left - $container.offset().left + $container.scrollLeft();
  $container.scrollLeft(Math.max(0, targetLeft - ($container.width() / 2)));
};

/**
 * Action plan event initialization. Binds all necessary event listeners
 *
 * The create and delete flows carry classes of their own : the answers screen binds its own handlers on
 * the task ones, and both would answer the same click
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiquali.actionPlan.event = function() {
  $(document).on('input', '#actionplan-label', window.digiquali.actionPlan.refreshCreateButton);
  $(document).on('change', '[name="actionplan-line"]', window.digiquali.actionPlan.refreshCreateButton);
  $(document).on('click', '.actionplan-create:not(.button-disable)', window.digiquali.actionPlan.createAction);
  $(document).on('click', '.actionplan-action-delete', window.digiquali.actionPlan.deleteAction);
};

/**
 * Enable the create button once the action can be created : it needs a question to hang on and a label
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiquali.actionPlan.refreshCreateButton = function() {
  const $modal  = $(this).closest('#actionplan_add');
  const $button = $modal.find('.wpeo-button.actionplan-create');

  const lineId = $modal.find('[name="actionplan-line"]').val();
  const label  = $modal.find('#actionplan-label').val();

  if (lineId > 0 && label.length > 0) {
    $button.removeClass('button-disable');
  } else {
    $button.addClass('button-disable');
  }
};

/**
 * Create an action of the plan. It is a project task hung on the answer to the chosen question
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiquali.actionPlan.createAction = function() {
  const token  = window.saturne.toolbox.getToken();
  const $modal = $(this).closest('#actionplan_add');

  $.ajax({
    url: `${document.URL}&action=add_task&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      objectLine_id:      $modal.find('[name="actionplan-line"]').val(),
      objectLine_element: 'controldet',
      label:              $modal.find('#actionplan-label').val(),
      date_start:         $modal.find('#actionplan-start-date').val(),
      date_end:           $modal.find('#actionplan-end-date').val(),
      budget_amount:      $modal.find('#actionplan-budget').val(),
      fk_project:         $modal.data('project-id'),
      fk_user_assign:     $modal.find('[name="actionplan-assigned-user"]').val()
    }),
    success: function() {
      // The figures, the progress bar and the tab badge all move with the new action : the page is
      // reloaded rather than patched row by row
      window.location.reload();
    }
  });
};

/**
 * Delete an action of the plan
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiquali.actionPlan.deleteAction = function() {
  const token = window.saturne.toolbox.getToken();
  const $this = $(this);

  if (!confirm($this.data('message'))) {
    return;
  }

  $.ajax({
    url: `${document.URL}&action=delete_task&token=${token}`,
    type: 'POST',
    contentType: 'application/json; charset=utf-8',
    data: JSON.stringify({
      objectLine_id:      $this.data('line-id'),
      objectLine_element: 'controldet',
      task_id:            $this.data('task-id')
    }),
    success: function() {
      window.location.reload();
    }
  });
};
