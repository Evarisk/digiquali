<?php
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
 * \file    core/tpl/frontend/digiquali_answer_wizard.tpl.php
 * \ingroup digiquali
 * \brief   Mobile answer wizard shared by the public QR page and the connected PWA screen.
 *
 * The following vars must be defined:
 * Global     : $conf, $langs, $user
 * Objects    : $object, $objectLine, $sheet
 * Parameters : $isFrontend
 * Optional   : $wizardIntroHtml        HTML block describing what is being answered (intro screen)
 *              $wizardSummaryExtraHtml HTML appended to the summary screen (signature, submit button)
 *              $wizardSteps            Pre-built steps, otherwise built here
 *              $wizardExtraClass       Extra CSS class on the wizard root (e.g. answer-wizard--pwa)
 */

require_once __DIR__ . '/../../../lib/digiquali_answer_wizard.lib.php';

$isFrontend = $isFrontend ?? true;

if (!isset($wizardSteps)) {
    $wizardSteps = digiquali_answer_wizard_build_steps($object, $sheet->fetchQuestionsAndGroups());
}

$wizardProgress  = digiquali_answer_wizard_get_progress($wizardSteps);
$wizardFirstStep = digiquali_answer_wizard_get_first_incomplete_step($wizardSteps);
$wizardIsDraft   = ($object->status == $object::STATUS_DRAFT);
$wizardStepCount = count($wizardSteps);
$wizardStarted   = ($wizardProgress['answered'] > 0);

// A sheet that fits in a single step needs no wizard at all: stepping through an intro and a
// summary to answer one question is pure ceremony. Everything is then stacked on one screen,
// which is also the no-JS fallback.
$wizardIsSingleScreen = ($wizardStepCount <= 1);
$wizardHasIntro       = (!empty($wizardIntroHtml) && !$wizardIsSingleScreen);

// Screens are numbered in a single sequence so the navigation stays trivial:
// 0 = intro (optional), then one screen per step, then the summary
$wizardStepOffset   = $wizardHasIntro ? 1 : 0;
$wizardSummaryIndex = $wizardStepOffset + $wizardStepCount;
$wizardStartIndex   = $wizardHasIntro ? 0 : $wizardStepOffset + $wizardFirstStep;
?>
<div class="answer-wizard<?php echo $wizardIsDraft ? '' : ' answer-wizard--readonly'; ?><?php echo $wizardIsSingleScreen ? ' answer-wizard--single' : ''; ?><?php echo !empty($wizardExtraClass) ? ' ' . dol_escape_htmltag($wizardExtraClass) : ''; ?>"
     data-current-screen="<?php echo $wizardStartIndex; ?>"
     data-step-offset="<?php echo $wizardStepOffset; ?>"
     data-step-count="<?php echo $wizardStepCount; ?>"
     data-summary-index="<?php echo $wizardSummaryIndex; ?>"
     data-resume-index="<?php echo $wizardStepOffset + $wizardFirstStep; ?>"
     data-total-questions="<?php echo $wizardProgress['total']; ?>"
     data-answered-questions="<?php echo $wizardProgress['answered']; ?>">

    <div class="answer-wizard__header">
        <button type="button" class="answer-wizard__back" aria-label="<?php echo dol_escape_htmltag($langs->transnoentities('AnswerWizardPrevious')); ?>">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="answer-wizard__header-content">
            <div class="answer-wizard__step-label"></div>
            <button type="button" class="answer-wizard__step-counter" aria-haspopup="true">
                <?php
                // trans() runs sprintf() even with no argument, so asking for the raw pattern would
                // blank the two %s. Feed it explicit tokens instead and let the JS fill them in.
                $wizardStepPattern = $langs->transnoentities('AnswerWizardStepOf', '%CURRENT%', '%TOTAL%');
                ?>
                <span class="answer-wizard__step-counter-text" data-step-pattern="<?php echo dol_escape_htmltag($wizardStepPattern); ?>"></span>
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="answer-wizard__progress">
            <div class="answer-wizard__progress-bar" data-percent="<?php echo $wizardProgress['percent']; ?>"></div>
        </div>
    </div>

    <?php // Outside the header: a single-step sheet has no header but still saves as you go ?>
    <div class="answer-wizard__save-state" data-state="idle">
        <i class="fas fa-check answer-wizard__save-icon answer-wizard__save-icon--saved"></i>
        <i class="fas fa-circle-notch fa-spin answer-wizard__save-icon answer-wizard__save-icon--saving"></i>
        <i class="fas fa-exclamation-triangle answer-wizard__save-icon answer-wizard__save-icon--error"></i>
    </div>

    <div class="answer-wizard__screens">
        <?php if ($wizardHasIntro) { ?>
            <section class="answer-wizard__screen" data-screen-index="0" data-screen-type="intro">
                <?php print $wizardIntroHtml; ?>
                <div class="answer-wizard__intro-summary">
                    <i class="fas fa-list-ol"></i>
                    <span><?php echo $langs->trans('AnswerWizardIntroQuestions', $wizardProgress['total'], $wizardStepCount); ?></span>
                </div>
                <?php if ($wizardIsDraft) { ?>
                    <button type="button" class="answer-wizard__button answer-wizard__button--primary answer-wizard__start">
                        <i class="fas fa-play"></i>
                        <?php echo $langs->trans($wizardStarted ? 'AnswerWizardResume' : 'AnswerWizardStart'); ?>
                    </button>
                <?php } ?>
            </section>
        <?php } ?>

        <?php foreach ($wizardSteps as $wizardStepIndex => $wizardStep) { ?>
            <section class="answer-wizard__screen answer-wizard__step"
                     data-screen-index="<?php echo $wizardStepOffset + $wizardStepIndex; ?>"
                     data-screen-type="step"
                     data-step-key="<?php echo dol_escape_htmltag($wizardStep['key']); ?>"
                     data-step-label="<?php echo dol_escape_htmltag($wizardStep['label']); ?>"
                     data-step-total="<?php echo $wizardStep['total']; ?>">
                <?php
                // Single-screen mode has no intro screen: the object header goes on top of the questions
                if ($wizardIsSingleScreen && !empty($wizardIntroHtml)) {
                    print $wizardIntroHtml;
                }
                ?>
                <?php
                // Only a real group deserves a title in the content: it carries a name and a
                // description. A pack of ungrouped questions is already named by the header counter.
                if ($wizardStep['type'] == 'group') { ?>
                    <header class="answer-wizard__step-header">
                        <span class="answer-wizard__step-picto"><?php print img_picto('', $wizardStep['picto']); ?></span>
                        <h2 class="answer-wizard__step-title"><?php echo dol_escape_htmltag($wizardStep['label']); ?></h2>
                    </header>
                    <?php if (!empty($wizardStep['description'])) { ?>
                        <div class="answer-wizard__step-description"><?php print dol_htmlentitiesbr($wizardStep['description']); ?></div>
                    <?php } ?>
                <?php } ?>
                <?php $object->displayAnswers($objectLine, $wizardStep['items'], $isFrontend); ?>
            </section>
        <?php } ?>

        <section class="answer-wizard__screen answer-wizard__summary"
                 data-screen-index="<?php echo $wizardSummaryIndex; ?>"
                 data-screen-type="summary"
                 data-step-label="<?php echo dol_escape_htmltag($langs->transnoentities('AnswerWizardSummary')); ?>">
            <?php
            // Counters are recomputed client-side: the user answers without reloading, so a
            // server-rendered summary would already be stale by the time it is displayed.
            $wizardRemaining = $wizardProgress['total'] - $wizardProgress['answered'];
            ?>
            <div class="answer-wizard__summary-status"
                 data-state="<?php echo $wizardRemaining > 0 ? 'remaining' : 'complete'; ?>"
                 data-remaining-pattern="<?php echo dol_escape_htmltag($langs->transnoentities('AnswerWizardRemaining', '%COUNT%')); ?>"
                 data-all-answered-label="<?php echo dol_escape_htmltag($langs->transnoentities('AnswerWizardAllAnswered')); ?>">
                <i class="fas fa-exclamation-circle answer-wizard__summary-icon answer-wizard__summary-icon--warning"></i>
                <i class="fas fa-check-circle answer-wizard__summary-icon answer-wizard__summary-icon--ok"></i>
                <span class="answer-wizard__summary-text">
                    <?php echo $wizardRemaining > 0 ? $langs->trans('AnswerWizardRemaining', $wizardRemaining) : $langs->trans('AnswerWizardAllAnswered'); ?>
                </span>
            </div>

            <ul class="answer-wizard__summary-list">
                <?php
                foreach ($wizardSteps as $wizardStepIndex => $wizardStep) {
                    $wizardUnanswered = digiquali_answer_wizard_get_unanswered_questions($wizardStep, $object);
                    if (empty($wizardUnanswered)) {
                        continue;
                    }
                    foreach ($wizardUnanswered as $wizardQuestion) {
                        print '<li class="answer-wizard__summary-item" data-goto-screen="' . ($wizardStepOffset + $wizardStepIndex) . '" data-goto-question="' . $wizardQuestion->id . '">';
                        print '<span class="answer-wizard__summary-item-label">' . dol_escape_htmltag($wizardQuestion->label) . '</span>';
                        if (!empty($wizardStep['label'])) {
                            print '<span class="answer-wizard__summary-item-step">' . dol_escape_htmltag($wizardStep['label']) . '</span>';
                        }
                        print '<i class="fas fa-chevron-right"></i>';
                        print '</li>';
                    }
                }
                ?>
            </ul>

            <?php if (!empty($wizardSummaryExtraHtml)) { ?>
                <div class="answer-wizard__summary-extra"><?php print $wizardSummaryExtraHtml; ?></div>
            <?php } ?>
        </section>
    </div>

    <?php if ($wizardIsDraft && !$wizardIsSingleScreen) { ?>
        <nav class="answer-wizard__footer">
            <button type="button" class="answer-wizard__button answer-wizard__button--ghost answer-wizard__previous">
                <i class="fas fa-chevron-left"></i>
                <?php echo $langs->trans('AnswerWizardPrevious'); ?>
            </button>
            <button type="button" class="answer-wizard__button answer-wizard__button--primary answer-wizard__next"
                    data-label-next="<?php echo dol_escape_htmltag($langs->transnoentities('AnswerWizardNext')); ?>"
                    data-label-summary="<?php echo dol_escape_htmltag($langs->transnoentities('AnswerWizardSummary')); ?>">
                <span class="answer-wizard__next-label"><?php echo $langs->trans('AnswerWizardNext'); ?></span>
                <i class="fas fa-chevron-right"></i>
            </button>
        </nav>
    <?php } ?>

    <div class="answer-wizard__picker" hidden>
        <div class="answer-wizard__picker-backdrop"></div>
        <div class="answer-wizard__picker-sheet">
            <div class="answer-wizard__picker-title"><?php echo $langs->trans('AnswerWizardSteps'); ?></div>
            <ul class="answer-wizard__picker-list">
                <?php foreach ($wizardSteps as $wizardStepIndex => $wizardStep) { ?>
                    <li class="answer-wizard__picker-item" data-goto-screen="<?php echo $wizardStepOffset + $wizardStepIndex; ?>">
                        <span class="answer-wizard__picker-item-label">
                            <?php echo dol_escape_htmltag(!empty($wizardStep['label']) ? $wizardStep['label'] : $langs->transnoentities('Questions')); ?>
                        </span>
                        <span class="answer-wizard__picker-item-count" data-step-key="<?php echo dol_escape_htmltag($wizardStep['key']); ?>">
                            <?php echo $wizardStep['answered'] . '/' . $wizardStep['total']; ?>
                        </span>
                    </li>
                <?php } ?>
                <li class="answer-wizard__picker-item" data-goto-screen="<?php echo $wizardSummaryIndex; ?>">
                    <span class="answer-wizard__picker-item-label"><?php echo $langs->trans('AnswerWizardSummary'); ?></span>
                    <i class="fas fa-flag-checkered"></i>
                </li>
            </ul>
        </div>
    </div>
</div>
