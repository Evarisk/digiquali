/* Copyright (C) 2024-2025 EVARISK <technique@evarisk.com>
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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/modules/object.js
 * \ingroup digiquali
 * \brief   JavaScript object file for module DigiQuali
 */

/**
 * Init object JS
 *
 * @memberof DigiQuali_Object
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 * @type {Object}
 */
window.digiquali.object = {};

/**
 * Object init
 *
 * @memberof DigiQuali_Object
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 * @returns {void}
 */
window.digiquali.object.init = function() {
  window.digiquali.object.event();

  window.digiquali.object.placePercents();
};

/**
 * Object event
 *
 * @memberof DigiQuali_Object
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 * @returns {void}
 */
window.digiquali.object.event = function() {
  $(document).on( 'change', '.object-table.linked-objects select', window.digiquali.object.disableOtherSelectors);
  $(document).on( 'click', '.answer:not(.disable)', window.digiquali.object.selectAnswer);
  $(document).on( 'input', '.input-answer:not(.disable)', window.digiquali.object.selectAnswer);
  $(document).on( 'keyup', '.question-comment', window.digiquali.object.showCommentUnsaved);
  $(document).on( 'blur', '.question-comment', window.digiquali.object.saveCommentAuto);
  $(document).on( 'blur', 'textarea.question-answer', window.digiquali.object.saveTextOrNumericAnswer);
  $(document).on( 'change', 'input[type="number"].question-answer', window.digiquali.object.saveTextOrNumericAnswer);
  $(document).on( 'change', '.question-answer', window.digiquali.object.changeStatusQuestion);
  $(document).on( 'click', '.answer:not(.disable)', window.digiquali.object.changeStatusQuestion);
  $(document).on('input', '.question-answer[type="range"]', function () {
    window.digiquali.object.rangePercent.call(this, false);
  });
};

/**
 * Disable selectors on object selection
 *
 * @since   1.8.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.disableOtherSelectors = function() {
  var objectForm = document.getElementById('createObjectForm');
  var formData   = new FormData(objectForm);

  let selectorId   = $(this).attr('id');
  let selectorData = formData.get(selectorId);

  if (selectorData >= 0) {
    $('.object-table.linked-objects').find('select').not('#' + selectorId).attr('disabled', 1);
  } else {
    $('.object-table.linked-objects').find('select').not('#' + selectorId).removeAttr('disabled');
  }
};

/**
 * Select an answer on question
 *
 * @since   1.0.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.selectAnswer = function() {
  let questionElement = $(this).closest('.select-answer.answer-cell');
  let questionId      = questionElement.attr('data-questionId');
  let publicInterface = $(this).closest('.table-id-' + questionId).attr('data-publicInterface');
  let autoSave        = $(this).closest('.table-id-' + questionId).attr('data-autoSave');
  let answer          = '';
  let answerValue     = $(this).hasClass('answer') ? $(this).attr('value') : $(this).val();
  let comment         = $(this).closest('.table-id-' + questionId).find('textarea[name="comment' + questionId + '"]').val();

  if ($(this).closest('.table-cell').hasClass('select-answer')) {
    if ($(this).hasClass('multiple-answers')) {
      $(this).closest('span').toggleClass('active');
      let selectedValues = [];
      questionElement.find('.multiple-answers.active').each(function() {
        selectedValues.push($(this).attr('value'));
      });
      answer = selectedValues;
    } else {
      $(this).closest('.table-cell').find('.answer.active').css( 'background-color', '#fff' );

      $(this).closest('.table-cell').find('span').removeClass('active');
      $(this).closest('span').addClass('active');
      answer = answerValue;
    }
    if ($(this).hasClass('active')) {
      let answerColor = $(this).closest('.answer-cell').find('.answer-color-' + $(this).attr('value')).val();
      $(this).attr('style', $(this).attr('style') + ' background:' + answerColor + ';');
    } else {
      $(this).attr('style', $(this).attr('style') + ' background:#fff;');
    }
    $(this).closest('.answer-cell').find('.question-answer').val(answer);
  }

  if (!publicInterface) {
    window.digiquali.object.saveAnswer(questionId, answer, comment);
  } else {
    window.digiquali.object.updateButtonsStatus();
  }
};

window.digiquali.object.changeStatusQuestion = function() {
  $(this).closest('.question').addClass('question-complete');
  window.digiquali.object.updateButtonsStatus();
};

/**
 * Show a comment for a question answer if focus out
 *
 * @since   1.1.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.showCommentUnsaved = function() {
  if (!$(this).hasClass('show-comment-unsaved-message') && !$('.question-answer-container').hasClass('question-answer-container-pwa')) {
    $(this).after('<p style="color:red;">Commentaire non enregistré</p>');
    $(this).addClass('show-comment-unsaved-message');
  }
  window.digiquali.object.updateButtonsStatus();
};

/**
 * Change buttons status
 *
 * @since   1.1.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.updateButtonsStatus = function() {
  $('#saveButton').removeClass('butActionRefused');
  $('#saveButton').addClass('butAction');
  $('#saveButton').css('background', '#0d8aff');
  $('.fa-circle').css('display', 'inline');
  $('#saveButton').removeAttr('onclick').off('click').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $("#saveObject").submit();
  });

  $('.validateButton').removeClass('butAction');
  $('#dialog-confirm-actionButtonValidate').removeAttr('id');
  $('.validateButton').addClass('butActionRefused');
};

/**
 * Save answer after click event
 *
 * @since   1.9.0
 * @version 1.11.0
 *
 * @param  {int}    questionId Question ID
 * @param  {string} answer     Answer value
 * @param  {string} comment    Comment value
 * @return {void}
 */
window.digiquali.object.saveTimeouts = window.digiquali.object.saveTimeouts || {};

window.digiquali.object.saveAnswer = function(questionId, answer, comment) {
  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);
  let answerVal      = Array.isArray(answer) ? answer.join(',') : answer;

  if (window.digiquali.object.saveTimeouts[questionId]) {
    clearTimeout(window.digiquali.object.saveTimeouts[questionId]);
  }

  window.digiquali.object.saveTimeouts[questionId] = setTimeout(function() {
    $.ajax({
      url: document.URL + querySeparator + 'action=save',
      type: 'POST',
      data: {
        token: token,
        autoSave: 'true',
        questionId: questionId,
        answer: answerVal,
        comment: comment
      },
      success: function(resp) {
        let $resp = $(resp);
        $('.progress-info').replaceWith($resp.find('.progress-info'));
        $('#dialog-confirm-actionButtonValidate>.confirmmessage').replaceWith($resp.find('#dialog-confirm-actionButtonValidate>.confirmmessage'));
        // Refresh the per-group answered-question counters in real time so that questions inside
        // groups (and nested sub-groups) are reflected immediately, on both backend and public interfaces.
        $('.group-answer-counter').each(function() {
          let groupId       = $(this).attr('data-group-id');
          let $freshCounter = $resp.find('.group-answer-counter[data-group-id="' + groupId + '"]');
          if ($freshCounter.length) {
            $(this).text($freshCounter.first().text());
          }
        });
        // Remove the red unsaved warning from the comment box that was saved
        let $commentArea = $('.question-comment[name="comment' + questionId + '"]');
        if ($commentArea.length) {
            $commentArea.removeClass('show-comment-unsaved-message');
            $commentArea.next('p').remove();
        }
        $.jnotify('Sauvegarde réussie', 'success', false, {autoHide: true, clickOverlay: false, minWidth: 250, TimeShown: 1500, ShowTimeEffect: 150, HideTimeEffect: 150, LongTrip: 20, HorizontalPosition: 'right', VerticalPosition: 'top', ShowOverlay: false, ColorOverlay: '#000', OpacityOverlay: 0.3});
      },
      error: function() {
        $.jnotify('Erreur de sauvegarde', 'error', false, {autoHide: true, clickOverlay: false, minWidth: 250, TimeShown: 1500, ShowTimeEffect: 150, HideTimeEffect: 150, LongTrip: 20, HorizontalPosition: 'right', VerticalPosition: 'top', ShowOverlay: false, ColorOverlay: '#000', OpacityOverlay: 0.3});
      }
    });
  }, 1000);
};

/**
 * Auto-save text or numeric answer on blur/change
 *
 * @since   1.12.0
 * @version 1.12.0
 *
 * @return {void}
 */
window.digiquali.object.saveTextOrNumericAnswer = function() {
  let inputName = $(this).attr('name');
  if (inputName && inputName.indexOf('answer') === 0) {
    let questionId      = inputName.replace('answer', '');
    let answer          = $(this).val();
    let comment         = $(this).closest('.table-id-' + questionId).find('textarea[name="comment' + questionId + '"]').val() || '';
    let publicInterface = $(this).closest('.table-id-' + questionId).attr('data-publicInterface');

    if (!publicInterface) {
      window.digiquali.object.saveAnswer(questionId, answer, comment);
    }
  }
};

/**
 * Range purcent
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 * @return {void}
 */
window.digiquali.object.rangePercent = function(fromInit) {
  const mobile      = window.saturne.toolbox.isPhone();
  const slider      = $(this);
  const value       = parseFloat(slider.val());
  const min         = parseInt(slider.attr('min'));
  const max         = parseInt(slider.attr('max'));
  const sliderWidth = slider.width();
  const sliderPos   = slider.position().left;
  const sliderTop   = slider.position().top;
  var thumbWidth    = mobile ? 36 : 70;
  let questionId      = (slider.attr('name') || '').replace('answer', '');
  let publicInterface = $(this).closest('.table-id-' + questionId).attr('data-publicInterface');
  let autoSave        = $(this).closest('.table-id-' + questionId).attr('data-autoSave');

  slider.parent().find('.range-percent').remove();

  const rangePercentValue = (Math.round(value * 100) / 100).toFixed(2);
  const rangePercent = $('<span class="range-percent">' + rangePercentValue + '%</span>');
  if (!mobile) {
    rangePercent.css('transform', 'translateX(0)');
  }

  rangePercent.addClass('badge badge-primary');

  rangePercent.css('top', (sliderTop - (thumbWidth * 1.05) / 2 - (mobile ? 10 : 5)) + 'px');

  var pos = (value - min) / (max - min);

  // how to get the thumb width
  var thumbCorrect = -thumbWidth * (pos - 0.5);
  var titlePos = sliderPos + Math.round((pos * sliderWidth) - (mobile ? 0 : thumbWidth / 4) + thumbCorrect);

  rangePercent.css('left', titlePos);
  slider.attr('value', rangePercentValue);

  slider.parent().append(rangePercent);

  if (!fromInit) {
    if (!publicInterface) {
      let comment = $(this).closest('.table-id-' + questionId).find('textarea[name="comment' + questionId + '"]').val() || '';
      window.digiquali.object.saveAnswer(questionId, rangePercentValue, comment);
    } else {
      window.digiquali.object.updateButtonsStatus();
    }
  }

}

/**
 * Place the object in the right place
 *
 * @since   1.11.0
 * @version 1.11.0
 *
 */
window.digiquali.object.placePercents = function() {
  $('.question-answer[type="range"]').each(function() {
    window.digiquali.object.rangePercent.call(this, true);
  });
}

/**
 * Auto-save comment on blur
 *
 * @since   1.12.0
 * @version 1.12.0
 *
 * @returns {void}
 */
window.digiquali.object.saveCommentAuto = function() {
  let inputName = $(this).attr('name');
  if (inputName && inputName.indexOf('comment') === 0) {
    let questionId = inputName.replace('comment', '');
    let comment    = $(this).val();
    
    // Find answer element. Fallback to common class if closest fails.
    let answerElement = $(this).closest('.table-id-' + questionId).find('.question-answer');
    if(answerElement.length === 0) {
        answerElement = $(this).closest('.answer-cell').find('.question-answer');
    }
    let answer = answerElement.val() || '';
    
    let publicInterface = $(this).closest('.table-id-' + questionId).attr('data-publicInterface');

    if (!publicInterface) {
      window.digiquali.object.saveAnswer(questionId, answer, comment);
    }
  }
};
