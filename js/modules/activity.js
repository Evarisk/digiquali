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
 * @memberof DigiQuali_Activity
 *
 * @since   21.3.0
 * @version 21.3.0
 */
window.digiquali.activity = {};

/**
 * Activity init
 *
 * @memberof DigiQuali_Activity
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.activity.init = function() {
  window.digiquali.activity.event();
};

/**
 * Activity event
 *
 * @memberof DigiQuali_Activity
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.activity.event = function() {
  $(document).on('click', '.answer-activity-create:not(.button-disable)', window.digiquali.activity.createActivity);
};

/**
 * Create activity
 *
 * @memberof DigiQuali_Activity
 *
 * @since   21.3.0
 * @version 21.3.0
 *
 * @return {void}
 */
window.digiquali.activity.createActivity = function() {
  const token = window.saturne.toolbox.getToken();

  const $this    = $(this);
  const $modal   = $this.closest('#badge_component');
  const fromId   = $modal.data('from-id');
  const fromType = $modal.data('from-type');
  const $list    = $(document).find(`#activity__list${fromId}`);

  const label = $modal.find('#answer-activity_-label').val();

  $.ajax({
    url: `${document.URL}&action=update_badge_component&token=${token}`,
    type: 'POST',
    data: JSON.stringify({
      objectLine_id:      fromId,
      objectLine_element: fromType,
    }),
    success: function(resp) {
      $modal.replaceWith($(resp).find('#badge_component'));
      $list.replaceWith($(resp).find(`#activity__list${fromId}`));
    }
  });
};
