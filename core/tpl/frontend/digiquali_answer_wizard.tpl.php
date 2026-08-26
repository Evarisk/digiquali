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
 * \brief   Mobile answer screen shared by the public QR page and the connected PWA screen.
 *
 * One continuous scroll, like any mobile form: no step navigation, no intro screen. The
 * sticky bar follows the scroll to tell which group is being answered and how far along the
 * user is, and opens a jump menu for long sheets.
 *
 * The following vars must be defined:
 * Global     : $conf, $langs, $user
 * Objects    : $object, $objectLine, $sheet
 * Parameters : $isFrontend
 * Optional   : $wizardIntroHtml        HTML block describing what is being answered (top of page)
 *              $wizardSummaryExtraHtml HTML appended at the end (signature, submit button)
 *              $wizardSteps            Pre-built sections, otherwise built here
 *              $wizardExtraClass       Extra CSS class on the root (e.g. answer-wizard--pwa)
 *              $wizardValidateConfirm  Ask for confirmation before validating
 */

require_once __DIR__ . '/../../../lib/digiquali_answer_wizard.lib.php';

$isFrontend = $isFrontend ?? true;

if (!isset($wizardSteps)) {
    $wizardSteps = digiquali_answer_wizard_build_steps($object, $sheet->fetchQuestionsAndGroups());
}

$wizardProgress = digiquali_answer_wizard_get_progress($wizardSteps);
$wizardIsDraft  = ($object->status == $object::STATUS_DRAFT);
$wizardNamed    = (count(array_filter($wizardSteps, function ($step) { return !empty($step['label']); })) > 0);
?>
<div class="answer-wizard<?php echo $wizardIsDraft ? '' : ' answer-wizard--readonly'; ?><?php echo $wizardNamed ? '' : ' answer-wizard--unnamed'; ?><?php echo !empty($wizardExtraClass) ? ' ' . dol_escape_htmltag($wizardExtraClass) : ''; ?>"
     data-total-questions="<?php echo $wizardProgress['total']; ?>"
     data-answered-questions="<?php echo $wizardProgress['answered']; ?>">

    <div class="answer-wizard__bar">
        <div class="answer-wizard__bar-content">
            <div class="answer-wizard__current-group"></div>
            <button type="button" class="answer-wizard__counter" aria-haspopup="true">
                <?php
                // trans() runs sprintf() even with no argument, so asking for the raw pattern would
                // blank the two %s. Feed it explicit tokens instead and let the JS fill them in.
                $wizardProgressPattern = $langs->transnoentities('AnswerWizardProgress', '%COUNT%', '%TOTAL%');
                ?>
                <span class="answer-wizard__counter-text" data-pattern="<?php echo dol_escape_htmltag($wizardProgressPattern); ?>"></span>
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="answer-wizard__progress">
            <div class="answer-wizard__progress-bar" data-percent="<?php echo $wizardProgress['percent']; ?>"></div>
        </div>
    </div>

    <?php if (!empty($wizardIntroHtml)) { ?>
        <div class="answer-wizard__intro"><?php print $wizardIntroHtml; ?></div>
    <?php } ?>

    <?php foreach ($wizardSteps as $wizardStep) { ?>
        <section class="answer-wizard__step"
                 data-step-key="<?php echo dol_escape_htmltag($wizardStep['key']); ?>"
                 data-step-label="<?php echo dol_escape_htmltag($wizardStep['label']); ?>"
                 data-step-total="<?php echo $wizardStep['total']; ?>">
            <?php if (!empty($wizardStep['label'])) { ?>
                <header class="answer-wizard__step-header">
                    <span class="answer-wizard__step-picto"><?php print img_picto('', $wizardStep['picto']); ?></span>
                    <h2 class="answer-wizard__step-title"><?php echo dol_escape_htmltag($wizardStep['label']); ?></h2>
                    <span class="answer-wizard__step-count" data-step-key="<?php echo dol_escape_htmltag($wizardStep['key']); ?>">
                        <?php echo $wizardStep['answered'] . '/' . $wizardStep['total']; ?>
                    </span>
                </header>
                <?php if (!empty($wizardStep['description'])) { ?>
                    <div class="answer-wizard__step-description"><?php print dol_htmlentitiesbr($wizardStep['description']); ?></div>
                <?php } ?>
            <?php } ?>
            <?php $object->displayAnswers($objectLine, $wizardStep['items'], $isFrontend); ?>
        </section>
    <?php } ?>

    <section class="answer-wizard__summary">
        <?php
        // Counters are recomputed client-side: the user answers without reloading, so a
        // server-rendered summary would already be stale by the time it is reached.
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
            foreach ($wizardSteps as $wizardStep) {
                foreach (digiquali_answer_wizard_get_unanswered_questions($wizardStep, $object) as $wizardQuestion) {
                    print '<li class="answer-wizard__summary-item" data-goto-question="' . $wizardQuestion->id . '">';
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

    <?php // Outside the bar: the save badge must stay visible wherever the user has scrolled ?>
    <div class="answer-wizard__save-state" data-state="idle">
        <i class="fas fa-check answer-wizard__save-icon answer-wizard__save-icon--saved"></i>
        <i class="fas fa-circle-notch fa-spin answer-wizard__save-icon answer-wizard__save-icon--saving"></i>
        <i class="fas fa-exclamation-triangle answer-wizard__save-icon answer-wizard__save-icon--error"></i>
    </div>

    <?php if (!empty($wizardValidateConfirm) && $wizardIsDraft) { ?>
        <div class="answer-wizard__confirm" hidden>
            <div class="answer-wizard__picker-backdrop answer-wizard__confirm-close"></div>
            <div class="answer-wizard__picker-sheet">
                <div class="answer-wizard__picker-title"><?php echo $langs->trans('AnswerWizardValidateTitle'); ?></div>
                <p class="answer-wizard__confirm-text"
                   data-pattern="<?php echo dol_escape_htmltag($langs->transnoentities('AnswerWizardValidateText', '%COUNT%', '%TOTAL%')); ?>"></p>
                <div class="answer-wizard__confirm-actions">
                    <button type="button" class="answer-wizard__button answer-wizard__button--ghost answer-wizard__confirm-close">
                        <?php echo $langs->trans('Cancel'); ?>
                    </button>
                    <button type="submit" name="validate_object" value="1" class="answer-wizard__button answer-wizard__button--primary">
                        <?php echo $langs->trans('Validate'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php if ($wizardNamed) { ?>
        <div class="answer-wizard__picker" hidden>
            <div class="answer-wizard__picker-backdrop"></div>
            <div class="answer-wizard__picker-sheet">
                <div class="answer-wizard__picker-title"><?php echo $langs->trans('AnswerWizardSteps'); ?></div>
                <ul class="answer-wizard__picker-list">
                    <?php foreach ($wizardSteps as $wizardStep) { ?>
                        <li class="answer-wizard__picker-item" data-goto-step="<?php echo dol_escape_htmltag($wizardStep['key']); ?>">
                            <span class="answer-wizard__picker-item-label">
                                <?php echo dol_escape_htmltag(!empty($wizardStep['label']) ? $wizardStep['label'] : $langs->transnoentities('Questions')); ?>
                            </span>
                            <span class="answer-wizard__picker-item-count" data-step-key="<?php echo dol_escape_htmltag($wizardStep['key']); ?>">
                                <?php echo $wizardStep['answered'] . '/' . $wizardStep['total']; ?>
                            </span>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    <?php } ?>
</div>
