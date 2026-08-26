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
 * \brief   JavaScript of the mobile answer wizard (screen navigation, progress, save state)
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
 * so this bails out immediately when the page holds no wizard.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.init = function() {
  const $wizard = $('.answer-wizard');
  if (!$wizard.length) {
    return;
  }

  $wizard.addClass('is-ready');

  window.digiquali.answerWizard.event();
  window.digiquali.answerWizard.showScreen(parseInt($wizard.attr('data-current-screen'), 10) || 0, false);
  window.digiquali.answerWizard.refreshProgress();
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
  $(document).on('click', '.answer-wizard__start, .answer-wizard__next', window.digiquali.answerWizard.goNext);
  $(document).on('click', '.answer-wizard__previous, .answer-wizard__back', window.digiquali.answerWizard.goPrevious);
  $(document).on('click', '.answer-wizard__step-counter', window.digiquali.answerWizard.togglePicker);
  $(document).on('click', '.answer-wizard__picker-backdrop', window.digiquali.answerWizard.togglePicker);
  $(document).on('click', '[data-goto-screen]', window.digiquali.answerWizard.goToScreen);

  // Same interactions as object.js, but here they only recompute the local progress
  $(document).on('click', '.answer-wizard .answer:not(.disable)', window.digiquali.answerWizard.refreshProgress);
  $(document).on('input change', '.answer-wizard .question-answer', window.digiquali.answerWizard.refreshProgress);

  $(document).ajaxSend(window.digiquali.answerWizard.onAnswerSaveStart);
  $(document).ajaxSuccess(window.digiquali.answerWizard.onAnswerSaveSuccess);
  $(document).ajaxError(window.digiquali.answerWizard.onAnswerSaveError);
};

/**
 * Show a wizard screen and sync the header, footer and picker with it.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @param  {int}  screenIndex Index of the screen to display
 * @param  {bool} scrollTop   Scroll back to the top of the wizard
 * @return {void}
 */
window.digiquali.answerWizard.showScreen = function(screenIndex, scrollTop) {
  const $wizard  = $('.answer-wizard');
  const $screens = $wizard.find('.answer-wizard__screen');

  const summaryIndex = parseInt($wizard.attr('data-summary-index'), 10);
  const boundedIndex = Math.max(0, Math.min(screenIndex, summaryIndex));
  const $screen      = $screens.filter('[data-screen-index="' + boundedIndex + '"]');
  if (!$screen.length) {
    return;
  }

  $screens.removeClass('is-active');
  $screen.addClass('is-active');
  $wizard.attr('data-current-screen', boundedIndex);

  window.digiquali.answerWizard.refreshChrome();

  if (scrollTop !== false) {
    $('html, body').scrollTop(Math.max(0, $wizard.offset().top - 10));
  }
};

/**
 * Update header labels, counter and footer buttons for the current screen.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.refreshChrome = function() {
  const $wizard = $('.answer-wizard');
  const current = parseInt($wizard.attr('data-current-screen'), 10);

  const stepOffset   = parseInt($wizard.attr('data-step-offset'), 10);
  const stepCount    = parseInt($wizard.attr('data-step-count'), 10);
  const summaryIndex = parseInt($wizard.attr('data-summary-index'), 10);

  const $screen    = $wizard.find('.answer-wizard__screen[data-screen-index="' + current + '"]');
  const screenType = $screen.attr('data-screen-type');

  let label   = '';
  let counter = '';
  if (screenType === 'step') {
    label   = $screen.attr('data-step-label') || '';
    counter = $wizard.find('.answer-wizard__step-counter-text').attr('data-step-pattern') || '';
    counter = counter.replace('%CURRENT%', current - stepOffset + 1).replace('%TOTAL%', stepCount);
  } else if (screenType === 'summary') {
    label = $screen.attr('data-step-label') || '';
  }

  // The intro screen carries its own object header: the wizard chrome would only add an empty bar
  $wizard.toggleClass('is-intro', screenType === 'intro');

  $wizard.find('.answer-wizard__step-label').text(label);
  $wizard.find('.answer-wizard__step-counter').toggle(screenType === 'step');
  $wizard.find('.answer-wizard__step-counter-text').text(counter);
  $wizard.find('.answer-wizard__back').prop('hidden', current === 0);

  const $next = $wizard.find('.answer-wizard__next');
  $next.prop('hidden', current === summaryIndex);
  $wizard.find('.answer-wizard__previous').prop('hidden', current === 0);

  $wizard.find('.answer-wizard__picker-item').removeClass('is-current')
    .filter('[data-goto-screen="' + current + '"]').addClass('is-current');
};

/**
 * Go to the next screen
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.goNext = function() {
  const $wizard = $('.answer-wizard');
  const current = parseInt($wizard.attr('data-current-screen'), 10);

  // From the intro screen, jump straight to where the user left off
  if (current === 0 && $wizard.find('.answer-wizard__screen[data-screen-index="0"]').attr('data-screen-type') === 'intro') {
    window.digiquali.answerWizard.showScreen(parseInt($wizard.attr('data-resume-index'), 10), true);
    return;
  }

  window.digiquali.answerWizard.showScreen(current + 1, true);
};

/**
 * Go to the previous screen
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.goPrevious = function() {
  const current = parseInt($('.answer-wizard').attr('data-current-screen'), 10);

  window.digiquali.answerWizard.showScreen(current - 1, true);
};

/**
 * Go to the screen carried by the clicked element, and highlight a question when asked.
 *
 * @since   21.0.0
 * @version 21.0.0
 *
 * @return {void}
 */
window.digiquali.answerWizard.goToScreen = function() {
  const $item      = $(this);
  const questionId = $item.attr('data-goto-question');

  $('.answer-wizard__picker').prop('hidden', true);
  window.digiquali.answerWizard.showScreen(parseInt($item.attr('data-goto-screen'), 10), true);

  if (questionId) {
    const $question = $('.answer-wizard .question.table-id-' + questionId);
    if ($question.length) {
      $('html, body').scrollTop(Math.max(0, $question.offset().top - 80));
    }
  }
};

/**
 * Open or close the step picker sheet
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
 * Recompute the global progress bar and the per-step counters.
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
    const $step     = $(this);
    const stepTotal = parseInt($step.attr('data-step-total'), 10) || 0;
    let stepAnswered = 0;

    $step.find('.question').each(function() {
      const $question = $(this);
      const isAnswered = window.digiquali.answerWizard.isQuestionAnswered($question);

      $question.toggleClass('question-complete', isAnswered);
      if (isAnswered) {
        stepAnswered++;
      }
    });

    answered += Math.min(stepAnswered, stepTotal);
    total    += stepTotal;

    const $count = $wizard.find('.answer-wizard__picker-item-count[data-step-key="' + $step.attr('data-step-key') + '"]');
    $count.text(Math.min(stepAnswered, stepTotal) + '/' + stepTotal)
      .toggleClass('is-complete', stepTotal > 0 && stepAnswered >= stepTotal);
  });

  const percent = total > 0 ? Math.round(answered * 100 / total) : 0;
  $wizard.find('.answer-wizard__progress-bar').css('width', percent + '%');
  $wizard.attr('data-answered-questions', answered);
};

/**
 * Flag the header as saving when an answer autosave request starts.
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
 * Flag the header as saved once the autosave request succeeded.
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
 * Flag the header as failed when the autosave request could not reach the server.
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
