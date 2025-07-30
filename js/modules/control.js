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
 * \file    js/modules/control.js
 * \ingroup digiquali
 * \brief   JavaScript control file
 */

'use strict';

/**
 * Init control JS
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiquali.control = {};

/**
 * Control init
 *
 * @since   1.0.0
 * @version 21.2.0
 *
 * @return {void}
 */
window.digiquali.control.init = function init() {
  window.digiquali.control.event();
};

/**
 * Control event initialization. Binds all necessary event listeners
 *
 * @since   1.0.0
 * @version 21.2.0
 *
 * @return {void}
 */
window.digiquali.control.event = function() {
  $(document).on( 'click', '.validateButton', window.digiquali.control.getAnswerCounter);
  $(document).on( 'change', '#fk_sheet', window.digiquali.control.showSelectObjectLinked);
  $(document).on( 'click', '.clipboard-copy', window.digiquali.control.copyToClipboard);
  $(document).on( 'change', '#productId', window.digiquali.control.refreshLotSelector);
  $(document).on('click', '.switch-public-control-view', window.digiquali.control.switchPublicControlView);

  // Event for sheet categories, sub categories and sheets in view mode pwa in create action
  $(document).on('click', '.photo-sheet-category', window.digiquali.control.getSheetCategoryID);
  $(document).on('click', '.photo-sheet-sub-category', window.digiquali.control.getSheetSubCategoryID);
  $(document).on('click', '.photo-sheet', window.digiquali.control.getSheetID);

  $(document).on('click', '[data-toggle-action]', function() {
    let action = $(this).data('toggle-action');
    let key    = $(this).data('toggle-key');

    if (action && key) {
      window.saturne.utils.toggleSetting.call(this, action, key);
    }
  });
};

/**
 * Get answered questions counter
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.getAnswerCounter = function ( event ) {
	let answerCounter = 0
	jQuery("#tablelines").children().each(function() {
		if ($(this).find(".answer.active").length > 0) {
			answerCounter += 1;
		}
	})
	document.cookie = "answerCounter=" + answerCounter
}

/**
 * Show select objects depending on sheet controllable objects
 *
 * @since   1.0.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.showSelectObjectLinked = function() {
  let sheetID        = $(this).val();
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

  let url = document.URL + querySeparator + 'fk_sheet=' + sheetID + '&token=' + token;

  window.saturne.loader.display($('.linked-objects'));

  $.ajax({
    url: url,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};

/**
 * Copy current link to clipboard
 *
 * @since   1.8.0
 * @version 1.8.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.copyToClipboard = function(  event ) {
	let copyText = $(".copy-to-clipboard").attr('value')
	navigator.clipboard.writeText(copyText).then(() => {
			$('.clipboard-copy').animate({
				backgroundColor: "#59ed9c"
			}, 200, () => {
				$('.clipboard-copy').attr('class', 'fas fa-check  clipboard-copy')
				$(this).tooltip({items : '.clipboard-copy', content: $('#copyToClipboardTooltip').val()});
				$(this).tooltip("open");
				$('.clipboard-copy').attr('style', '')
			})
		}
	)
};

/**
 * Refresh product lot selector
 *
 * @since   1.8.0
 * @version 1.8.0
 *
 * @param  {MouseEvent} event Les attributs lors du clic.
 * @return {void}
 */
window.digiquali.control.refreshLotSelector = function(  event ) {

  var controlEquipmentForm = document.getElementById('add_control_equipment');
  var formData = new FormData(controlEquipmentForm);

  let token = window.saturne.toolbox.getToken();

  let productId = formData.get('productId')
  let urlToGo = document.URL + '&token=' + token
  urlToGo += '&fk_product=' + productId
  window.saturne.loader.display($('.product-lot'))
  $.ajax({
    url: urlToGo,
    type: "POST",
    processData: false,
    contentType: false,
    success: function ( resp ) {
      $('.product-lot').replaceWith($(resp).find('.product-lot'))
    },
    error: function ( ) {
    }
  });
};

/**
 * Switch public control mode
 *
 * @since   20.1.0
 * @version 20.1.0
 *
 * @return {void}
 */
window.digiquali.control.switchPublicControlView = function() {
  const route = $(this).data('route');

  window.saturne.loader.display($(this));

  $.ajax({
    url: document.URL + '&route=' + route,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function (resp) {
      $('.public-card__container').children().fadeOut(300, function () {
        $('#publicControlHistory').replaceWith($(resp).find('#publicControlHistory'));
      });
    },
    error: function () {}
  });
};

/**
 * Get sheet category ID after click event
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.getSheetCategoryID = function() {
  let sheetCategoryID = $(this).attr('value');
  let token           = window.saturne.toolbox.getToken();
  let querySeparator  = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.sheet-images-container'));

  $.ajax({
    url: document.URL + querySeparator + 'sheetCategoryID=' + sheetCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.sheet-images-container').replaceWith($(resp).find('.sheet-images-container'));
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').css('border', '3px solid #0d8aff');
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').addClass('photo-sheet-category-active');
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};

/**
 * Get sheet sub category ID after click event
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.getSheetSubCategoryID = function() {
  let sheetCategoryID    = $('.photo-sheet-category-active').attr('value');
  let sheetSubCategoryID = $(this).attr('value');
  let token              = window.saturne.toolbox.getToken();
  let querySeparator     = window.saturne.toolbox.getQuerySeparator(document.URL);
  window.saturne.loader.display($('.sheet-images-container'));

  $.ajax({
    url: document.URL + querySeparator + 'sheetCategoryID=' + sheetCategoryID + '&sheetSubCategoryID=' + sheetSubCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.sheet-images-container').replaceWith($(resp).find('.sheet-images-container'));
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').css('border', '3px solid #0d8aff');
      $('.photo-sheet-category[value=' + sheetCategoryID + ']').addClass('photo-sheet-category-active');
      $('.photo-sheet-sub-category[value=' + sheetSubCategoryID + ']').css('border', '3px solid #0d8aff');
      $('.photo-sheet-sub-category[value=' + sheetSubCategoryID + ']').addClass('photo-sheet-sub-category-active');
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};

/**
 * Get sheet ID after click event
 *
 * @since   1.10.0
 * @version 1.10.0
 *
 * @return {void}
 */
window.digiquali.control.getSheetID = function() {
  let sheetID            = $(this).attr('data-object-id');
  let sheetCategoryID    = $('.photo-sheet-category-active').attr('value');
  let sheetSubCategoryID = $('.photo-sheet-sub-category-active').attr('value');
  let token              = window.saturne.toolbox.getToken();
  let querySeparator     = window.saturne.toolbox.getQuerySeparator(document.URL);

  window.saturne.loader.display($('.sheet-elements'));
  window.saturne.loader.display($('.linked-objects'));

  $.ajax({
    url: document.URL + querySeparator + 'fk_sheet=' + sheetID + '&sheetCategoryID=' + sheetCategoryID + '&sheetSubCategoryID=' + sheetSubCategoryID + '&token=' + token,
    type: 'POST',
    processData: false,
    contentType: false,
    success: function(resp) {
      $('.sheet-elements').replaceWith($(resp).find('.sheet-elements'));
      $('.photo-sheet[data-object-id=' + sheetID + ']').css('border', '3px solid #0d8aff');
      $('.linked-objects').replaceWith($(resp).find('.linked-objects'));
    },
    error: function() {}
  });
};
