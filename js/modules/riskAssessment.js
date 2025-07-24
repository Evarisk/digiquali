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
 * \file    js/modules/riskAssessment.js
 * \ingroup digiquali
 * \brief   JavaScript risk assessment file
 */

'use strict';

/**
 * Init risk assessment JS
 *
 * @since   21.3.0
 * @version 21.3.0
 */
window.digiquali.riskAssessment = {};

/**
 * Risk assessment init
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessment.init = function init() {
  window.digiquali.riskAssessment.event();
};

/**
 * Risk assessment event initialization. Binds all necessary event listeners
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessment.event = function initializeEvents() {
  // Event for gravity buttons/inputs
  $(document).on('click', '.gravity-button', window.digiquali.riskAssessment.updateGravityPercentage);
  $(document).on('change', '.gravity-percentage-input', window.digiquali.riskAssessment.updateGravityPercentage);

  // Event for frequency buttons/inputs
  $(document).on('click', '.frequency-button', window.digiquali.riskAssessment.updateFrequencyPercentage);
  $(document).on('change', '.frequency-percentage-input', window.digiquali.riskAssessment.updateFrequencyPercentage);

  // Event for control slider/inputs
  $(document).on('input', '.control-slider', window.digiquali.riskAssessment.updateControlPercentage);
  $(document).on('change', '.control-percentage-input', window.digiquali.riskAssessment.updateControlPercentage);

  // Events for create/update risk assessment
  $(document).on('click', '#riskassessment_add', function createRiskAssessment() {
    window.saturne.object.ObjectFromModal.call(this, 'create', 'riskassessment');
  });
  $(document).on('click', '#riskassessment_edit', function updateRiskAssessment() {
    window.saturne.object.ObjectFromModal.call(this, 'update', 'riskassessment');
  });
};

/**
 * Initializes the UI state for a SPECIFIC modal when it is opened.
 * This should be called whenever a modal (add or edit) becomes visible.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @param {jQuery} $modal The jQuery object of the modal that is being opened.
 * @return {void}
 */
window.digiquali.riskAssessment.initializeModalUIState = function initializeModalUIState($modal) {
  // Trigger initial state for gravity
  $('.gravity-button.selected').each(function() {
    $(this).trigger('click');
  });
  // If no button is selected but input has value, process it
  if ($modal.find('.gravity-percentage-input').val() && $modal.find('.gravity-button.selected').length === 0) {
    $modal.find('.gravity-percentage-input').trigger('change');
  }

  // Trigger initial state for frequency
  $modal.find('.frequency-button.selected').each(function() {
    $(this).trigger('click');
  });
  if ($modal.find('.frequency-percentage-input').val() && $modal.find('.frequency-button.selected').length === 0) {
    $modal.find('.frequency-percentage-input').trigger('change');
  }

  // Trigger initial state for control
  // Ensure the slider and input are in sync and calculations are run
  $modal.find('.control-slider').trigger('input');
  // Also trigger change on input in case slider value didn't fire 'input' or was manually set
  $modal.find('.control-percentage-input').trigger('change');

  // Finally, ensure the add/update button state is correct for this specific modal
  window.digiquali.riskAssessment.updateModalRiskAssessmentButton($modal);
};


/**
 * * Handles the selection of a gravity button or input change and updates related fields.
 *
 * This function should be called as an event handler (e.g., on 'click' or 'change').
 * The 'this' context inside the function will refer to the clicked button or changed input.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessment.updateGravityPercentage = function updateGravityPercentage() {
  const $this  = $(this); // The clicked button OR the input field that triggered this
  const $modal = $this.closest('.modal-riskassessment'); // Find the parent modal of the element that triggered the event

  let percentageValue;
  if ($this.is('button')) {
    percentageValue = $this.data('gravity-value');
    // Remove 'selected' class from all gravity buttons WITHIN THIS MODAL'S gravity-buttons
    $modal.find('.gravity-button').removeClass('selected');
    // Add 'selected' class to the clicked button
    $this.addClass('selected');
  } else if ($this.is('input')) { // This means the call came from the percentage input field
    percentageValue = window.saturne.utils.getSanitizedPercentageValue($this);
    $this.val(percentageValue); // Set the sanitized value back to the input

    // Find the closest button within this modal and select it
    const $buttons = $modal.find('.gravity-button');
    $buttons.removeClass('selected'); // Remove all selections first

    // Find the button with the matching data-gravity-value
    $buttons.each(function() {
      if ($(this).data('gravity-value') === percentageValue) {
        $(this).addClass('selected');
        return false; // Break from .each()
      }
    });
  }

  // Set the value of the gravity percentage input within the current modal
  $modal.find('.gravity-percentage-input').val(percentageValue);

  // Re-calculate and display risks for the current modal
  window.digiquali.riskAssessment.calculateAndDisplayRisks($modal);

  // Call the updateModalRiskButton to re-evaluate the state of the create/update button
  window.digiquali.riskAssessment.updateModalRiskAssessmentButton($modal);
};

/**
 * Handles the selection of a frequency button or input change and updates related fields.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessment.updateFrequencyPercentage = function updateFrequencyPercentage() {
  const $this  = $(this);
  const $modal = $this.closest('.modal-riskassessment');

  let percentageValue;
  if ($this.is('button')) {
    percentageValue = $this.data('frequency-value');
    $modal.find('.frequency-button').removeClass('selected');
    $this.addClass('selected');
  } else if ($this.is('input')) {
    percentageValue = window.saturne.utils.getSanitizedPercentageValue($this);
    $this.val(percentageValue);

    const $buttons = $modal.find('.frequency-button');
    $buttons.removeClass('selected');
    $buttons.each(function() {
      if ($(this).data('frequency-value') === percentageValue) {
        $(this).addClass('selected');
        return false;
      }
    });
  }

  $modal.find('.frequency-percentage-input').val(percentageValue);

  window.digiquali.riskAssessment.calculateAndDisplayRisks($modal);
  window.digiquali.riskAssessment.updateModalRiskAssessmentButton($modal);
};

/**
 * Handles the change of the control slider or its percentage input.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskAssessment.updateControlPercentage = function updateControlPercentage() {
  const $this  = $(this);
  const $modal = $this.closest('.modal-riskassessment');

  let controlValue;
  if ($this.is('.control-slider')) { // If it's the slider
    controlValue = window.saturne.utils.getSanitizedPercentageValue($this);
    $modal.find('.control-percentage-input').val(controlValue); // Update the number input field
  } else if ($this.is('.control-percentage-input')) { // If it's the number input
    controlValue = window.saturne.utils.getSanitizedPercentageValue($this);
    $this.val(controlValue); // Set the sanitized value back
    $modal.find('.control-slider').val(controlValue); // Update the slider
  } else {
    // Fallback for initial state if triggered without 'this' being slider/input
    controlValue = window.saturne.utils.getSanitizedPercentageValue($modal.find('.control-percentage-input'));
    $modal.find('.control-slider').val(controlValue);
    $modal.find('.control-percentage-input').val(controlValue);
  }

  window.digiquali.riskAssessment.calculateAndDisplayRisks($modal);
  window.digiquali.riskAssessment.updateModalRiskAssessmentButton($modal);
};


/**
 * Calculates and displays the "Risk" and "Residual risk" values for a given modal.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @param  {jQuery} $modal The jQuery object of the modal to target.
 * @return {void}
 */
window.digiquali.riskAssessment.calculateAndDisplayRisks = function calculateAndDisplayRisks($modal) {
  // Get values from inputs within the SPECIFIC MODAL.
  const gravity   = window.saturne.utils.getSanitizedPercentageValue($modal.find('.gravity-percentage-input'));
  const frequency = window.saturne.utils.getSanitizedPercentageValue($modal.find('.frequency-percentage-input'));
  const control   = window.saturne.utils.getSanitizedPercentageValue($modal.find('.control-percentage-input'));

  // Perform calculations
  const gravityDecimal   = gravity / 100;
  const frequencyDecimal = frequency / 100;
  const controlDecimal   = control / 100;

  // Risk = Gravity x Frequency
  let risk = gravityDecimal * frequencyDecimal;

  // Residual risk = Risk x (1 - Control)
  // Control is a reduction factor. If control is 80%, it means 80% control, so 20% remains
  let residualRisk = risk * (1 - controlDecimal);

  // Convert back to percentage for display (multiply by 100)
  risk         = (risk * 100).toFixed(2);
  residualRisk = (residualRisk * 100).toFixed(2);

  // Update the display elements within the SPECIFIC MODAL
  const $riskPercentage         = $modal.find('.risk-percentage-value');
  const $residualRiskPercentage = $modal.find('.residual-risk-percentage-value');

  $riskPercentage.text(`${risk}%`);
  $residualRiskPercentage.text(`${residualRisk}%`);

  // Update visual indicators within the SPECIFIC MODAL
  if (risk >= 75) {
    $riskPercentage.removeClass('grey yellow red').addClass('black');
  } else if (risk >= 50) {
    $riskPercentage.removeClass('grey yellow black').addClass('red');
  } else if (risk >= 25) {
    $riskPercentage.removeClass('grey red black').addClass('yellow');
  } else {
    $riskPercentage.removeClass('yellow red black').addClass('grey');
  }

  if (residualRisk >= 75) {
    $residualRiskPercentage.removeClass('grey yellow red').addClass('black');
  } else if (residualRisk >= 50) {
    $residualRiskPercentage.removeClass('grey yellow black').addClass('red');
  } else if (residualRisk >= 25) {
    $residualRiskPercentage.removeClass('grey red black').addClass('yellow');
  } else {
    $residualRiskPercentage.removeClass('yellow red black').addClass('grey');
  }
};


/**
 * Update modal risk assessment add/update button state for a given modal.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @param  {jQuery} $modal The jQuery object of the modal to target.
 * @return {void}
 */
window.digiquali.riskAssessment.updateModalRiskAssessmentButton = function updateModalRiskAssessmentButton($modal) {
  // Determine which button to target based on the modal's ID
  const $button = $modal.find('#riskassessment_add, #riskassessment_edit'); // Target both, jQuery will find the one that exists

  const gravityValue   = window.saturne.utils.getSanitizedPercentageValue($modal.find('.gravity-percentage-input'));
  const frequencyValue = window.saturne.utils.getSanitizedPercentageValue($modal.find('.frequency-percentage-input'));
  const controlValue   = window.saturne.utils.getSanitizedPercentageValue($modal.find('.control-percentage-input'));

  // Check if all necessary values are valid numbers and within a reasonable range (0-100)
  // Also, ensure a button is selected for gravity and frequency OR that values are present
  const isGravityValid   = !isNaN(gravityValue) && gravityValue >= 0 && gravityValue <= 100;
  const isFrequencyValid = !isNaN(frequencyValue) && frequencyValue >= 0 && frequencyValue <= 100;
  const isControlValid   = !isNaN(controlValue) && controlValue >= 0 && controlValue <= 100;

  // Enable button if all core inputs are valid
  if (isGravityValid && isFrequencyValid && isControlValid) {
    $button.removeClass('button-disable').prop('disabled', false);
  } else {
    $button.addClass('button-disable').prop('disabled', true);
  }
};
