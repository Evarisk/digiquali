/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    js/modules/tools.js
 * \ingroup digiquali
 * \brief   JavaScript handling the data-migration import diagnostic console.
 *          Markup: core/tpl/digiquali_import_console.tpl.php — styles: css/scss/page/import-console.scss.
 */

/**
 * Init tools JS
 *
 * @memberof DigiQuali_Tools
 *
 * @type {Object}
 */
window.digiquali.tools = {};

/**
 * Tools init
 *
 * @memberof DigiQuali_Tools
 *
 * @returns {void}
 */
window.digiquali.tools.init = function() {
  window.digiquali.tools.event();
  window.digiquali.tools.autoOpen();
};

/**
 * Tools event
 *
 * @memberof DigiQuali_Tools
 *
 * @returns {void}
 */
window.digiquali.tools.event = function() {
  $(document).on('click', '.digiquali-console-header', window.digiquali.tools.toggleConsole);
  $(document).on('click', '.digiquali-console-copy', window.digiquali.tools.copyConsole);
  $(document).on('click', '.digiquali-console-clear', window.digiquali.tools.clearConsole);
};

/**
 * Open the console automatically when the page carries an import log.
 *
 * @memberof DigiQuali_Tools
 *
 * @returns {void}
 */
window.digiquali.tools.autoOpen = function() {
  var $console = $('#digiquali-console');
  if ($console.length && $console.attr('data-auto-open') === '1' && !$console.hasClass('is-open')) {
    window.digiquali.tools.toggleConsole();
  }
};

/**
 * Toggle the console open/closed and keep the arrow + scroll position in sync.
 *
 * @memberof DigiQuali_Tools
 *
 * @returns {void}
 */
window.digiquali.tools.toggleConsole = function() {
  var $console = $('#digiquali-console');
  var isOpen   = $console.toggleClass('is-open').hasClass('is-open');

  $console.find('.digiquali-console-toggle').html(isOpen ? '&#9660;' : '&#9650;');

  if (isOpen) {
    var body = $console.find('.digiquali-console-body').get(0);
    if (body) {
      body.scrollTop = body.scrollHeight;
    }
  }
};

/**
 * Copy the whole console content to the clipboard.
 *
 * @memberof DigiQuali_Tools
 *
 * @param  {Event} event Click event
 * @returns {void}
 */
window.digiquali.tools.copyConsole = function(event) {
  event.stopPropagation();

  var text = '';
  $('#digiquali-console .digiquali-console-line').each(function() {
    var time = $(this).find('.digiquali-console-time').text();
    var msg  = $(this).find('[class^="digiquali-log-"]').text();
    text += time + ' >_ ' + msg + '\n';
  });

  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(function() {
      var $btn = $(event.target);
      $btn.text('✓');
      setTimeout(function() { $btn.text('Copier'); }, 2000);
    });
  }
};

/**
 * Empty the console body.
 *
 * @memberof DigiQuali_Tools
 *
 * @param  {Event} event Click event
 * @returns {void}
 */
window.digiquali.tools.clearConsole = function(event) {
  event.stopPropagation();
  $('#digiquali-console .digiquali-console-body').empty();
};
