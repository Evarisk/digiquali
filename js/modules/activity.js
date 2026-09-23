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
 * \file    js/modules/activity.js
 * \ingroup digiquali
 * \brief   JavaScript activity file
 */

'use strict';

/**
 * Init activity JS
 *
 * @since   21.3.0
 * @version 21.3.0
 */
window.digiquali.activity = {};

/**
 * Activity init
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.activity.init = function init() {
  window.digiquali.activity.event();
};

/**
 * Activity event initialization. Binds all necessary event listeners
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.activity.event = function initializeEvents() {
  $(document).on('input', '#label', window.digiquali.activity.updateModalActivityButton);
  $(document).on('blur', '.activity-list-container [contenteditable="true"]', window.digiquali.activity.updateContentEditable);
  $(document).on('keydown', '.activity-list-container [contenteditable="true"]', window.digiquali.activity.preventLineBreak);

  // Events for create activity
  $(document).on('click', '#activity_add', function createActivity() {
    window.saturne.object.ObjectFromModal.call(this, 'create', 'activity');
  });
};

/**
 * Update modal activity button state when input change value
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.activity.updateModalActivityButton = function() {
  const $this   = $(this);
  const $modal  = $this.closest('#activity_create');
  const $button = $modal.find('#activity_add');
  const value   = $this.val();

  if (value.length > 0) {
    $button.removeClass('button-disable');
  } else {
    $button.addClass('button-disable');
  }
};

/**
 * Prevent line breaks in editable badges: Enter exits the field (which triggers the save on blur)
 * instead of inserting a new line that would break the badge layout.
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @param  {Event} e - The keydown event
 * @return {void}
 */
window.digiquali.activity.preventLineBreak = function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    $(this).blur();
  }
};

/**
 * Toggles a configuration setting based on a button state and updates the UI dynamically
 *
 * This function is used to send an AJAX request to toggle a specific setting and dynamically
 * update the relevant UI elements based on the response
 *
 * @memberof Saturne_Utils
 *
 * @since   1.8.0
 * @version 1.8.0
 *
 * @param {string} action  - The action name to send in the AJAX request
 * @param {string} dataKey - The key name for the data payload
 */
window.digiquali.activity.updateContentEditable = function() {
  const querySeparator  = window.saturne.toolbox.getQuerySeparator(document.URL);
  const token           = window.saturne.toolbox.getToken();
  const $content        = $(this);
  const $container      = $(this).closest('.activity-container');

  const objectId    = $container.data('object-id');
  const field       = $content.data('field');
  const contentText = $content.text();

  $.ajax({
    url: `${document.URL}${querySeparator}&action=update_activity&token=${token}`,
    type: 'POST',
    contentType: 'application/json charset=utf-8',
    data: JSON.stringify({
      object_id: objectId,
      field:     field,
      value:     contentText
    }),
    success: function ( resp ) {
      $.jnotify($(resp).find('#success_message').val(), 'success', true, {autoHide: true, TimeShown: 2000, ShowTimeEffect: 200, HideTimeEffect: 200, HorizontalPosition: 'right', VerticalPosition: 'top', ShowOverlay: false});
    },
  });
};
