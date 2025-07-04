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
 * \file    js/modules/risk.js
 * \ingroup digiquali
 * \brief   JavaScript risk file
 */

'use strict';

/**
 * Init risk JS
 *
 * @since   21.3.0
 * @version 21.3.0
 */
window.digiquali.risk = {};

/**
 * Risk init
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.init = function init() {
  window.digiquali.risk.event();
};

/**
 * Risk event initialization. Binds all necessary event listeners.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.event = function initializeEvents() {
  // Event for the textarea input to enable/disable the add button
  $(document).on('input', '#myTextareadsf', window.digiquali.risk.updateModalRiskAddButton);

  // Event for gravity button clicks
  $(document).on('click', '.gravity-button', window.digiquali.risk.updateGravityPercentage);
  // Event for manual input into the gravity percentage field
  $(document).on('change', '#gravity-percentage-input', window.digiquali.risk.updateGravityPercentage);


  // Event for frequency button clicks
  $(document).on('click', '.frequency-button', window.digiquali.risk.updateFrequencyPercentage);
  // Event for manual input into the frequency percentage field
  $(document).on('change', '#frequency-percentage-input', window.digiquali.risk.updateFrequencyPercentage);


  // Event for control slider input change
  $(document).on('input', '#control-slider', window.digiquali.risk.updateControlPercentage);
  // Event for manual input into the control percentage field
  $(document).on('change', '#control-percentage-input', window.digiquali.risk.updateControlPercentage);


  // Event for manual input into the control percentage field (if allowed)
  $(document).on('change', '#risk-control-input', function() {
    // Ensure the slider matches the input if user types
    const val = parseFloat($(this).val());
    if (!isNaN(val) && val >= 0 && val <= 100) {
      $('#control-slider').val(val);
    }
    window.digiquali.risk.updateControlPercentage.call(this); // Call the main handler
  });

  // Events for create/update buttons
  $(document).on('click', '#risk_create', window.digiquali.risk.createRisk);
  $(document).on('click', '#risk_update', window.digiquali.risk.updateRisk);
};

// /**
//  * Update modal task add button state when input change value
//  *
//  * @since   21.3.0
//  * @version 21.3.0
//  *
//  * @return {void}
//  */
// window.digiquali.risk.updateModalRiskAddButton = function updateModalRiskAddButton() {
//   const $this   = $(this);
//   const $modal  = $this.closest('#risk_add');
//   const $button = $modal.find('#risk_create');
//   const value   = $this.val();
//
//   if (value.length > 0) {
//     $button.removeClass('button-disable');
//   } else {
//     $button.addClass('button-disable');
//   }
// };

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
window.digiquali.risk.updateGravityPercentage = function updateGravityPercentage() {
  const $this = $(this);

  $this.closest('.gravity-buttons').find('.gravity-button').removeClass('selected');
  $this.addClass('selected');
  const percentageValue = $this.data('gravity-value');
  $('#gravity-percentage-input').val(percentageValue);

  // Re-calculate and display risks
  window.digiquali.risk.calculateAndDisplayRisks();

  // Call the updateModalRiskAddButton to re-evaluate the state of the create button
  window.digiquali.risk.updateModalRiskAddButton();
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
window.digiquali.risk.updateFrequencyPercentage = function updateFrequencyPercentage() {
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
  window.digiquali.risk.calculateAndDisplayRisks();

  // Call the updateModalRiskAddButton to re-evaluate the state of the create button
  window.digiquali.risk.updateModalRiskAddButton();
};

/**
 * Handles the change of the control slider and updates its percentage input.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.updateControlPercentage = function updateControlPercentage() {
  const $this        = $(this);
  const controlValue = $this.val();

  // Update the number input field with the slider's value
  $('#control-percentage-input').val(controlValue);

  // Re-calculate and display risks
  window.digiquali.risk.calculateAndDisplayRisks();

  // Call the updateModalRiskAddButton to re-evaluate the state of the create button
  window.digiquali.risk.updateModalRiskAddButton();
};


/**
 * Calculates and displays the "Risk" and "Residual risk" values
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.calculateAndDisplayRisks = function calculateAndDisplayRisks() {
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
 * Update modal risk add button state when relevant inputs/selections change
 *
 * This function should encapsulate the logic to determine if the #risk_create button
 * should be enabled or disabled based on the current state of the form
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.updateModalRiskAddButton = function updateModalRiskAddButton() {
  const $modal  = $('#risk_add');
  const $button = $modal.find('#risk_create');

  const gravityValue   = parseFloat($('#gravity-percentage-input').val());
  const frequencyValue = parseFloat($('#frequency-percentage-input').val());
  const controlValue   = parseFloat($('#control-percentage-input').val());

  // Assuming #description is still relevant for enabling the button
  const descriptionValue = $('#description').val();
  const descriptionHasContent = descriptionValue && descriptionValue.trim().length > 0;

  // Check if all necessary values are valid numbers and within a reasonable range (0-100)
  // Also, ensure a button is selected for gravity and frequency OR that values are present
  const isGravityValid  = !isNaN(gravityValue) && gravityValue >= 0 && gravityValue <= 100;
  const isFrequencyValid = !isNaN(frequencyValue) && frequencyValue >= 0 && frequencyValue <= 100;
  const isControlValid   = !isNaN(controlValue) && controlValue >= 0 && controlValue <= 100;

  // Enable button if all core inputs are valid and textarea has content
  if (isGravityValid && isFrequencyValid && isControlValid && descriptionHasContent) {
    $button.removeClass('button-disable').prop('disabled', false);
  } else {
    $button.addClass('button-disable').prop('disabled', true);
  }
};


/**
 * Create risk
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.createRisk = function createRisk() {
  const token = window.saturne.toolbox.getToken();

  const $this    = $(this);
  const $modal   = $this.closest('#risk_add');
  const fromId   = $modal.data('from-id');
  const fromType = $modal.data('from-type');
  const $list    = $(document).find(`#risk_list_container_${fromId}`);

  const description = $modal.find('#description').val();
  const gravity     = $modal.find('#gravity-percentage-input').val();
  const frequency   = $modal.find('#frequency-percentage-input').val();
  const control     = $modal.find('#control-percentage-input').val();

  window.saturne.loader.display($list);

  $.ajax({
    url: `${document.URL}&action=add_risk&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      objectLine_id:        fromId,
      objectLine_element:   fromType,
      description:          description,
      gravity_percentage:   gravity,
      frequency_percentage: frequency,
      control_percentage:   control,
      token:                token
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#risk_add'));
      $list.replaceWith($(resp).find(`#risk_list_container_${fromId}`));
    }
  });
};

/**
 * Update risk
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.updateRisk = function updateRisk() {
  const token = window.saturne.toolbox.getToken();

  const $this    = $(this);
  const $modal   = $this.closest('#risk_edit');
  const fromId   = $modal.data('from-id');
  const fromType = $modal.data('from-type');
  const $list    = $(document).find(`#risk-list-container`);

  const label = $modal.find('#myTextareadsf').val();

  window.saturne.loader.display($list);

  $.ajax({
    url: `${document.URL}&action=update_risk&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      object_id:      fromId,
      object_element: fromType,
      label: label
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#risk_edit'));
      $list.replaceWith($(resp).find(`#risk-list-container`));
    }
  });
};
