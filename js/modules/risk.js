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
 * Risk event
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.event = function initializeEvents() {
  $(document).on('input', '#myTextareadsf', window.digiquali.risk.updateModalRiskAddButton);

  $(document).on('click', '#risk_create', window.digiquali.risk.createRisk);
  $(document).on('click', '#risk_update', window.digiquali.risk.updateRisk);
};

/**
 * Update modal task add button state when input change value
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.risk.updateModalRiskAddButton = function updateModalRiskAddButton() {
  const $this   = $(this);
  const $modal  = $this.closest('#risk_add');
  const $button = $modal.find('#risk_create');
  const value   = $this.val();

  if (value.length > 0) {
    $button.removeClass('button-disable');
  } else {
    $button.addClass('button-disable');
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
  const $list    = $(document).find(`#risk-list-container`);

  const label = $modal.find('#myTextareadsf').val();

  window.saturne.loader.display($list);

  $.ajax({
    url: `${document.URL}&action=add_risk&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      objectLine_id:      fromId,
      objectLine_element: fromType,
      label: label,
      token: token
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#risk_add'));
      $list.replaceWith($(resp).find(`#risk-list-container`));
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
