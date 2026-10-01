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

/**
 * \file    js/modules/photo_multiple.js
 * \ingroup digiquali
 * \brief   JavaScript of the PhotoMultiple question type : one row per answer photo, with its comment and OK/KO status.
 *          Markup: digiquali_render_photo_multiple_answer() in lib/digiquali_answer.lib.php.
 *
 * The rows only fill the hidden input named answer<questionId>, a JSON object keyed by photo name :
 * that input is what the save action and the auto-save read. The photos themselves come and go
 * through the Saturne media block of the question, or come from the media library modal : the
 * responses of both are replayed here to keep the table in step with the gallery.
 */

/**
 * Init photo multiple JS
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @type {Object}
 */
window.digiquali.photoMultiple = {};

/**
 * Photo multiple init
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @returns {void}
 */
window.digiquali.photoMultiple.init = function() {
  window.digiquali.photoMultiple.event();
};

/**
 * Photo multiple events
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @returns {void}
 */
window.digiquali.photoMultiple.event = function() {
  $(document).on('click', '.question-photo-multiple__status-button:not([disabled])', window.digiquali.photoMultiple.toggleStatus);
  // Typing only refreshes the answer so a full save carries it, the round trip waits for the blur
  $(document).on('input', '.question-photo-multiple__comment', window.digiquali.photoMultiple.onCommentInput);
  $(document).on('change', '.question-photo-multiple__comment', window.digiquali.photoMultiple.onCommentChange);
  $(document).ajaxComplete(window.digiquali.photoMultiple.onMediaBlockResponse);
};

/**
 * Set or clear the status of a photo : a second click on the active status clears it
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @returns {void}
 */
window.digiquali.photoMultiple.toggleStatus = function() {
  const $button   = $(this);
  const isActive  = $button.hasClass('active');
  const $buttons  = $button.closest('.question-photo-multiple__status').find('.question-photo-multiple__status-button');

  $buttons.removeClass('active').attr('aria-pressed', 'false');
  if (!isActive) {
    $button.addClass('active').attr('aria-pressed', 'true');
  }

  window.digiquali.photoMultiple.sync($button.closest('.question-photo-multiple'), true);
};

/**
 * Refresh the answer while a photo comment is typed
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @returns {void}
 */
window.digiquali.photoMultiple.onCommentInput = function() {
  window.digiquali.photoMultiple.sync($(this).closest('.question-photo-multiple'), false);
};

/**
 * Save the answer once a photo comment is left
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @returns {void}
 */
window.digiquali.photoMultiple.onCommentChange = function() {
  window.digiquali.photoMultiple.sync($(this).closest('.question-photo-multiple'), true);
};

/**
 * Read the rows of a widget into the answer object, keyed by photo name
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @param  {jQuery} $widget The .question-photo-multiple element
 * @return {Object}         Rows keyed by photo name, each with its comment and status
 */
window.digiquali.photoMultiple.readRows = function($widget) {
  const rows = {};

  $widget.find('.question-photo-multiple__row').each(function() {
    const $row = $(this);

    rows[$row.attr('data-file-name')] = {
      comment: $row.find('.question-photo-multiple__comment').val() || '',
      status: $row.find('.question-photo-multiple__status-button.active').attr('data-status') || ''
    };
  });

  return rows;
};

/**
 * Write the rows into the hidden answer, mark the question and save it when asked and changed
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @param  {jQuery}  $widget The .question-photo-multiple element
 * @param  {boolean} save    True to send the answer to the server if it changed since the last save
 * @return {void}
 */
window.digiquali.photoMultiple.sync = function($widget, save) {
  const questionId = $widget.attr('data-question-id');
  const rows       = window.digiquali.photoMultiple.readRows($widget);
  // No photo is no answer, not an empty object : the question must stay unanswered
  const answer     = Object.keys(rows).length > 0 ? JSON.stringify(rows) : '';
  const $answer    = $widget.find('.question-answer');
  const $question  = $widget.closest('.question');

  $answer.val(answer);
  // Refreshes the save buttons and the progress of the answer screen, like any other answer input
  $answer.trigger('change');
  // The generic change handler marks the question complete unconditionally, which is wrong once every photo is gone
  $question.toggleClass('question-complete', answer !== '');

  if (save && answer !== $widget.attr('data-saved-answer')) {
    const comment = $question.find('textarea[name="comment' + questionId + '"], input[name="comment' + questionId + '"]').val() || '';

    $widget.attr('data-saved-answer', answer);
    window.digiquali.object.saveAnswer(questionId, answer, comment);
  }
};

/**
 * Replay the page returned to a photo upload or deletion of the media block, or to photos added from
 * the media library : the table of the question gets its rows back from the server, with what was
 * typed meanwhile kept on each photo
 *
 * @memberof DigiQuali_PhotoMultiple
 *
 * @since   23.2.0
 * @version 23.2.0
 *
 * @param  {Event}  event    AJAX complete event
 * @param  {Object} xhr      jQuery XHR of the request
 * @param  {Object} settings Settings of the request
 * @return {void}
 */
window.digiquali.photoMultiple.onMediaBlockResponse = function(event, xhr, settings) {
  const isMediaBlockRequest   = settings && /[?&]action=(uploadPhoto|deletePhoto)(&|$)/.test(settings.url || '') && settings.data instanceof FormData;
  const isMediaLibraryRequest = settings && /[?&]subaction=addFiles(&|$)/.test(settings.url || '') && typeof settings.data === 'string';
  if (!isMediaBlockRequest && !isMediaLibraryRequest) {
    return;
  }

  // The media block posts the directory from the module root, the library modal only its part under the object
  let matchesWidget;
  if (isMediaBlockRequest) {
    const subDir  = settings.data.get('sub_dir');
    matchesWidget = function(widgetSubDir) { return widgetSubDir === subDir; };
  } else {
    let libraryData = {};
    try {
      libraryData = JSON.parse(settings.data) || {};
    } catch (e) {
      return;
    }
    const objectSubdir = '/' + (libraryData.objectSubdir || '');
    matchesWidget = function(widgetSubDir) {
      return objectSubdir.length > 1 && widgetSubDir.slice(-objectSubdir.length) === objectSubdir;
    };
  }

  const $widget = $('.question-photo-multiple').filter(function() {
    return matchesWidget($(this).attr('data-sub-dir') || '');
  });
  if (!$widget.length || !xhr || typeof xhr.responseText !== 'string') {
    return;
  }

  const $freshWidget = $('<div>').html(xhr.responseText).find('#' + $widget.attr('id'));
  if (!$freshWidget.length) {
    return;
  }

  // The server only knows what was saved : a comment still being typed must survive the refresh
  const typedRows = window.digiquali.photoMultiple.readRows($widget);
  $freshWidget.find('.question-photo-multiple__row').each(function() {
    const typedRow = typedRows[$(this).attr('data-file-name')];
    if (!typedRow) {
      return;
    }

    $(this).find('.question-photo-multiple__comment').val(typedRow.comment);
    $(this).find('.question-photo-multiple__status-button').each(function() {
      const isActive = $(this).attr('data-status') === typedRow.status;
      $(this).toggleClass('active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
    });
  });

  // The server rendered what it had in base, which may lag behind an auto-save still on its way
  $freshWidget.attr('data-saved-answer', $widget.attr('data-saved-answer'));
  $widget.replaceWith($freshWidget);

  // A new photo is a new row and a deleted one a row less : either way the answer has changed
  window.digiquali.photoMultiple.sync($freshWidget, true);
};
