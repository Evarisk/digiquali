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
 * \file    js/modules/riskassessment.js
 * \ingroup digiquali
 * \brief   JavaScript riskassessment file
 */

'use strict';

/**
 * Init riskassessment JS
 *
 * @since   21.3.0
 * @version 21.3.0
 */
window.digiquali.riskassessment = {};

/**
 * Risk init
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.init = function init() {
  window.digiquali.riskassessment.event();
};

/**
 * Risk event initialization. Binds all necessary event listeners
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.event = function initializeEvents() {
  // Event for gravity button clicks
  $(document).on('click', '.gravity-button', window.digiquali.riskassessment.updateGravityPercentage);
  // Event for manual input into the gravity percentage field
  $(document).on('change', '#gravity-percentage-input', window.digiquali.riskassessment.updateGravityPercentage);


  // Event for frequency button clicks
  $(document).on('click', '.frequency-button', window.digiquali.riskassessment.updateFrequencyPercentage);
  // Event for manual input into the frequency percentage field
  $(document).on('change', '#frequency-percentage-input', window.digiquali.riskassessment.updateFrequencyPercentage);


  // Event for control slider input change
  $(document).on('input', '#control-slider', window.digiquali.riskassessment.updateControlPercentage);
  // Event for manual input into the control percentage field
  $(document).on('change', '#control-percentage-input', window.digiquali.riskassessment.updateControlPercentage);


  // Event for manual input into the control percentage field (if allowed)
  $(document).on('change', '#risk-control-input', function() {
    // Ensure the slider matches the input if user types
    const val = parseFloat($(this).val());
    if (!isNaN(val) && val >= 0 && val <= 100) {
      $('#control-slider').val(val);
    }
    window.digiquali.riskassessment.updateControlPercentage.call(this); // Call the main handler
  });

  // Events for create/update buttons
  $(document).on('click', '#riskassessment_create', window.digiquali.riskassessment.createRiskAssessment);
  $(document).on('click', '#riskassessment_update', window.digiquali.riskassessment.updateRiskAssessment);
};


/**
 * Handles the selection of a gravity button and updates the corresponding percentage input
 *
 * This function should be called as an event handler (e.g., on 'click')
 * The 'this' context inside the function will refer to the clicked button element
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.updateGravityPercentage = function updateGravityPercentage() {
  const $this = $(this);

  $this.closest('.gravity-buttons').find('.gravity-button').removeClass('selected');
  $this.addClass('selected');
  const percentageValue = $this.data('gravity-value');
  $('#gravity-percentage-input').val(percentageValue);

  // Re-calculate and display risks
  window.digiquali.riskassessment.calculateAndDisplayRisks();

  // Call the updateModalRiskAddButton to re-evaluate the state of the create button
  window.digiquali.riskassessment.updateModalRiskAssessmentAddButton();
};

/**
 * Handles the selection of a frequency button and updates the corresponding percentage input.
 *
 * This function should be called as an event handler (e.g., on 'click').
 * The 'this' context inside the function will refer to the clicked button element.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.updateFrequencyPercentage = function updateFrequencyPercentage() {
  const $this    = $(this); // The clicked frequency button OR the input field that triggered this
  const $buttons = $this.closest('.frequency-buttons').find('.frequency-button').removeClass('selected');

  let percentageValue;
  if ($this.is('button')) {
    percentageValue = $this.data('frequency-value');
    $this.addClass('selected');
  } else if ($this.is('input')) { // This means the call came from the percentage input field
    percentageValue = parseFloat($this.val());
    if (isNaN(percentageValue)) {
      percentageValue = 0;
    }

    let foundMatch = false;
    $buttons.each(function() {
      if ($(this).data('frequency-value') === percentageValue) {
        $(this).addClass('selected');
        foundMatch = true;
        return false;
      }
    });

    percentageValue = Math.max(0, Math.min(100, percentageValue));
    $this.val(percentageValue);
  }

  $('#frequency-percentage-input').val(percentageValue);

  // Re-calculate and display risks
  window.digiquali.riskassessment.calculateAndDisplayRisks();

  // Call the updateModalRiskAssessmentAddButton to re-evaluate the state of the create button
  window.digiquali.riskassessment.updateModalRiskAssessmentAddButton();
};

/**
 * Handles the change of the control slider and updates its percentage input.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.updateControlPercentage = function updateControlPercentage() {
  const $this        = $(this);
  const controlValue = $this.val();

  // Update the number input field with the slider's value
  $('#control-percentage-input').val(controlValue);

  // Re-calculate and display risks
  window.digiquali.riskassessment.calculateAndDisplayRisks();

  // Call the updateModalRiskAssessmentAddButton to re-evaluate the state of the create button
  window.digiquali.riskassessment.updateModalRiskAssessmentAddButton();
};


/**
 * Calculates and displays the "Risk" and "Residual risk" values
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.calculateAndDisplayRisks = function calculateAndDisplayRisks() {
  // Get values from inputs. Ensure they are numbers
  const gravity   = parseFloat($('#gravity-percentage-input').val()) || 0;
  const frequency = parseFloat($('#frequency-percentage-input').val()) || 0;
  const control   = parseFloat($('#control-percentage-input').val()) || 0;

  // Perform calculations
  // Convert to decimal for calculation (e.g., 80% -> 0.8)
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


  // Update the display elements
  const $riskPercentage         = $('#risk-percentage-value');
  const $residualRiskPercentage = $('#residual-risk-percentage-value');

  $riskPercentage.text(`${risk}%`);
  $residualRiskPercentage.text(`${residualRisk}%`);

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
 * Update modal risk assessment add button state when relevant inputs/selections change
 *
 * This function should encapsulate the logic to determine if the #riskassessment_create button
 * should be enabled or disabled based on the current state of the form
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.updateModalRiskAssessmentAddButton = function updateModalRiskAssessmentAddButton() {
  const $modal  = $('#riskassessment_add');
  const $button = $modal.find('#riskassessment_create');

  const gravityValue   = parseFloat($('#gravity-percentage-input').val());
  const frequencyValue = parseFloat($('#frequency-percentage-input').val());
  const controlValue   = parseFloat($('#control-percentage-input').val());

  // Check if all necessary values are valid numbers and within a reasonable range (0-100)
  // Also, ensure a button is selected for gravity and frequency OR that values are present
  const isGravityValid   = !isNaN(gravityValue) && gravityValue >= 0 && gravityValue <= 100;
  const isFrequencyValid = !isNaN(frequencyValue) && frequencyValue >= 0 && frequencyValue <= 100;
  const isControlValid   = !isNaN(controlValue) && controlValue >= 0 && controlValue <= 100;

  // Enable button if all core inputs are valid and textarea has content
  if (isGravityValid && isFrequencyValid && isControlValid) {
    $button.removeClass('button-disable').prop('disabled', false);
  } else {
    $button.addClass('button-disable').prop('disabled', true);
  }
};


/**
 * Create risk assessment
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.createRiskAssessment = function createRiskAssessment() {
  const token = window.saturne.toolbox.getToken();

  const $this    = $(this);
  const $modal   = $this.closest('#riskassessment_add');
  const fromId   = $modal.data('from-id');
  const fromType = $modal.data('from-type');
  const $list    = $(document).find(`#riskassessment_list_container_${fromId}`);

  const comment   = $modal.find('#comment').val();
  const gravity   = $modal.find('#gravity-percentage-input').val();
  const frequency = $modal.find('#frequency-percentage-input').val();
  const control   = $modal.find('#control-percentage-input').val();

  window.saturne.loader.display($list);

  $.ajax({
    url: `${document.URL}&action=add_riskassessment&token=${token}`,
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
      objectLine_id:        fromId,
      objectLine_element:   fromType,
      comment:              comment,
      gravity_percentage:   gravity,
      frequency_percentage: frequency,
      control_percentage:   control,
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#riskassessment_add'));
      $list.replaceWith($(resp).find(`#riskassessment_list_container_${fromId}`));
    }
  });
};

/**
 * Update risk assessment
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.riskassessment.updateRiskAssessment = function updateRiskAssessment() {
  const token = window.saturne.toolbox.getToken();

  const $this    = $(this);
  const $modal   = $this.closest('#riskassessment_edit');
  const fromId   = $modal.data('from-id');
  const fromType = $modal.data('from-type');
  const $list    = $(document).find(`#riskassessment-list-container`);

  const label = $modal.find('#myTextareadsf').val();

  window.saturne.loader.display($list);

  $.ajax({
    url: `${document.URL}&action=update_riskassessment&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      object_id:      fromId,
      object_element: fromType,
      label: label
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#riskassessment_edit'));
      $list.replaceWith($(resp).find(`#riskassessment-list-container`));
    }
  });
};
