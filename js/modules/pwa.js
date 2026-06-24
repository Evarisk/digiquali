/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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

'use strict';

/**
 * \file    js/modules/pwa.js
 * \ingroup digiquali
 * \brief   PWA frontend: server-side live search (AJAX) + clickable list cards.
 */

window.digiquali.pwa = {};

/**
 * Debounce timer handle for the live search.
 */
window.digiquali.pwa.searchTimer = null;

/**
 * PWA init
 *
 * @return {void}
 */
window.digiquali.pwa.init = function init() {
  window.digiquali.pwa.event();
};

/**
 * PWA event initialization.
 *
 * @return {void}
 */
window.digiquali.pwa.event = function event() {
  $(document).on('input', '[data-pwa-search]', window.digiquali.pwa.onSearchInput);
  $(document).on('submit', '.pwa-search', window.digiquali.pwa.onSearchSubmit);
  $(document).on('click', '.pwa-card[data-pwa-href]', window.digiquali.pwa.openCard);
};

/**
 * Debounced handler: trigger the server search shortly after the user stops typing.
 *
 * @return {void}
 */
window.digiquali.pwa.onSearchInput = function onSearchInput() {
  let $input = $(this);
  clearTimeout(window.digiquali.pwa.searchTimer);
  window.digiquali.pwa.searchTimer = setTimeout(function() {
    window.digiquali.pwa.runSearch($input);
  }, 300);
};

/**
 * Prevent the search form from reloading the page (search is done via AJAX).
 *
 * @param  {Event} event Submit event
 * @return {void}
 */
window.digiquali.pwa.onSearchSubmit = function onSearchSubmit(event) {
  event.preventDefault();
  clearTimeout(window.digiquali.pwa.searchTimer);
  window.digiquali.pwa.runSearch($(this).find('[data-pwa-search]').first());
};

/**
 * Run the server-side search and replace the list with the returned cards.
 *
 * @param  {jQuery} $input The search input
 * @return {void}
 */
window.digiquali.pwa.runSearch = function runSearch($input) {
  if (!$input || !$input.length) {
    return;
  }

  let $list = $input.closest('.pwa-container').find('[data-pwa-list]');
  if (!$list.length) {
    return;
  }

  $list.addClass('pwa-list-loading');

  $.ajax({
    url: $input.attr('data-pwa-url'),
    method: 'GET',
    dataType: 'html',
    data: {
      object_type: $input.attr('data-pwa-object'),
      search: $input.val()
    }
  }).done(function(html) {
    $list.html(html);
  }).always(function() {
    $list.removeClass('pwa-list-loading');
  });
};

/**
 * Open the object card when clicking anywhere on the card, except on an inner link.
 *
 * @param  {Event} event Click event
 * @return {void}
 */
window.digiquali.pwa.openCard = function openCard(event) {
  if ($(event.target).closest('a').length) {
    return;
  }
  window.location.href = $(this).attr('data-pwa-href');
};
