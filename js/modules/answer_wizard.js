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
 * \file    js/modules/answer_wizard.js
 * \ingroup digiquali
 * \brief   JavaScript of the mobile answer screen (progress, current group, save state)
 */

'use strict';

/**
 * Init answer wizard JS
 *
 * @since   21.0.0
 * @version 21.0.0
 */
window.digiquali.answerWizard = {};

/**
 * Answer wizard init
 *
 * The module loader calls init() on every registered module without a try/catch,
 * so this bails out immediately when the page holds no answer screen.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.init = function() {
  if (!$('.answer-wizard').length) {
    return;
  }

  window.digiquali.answerWizard.event();
  window.digiquali.answerWizard.refreshProgress();
  window.digiquali.answerWizard.refreshCurrentGroup();
};

/**
 * Answer wizard events
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.event = function() {
  $(document).on('click', '.answer-wizard__counter', window.digiquali.answerWizard.togglePicker);
  $(document).on('click', '.answer-wizard__picker-backdrop', window.digiquali.answerWizard.closePicker);
  $(document).on('click', '[data-goto-step]', window.digiquali.answerWizard.goToStep);
  $(document).on('click', '[data-goto-question]', window.digiquali.answerWizard.goToQuestion);
  $(document).on('click', '.answer-wizard__finish', window.digiquali.answerWizard.openConfirm);
  $(document).on('click', '.answer-wizard__confirm-close', window.digiquali.answerWizard.closeConfirm);

  // Same interactions as object.js, but here they only recompute the local progress
  $(document).on('click', '.answer-wizard .answer:not(.disable)', window.digiquali.answerWizard.refreshProgress);
  $(document).on('input change', '.answer-wizard .question-answer', window.digiquali.answerWizard.refreshProgress);

  $(window).on('scroll', window.digiquali.answerWizard.refreshCurrentGroup);

  $(document).ajaxSend(window.digiquali.answerWizard.onAnswerSaveStart);
  $(document).ajaxSuccess(window.digiquali.answerWizard.onAnswerSaveSuccess);
  $(document).ajaxError(window.digiquali.answerWizard.onAnswerSaveError);
};

/**
 * Name the group the user is currently scrolled into.
 *
 * The page is one continuous scroll, so the sticky bar is what tells the user where they
 * are. The current section is the last one whose top has passed under the bar.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.refreshCurrentGroup = function() {
  const $wizard = $('.answer-wizard');
  if (!$wizard.length) {
    return;
  }

  const barBottom = $wizard.find('.answer-wizard__bar').offset().top + $wizard.find('.answer-wizard__bar').outerHeight();

  // Above the first section (the object header) no section has scrolled past yet: keep the
  // sheet name rather than leaving the bar blank
  let label = $wizard.attr('data-default-label') || '';

  $wizard.find('.answer-wizard__step').each(function() {
    const $step = $(this);
    if ($step.offset().top <= barBottom + 5) {
      label = $step.attr('data-step-label') || '';
    }
  });

  $wizard.find('.answer-wizard__current-group').text(label);
};

/**
 * Scroll to a section, from the jump menu
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.goToStep = function() {
  const $step = $('.answer-wizard__step[data-step-key="' + $(this).attr('data-goto-step') + '"]');

  window.digiquali.answerWizard.closePicker();
  if ($step.length) {
    window.digiquali.answerWizard.scrollTo($step);
  }
};

/**
 * Scroll to a question, from the summary list
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.goToQuestion = function() {
  const $question = $('.answer-wizard .question.table-id-' + $(this).attr('data-goto-question'));

  if ($question.length) {
    window.digiquali.answerWizard.scrollTo($question);
  }
};

/**
 * Scroll an element just under the sticky bar
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {jQuery} $target Element to bring into view
 * @return {void}
 */
window.digiquali.answerWizard.scrollTo = function($target) {
  const $bar   = $('.answer-wizard__bar');
  const offset = $target.offset().top - ($bar.length ? $bar.outerHeight() : 0) - 10;

  $('html, body').animate({scrollTop: Math.max(0, offset)}, 250);
};

/**
 * Open or close the jump menu
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.togglePicker = function() {
  const $picker = $('.answer-wizard__picker');

  $picker.prop('hidden', !$picker.prop('hidden'));
};

/**
 * Close the jump menu
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.closePicker = function() {
  $('.answer-wizard__picker').prop('hidden', true);
};

/**
 * Open the confirmation sheet, filled with the answer count of the moment.
 *
 * Validating locks the answers, so the user is told how many questions are actually
 * answered before committing.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.openConfirm = function() {
  const $wizard  = $('.answer-wizard');
  const $confirm = $wizard.find('.answer-wizard__confirm');
  if (!$confirm.length) {
    return;
  }

  const answered = parseInt($wizard.attr('data-answered-questions'), 10) || 0;
  const total    = parseInt($wizard.attr('data-total-questions'), 10) || 0;
  const $text    = $confirm.find('.answer-wizard__confirm-text');

  $text.text(($text.attr('data-pattern') || '').replace('%COUNT%', answered).replace('%TOTAL%', total));
  $confirm.prop('hidden', false);
};

/**
 * Close the confirmation sheet without validating
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.closeConfirm = function() {
  $('.answer-wizard__confirm').prop('hidden', true);
};

/**
 * Tell whether a question card currently holds an answer.
 *
 * Mirrors the server rule (an answer that is not an empty string), so the local
 * counters stay consistent with the ones computed by the wizard library.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {jQuery} $question Question card
 * @return {bool}             True when the question is answered
 */
window.digiquali.answerWizard.isQuestionAnswered = function($question) {
  const $selectAnswer = $question.find('.select-answer');
  if ($selectAnswer.length) {
    return $selectAnswer.find('.answer.active').length > 0;
  }

  const $input = $question.find('.question-answer').not('[type="hidden"]');
  if (!$input.length) {
    return false;
  }

  const value = $input.first().val();

  return value !== undefined && value !== null && String(value).trim() !== '';
};

/**
 * Recompute the progress bar and the per-section counters.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.refreshProgress = function() {
  const $wizard = $('.answer-wizard');
  if (!$wizard.length) {
    return;
  }

  let answered = 0;
  let total    = 0;

  $wizard.find('.answer-wizard__step').each(function() {
    const $step      = $(this);
    const stepTotal  = parseInt($step.attr('data-step-total'), 10) || 0;
    let stepAnswered = 0;

    $step.find('.question').each(function() {
      const $question  = $(this);
      const isAnswered = window.digiquali.answerWizard.isQuestionAnswered($question);

      $question.toggleClass('question-complete', isAnswered);
      if (isAnswered) {
        stepAnswered++;
      }
    });

    stepAnswered = Math.min(stepAnswered, stepTotal);
    answered    += stepAnswered;
    total       += stepTotal;

    $wizard.find('[data-step-key="' + $step.attr('data-step-key') + '"]')
      .filter('.answer-wizard__step-count, .answer-wizard__picker-item-count')
      .text(stepAnswered + '/' + stepTotal)
      .toggleClass('is-complete', stepTotal > 0 && stepAnswered >= stepTotal);
  });

  const percent = total > 0 ? Math.round(answered * 100 / total) : 0;
  $wizard.find('.answer-wizard__progress-bar').css('width', percent + '%');
  $wizard.attr('data-answered-questions', answered);

  const $counter = $wizard.find('.answer-wizard__counter-text');
  $counter.text(($counter.attr('data-pattern') || '').replace('%COUNT%', answered).replace('%TOTAL%', total));

  window.digiquali.answerWizard.refreshSummary(total - answered);
};

/**
 * Refresh the summary from the current state of the questions.
 *
 * The summary is rendered server-side on page load, but answers are given without
 * reloading: left alone it would still list questions the user has just answered.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {int} remaining Number of questions still unanswered
 * @return {void}
 */
window.digiquali.answerWizard.refreshSummary = function(remaining) {
  const $wizard = $('.answer-wizard');

  $wizard.find('.answer-wizard__summary-item[data-goto-question]').each(function() {
    const $item     = $(this);
    const $question = $wizard.find('.question.table-id-' + $item.attr('data-goto-question'));

    if ($question.length) {
      $item.toggle(!window.digiquali.answerWizard.isQuestionAnswered($question));
    }
  });

  const $status = $wizard.find('.answer-wizard__summary-status');
  if (!$status.length) {
    return;
  }

  if (remaining > 0) {
    $status.attr('data-state', 'remaining');
    $status.find('.answer-wizard__summary-text').text(($status.attr('data-remaining-pattern') || '').replace('%COUNT%', remaining));
  } else {
    $status.attr('data-state', 'complete');
    $status.find('.answer-wizard__summary-text').text($status.attr('data-all-answered-label') || '');
  }
};

/**
 * Flag the save badge as saving when an answer autosave request starts.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {Event}  event    jQuery event
 * @param  {Object} xhr      Request
 * @param  {Object} settings Request settings
 * @return {void}
 */
window.digiquali.answerWizard.onAnswerSaveStart = function(event, xhr, settings) {
  if (window.digiquali.answerWizard.isAnswerSave(settings)) {
    $('.answer-wizard__save-state').attr('data-state', 'saving');
  }
};

/**
 * Flag the save badge as saved once the autosave request succeeded.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {Event}  event    jQuery event
 * @param  {Object} xhr      Request
 * @param  {Object} settings Request settings
 * @return {void}
 */
window.digiquali.answerWizard.onAnswerSaveSuccess = function(event, xhr, settings) {
  if (window.digiquali.answerWizard.isAnswerSave(settings)) {
    $('.answer-wizard__save-state').attr('data-state', 'saved');
    window.digiquali.answerWizard.refreshProgress();
  }
};

/**
 * Flag the save badge as failed when the autosave request could not reach the server.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {Event}  event    jQuery event
 * @param  {Object} xhr      Request
 * @param  {Object} settings Request settings
 * @return {void}
 */
window.digiquali.answerWizard.onAnswerSaveError = function(event, xhr, settings) {
  if (window.digiquali.answerWizard.isAnswerSave(settings)) {
    $('.answer-wizard__save-state').attr('data-state', 'error');
  }
};

/**
 * Tell whether an ajax request is an answer autosave.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {Object} settings Request settings
 * @return {bool}            True for an answer autosave request
 */
window.digiquali.answerWizard.isAnswerSave = function(settings) {
  return !!(settings && typeof settings.data === 'string' && settings.data.indexOf('autoSave=true') !== -1);
};
