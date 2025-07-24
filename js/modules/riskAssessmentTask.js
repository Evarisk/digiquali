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
 * \file    js/modules/riskAssessmentTask.js
 * \ingroup digiquali
 * \brief   JavaScript risk assessment task file
 */

'use strict';

/**
 * Init risk assessment task JS
 *
 * @since   21.3.0
 * @version 21.3.0
 */
window.digiquali.riskAssessmentTask = {};

/**
 * Risk assessment task init
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessmentTask.init = function init() {
  window.digiquali.riskAssessmentTask.event();
};

/**
 * Risk assessment task event initialization. Binds all necessary event listeners
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessmentTask.event = function initializeEvents() {
  $(document).on('input', '.label', window.digiquali.riskAssessmentTask.updateModalRiskAssessmentTaskButton);

  // Events for create/update risk assessment task
  $(document).on('click', '#riskassessment_task_add', function createRiskAssessmentTask() {
    window.saturne.object.ObjectFromModal.call(this, 'create', 'riskassessment_task');
  });
  $(document).on('click', '#riskassessment_task_edit', function updateRiskAssessmentTask() {
    window.saturne.object.ObjectFromModal.call(this, 'update', 'riskassessment_task');
  });
};

/**
 * Update modal risk assessment task add/update button state for a given modal.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessmentTask.updateModalRiskAssessmentTaskButton = function() {
  const $this   = $(this);
  const $modal  = $this.closest('.modal-riskassessment-task');
  const $button = $modal.find('#riskassessment_task_add'); // Target both, jQuery will find the one that exists

  const labelValue = $modal.find('.label');

  if (labelValue.length > 0) {
    $button.removeClass('button-disable');
  } else {
    $button.addClass('button-disable');
  }
};

