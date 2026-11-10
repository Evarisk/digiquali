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
 * \file    core/tpl/survey/survey_session_summary.tpl.php
 * \ingroup digiquali
 * \brief   Summary of a collect session, laid out for the screen and for print (css/scss/page/_pwa-survey.scss).
 *
 * Expected variables (set by view/survey/survey_session_summary.php):
 * - Survey   $session           Session
 * - Sheet    $sheet             Sheet of the session
 * - Survey[] $responses         Responses of the session
 * - array    $summary           Summary, see digiquali_survey_session_get_summary()
 * - array    $linkedObjectNames Names of the objects the session is about, by object type label
 */

global $langs, $mysoc;

$decimalSeparator = $langs->transnoentitiesnoconv('SeparatorDecimal');
$formatNumber     = function ($number) use ($decimalSeparator): string {
    return str_replace('.', $decimalSeparator, (string) round((float) $number, 1));
};
$respondentCount  = count($summary['respondents']);
$isNominative     = !empty($session->nominative);

// Period of the collect
if ($session->status == Survey::STATUS_DRAFT) {
    $period = $langs->trans('SurveySessionOpenSince', dol_print_date($session->date_creation, 'dayhour', 'tzuserrel'));
    if (!empty($session->date_end)) {
        $period .= ' - ' . $langs->trans('SurveySessionClosesOn', dol_print_date($session->date_end, 'dayhour', 'tzuserrel'));
    }
} else {
    $period = $langs->trans('SurveySessionPeriod', dol_print_date($session->date_creation, 'dayhour', 'tzuserrel'), dol_print_date($session->tms, 'dayhour', 'tzuserrel'));
}
?>
<div class="survey-session-summary">
    <div class="survey-session-summary__actions noprint">
        <a class="butAction" href="<?php echo dol_escape_htmltag(dol_buildpath('/digiquali/view/survey/survey_card.php', 1) . '?id=' . $session->id); ?>"><i class="fas fa-arrow-left"></i> <?php echo $langs->trans('SurveySessionBackToSession'); ?></a>
        <button type="button" class="butAction survey-session-summary__print"><i class="fas fa-print"></i> <?php echo $langs->trans('SurveySessionPrint'); ?></button>
    </div>

    <header class="survey-session-summary__header">
        <div class="survey-session-summary__company"><?php echo dol_escape_htmltag($mysoc->name); ?></div>
        <h1 class="survey-session-summary__title"><?php echo $langs->trans('SurveySessionSummary'); ?> : <?php echo dol_escape_htmltag(digiquali_survey_get_title($session, $sheet)); ?></h1>
        <dl class="survey-session-summary__meta">
            <dt><?php echo $langs->trans('Sheet'); ?></dt>
            <dd><?php echo dol_escape_htmltag(($sheet->label ?: $sheet->ref) . ' (' . $sheet->ref . ')'); ?></dd>
            <dt><?php echo $langs->trans('SurveySessionRef'); ?></dt>
            <dd><?php echo dol_escape_htmltag($session->ref); ?></dd>
            <?php foreach ($linkedObjectNames as $linkedObjectType => $names) : ?>
                <dt><?php echo dol_escape_htmltag($linkedObjectType); ?></dt>
                <dd><?php echo dol_escape_htmltag(implode(', ', $names)); ?></dd>
            <?php endforeach; ?>
            <dt><?php echo $langs->trans('SurveySessionCollect'); ?></dt>
            <dd><?php echo $period; ?></dd>
            <dt><?php echo $langs->trans('SurveySessionMode'); ?></dt>
            <dd><?php echo $langs->trans($isNominative ? 'SurveySessionModeNominative' : 'SurveySessionModeAnonymous'); ?></dd>
            <dt><?php echo $langs->trans('SurveySessionPrintedOn'); ?></dt>
            <dd><?php echo dol_print_date(dol_now(), 'dayhour', 'tzuserrel'); ?></dd>
        </dl>
    </header>

    <section class="survey-session-summary__figures">
        <div class="survey-session-summary__figure">
            <span class="survey-session-summary__figure-value"><?php echo $respondentCount; ?></span>
            <span class="survey-session-summary__figure-label"><?php echo $langs->trans('SurveySessionRespondents'); ?></span>
        </div>
        <?php // A response begun and not sent is no respondent : it is only counted here, apart ?>
        <div class="survey-session-summary__figure">
            <span class="survey-session-summary__figure-value"><?php echo (int) $summary['started']; ?></span>
            <span class="survey-session-summary__figure-label"><?php echo $langs->trans('SurveySessionStartedNotSent'); ?></span>
        </div>
        <div class="survey-session-summary__figure">
            <span class="survey-session-summary__figure-value"><?php echo (int) $summary['responses']; ?></span>
            <span class="survey-session-summary__figure-label"><?php echo $langs->trans('SurveySessionOpenedSurveys'); ?></span>
        </div>
        <?php // With invitees, the proof of the collect is the response rate : who was asked, who answered
        if (!empty($inviteeCounts['total'])) : ?>
            <div class="survey-session-summary__figure">
                <span class="survey-session-summary__figure-value"><?php echo (int) $inviteeCounts['total']; ?></span>
                <span class="survey-session-summary__figure-label"><?php echo $langs->trans('SurveySessionInviteesTotal'); ?></span>
            </div>
            <div class="survey-session-summary__figure">
                <span class="survey-session-summary__figure-value"><?php echo round(100 * (int) $inviteeCounts['answered'] / (int) $inviteeCounts['total']) . ' %'; ?></span>
                <span class="survey-session-summary__figure-label"><?php echo $langs->trans('SurveySessionResponseRate', (int) $inviteeCounts['answered'], (int) $inviteeCounts['total']); ?></span>
            </div>
        <?php endif; ?>
    </section>
    <p class="survey-session-summary__muted survey-session-summary__sent-only"><?php echo $langs->trans('SurveySessionSentOnlyHelp'); ?></p>

    <?php if ($respondentCount == 0) : ?>
        <p class="survey-session-summary__empty"><?php echo $langs->trans('SurveySessionNoResponse'); ?></p>
    <?php endif; ?>

    <?php $questionNumber = 0;
    foreach ($summary['questions'] as $questionSummary) :
        $question = $questionSummary['question'];
        $questionNumber++; ?>
        <section class="survey-session-summary__question">
            <h2 class="survey-session-summary__question-title"><?php echo $questionNumber . '. ' . dol_escape_htmltag($question->label ?: $question->ref); ?></h2>
            <div class="survey-session-summary__question-count">
                <?php echo $langs->trans('SurveySessionAnsweredBy', $questionSummary['answered'], $respondentCount); ?>
                <?php if ($question->type == 'MultipleChoices') : ?>
                    - <?php echo $langs->trans('SurveySessionSeveralChoices'); ?>
                <?php endif; ?>
            </div>

            <?php if ($questionSummary['kind'] == 'choice') : ?>
                <table class="survey-session-summary__options">
                    <?php foreach ($questionSummary['options'] as $option) : ?>
                        <tr>
                            <td class="survey-session-summary__option-label"><?php echo dol_escape_htmltag($option['label']); ?></td>
                            <td class="survey-session-summary__option-count"><?php echo $langs->trans('SurveySessionCountOutOf', $option['count'], $questionSummary['answered']); ?></td>
                            <td class="survey-session-summary__option-bar"><progress max="100" value="<?php echo (int) $option['percent']; ?>"><?php echo (int) $option['percent']; ?> %</progress></td>
                            <td class="survey-session-summary__option-percent"><?php echo (int) $option['percent']; ?> %</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php elseif ($questionSummary['kind'] == 'score' && $questionSummary['average'] !== null) : ?>
                <p class="survey-session-summary__average">
                    <?php echo $langs->trans('SurveySessionAverage'); ?> :
                    <strong><?php echo $formatNumber($questionSummary['average']) . ($question->type == 'Percentage' ? ' %' : ($questionSummary['max'] !== null ? ' / ' . $formatNumber($questionSummary['max']) : '')); ?></strong>
                    <span class="survey-session-summary__muted">(<?php echo $langs->trans('SurveySessionMinMax', $formatNumber(min($questionSummary['values'])), $formatNumber(max($questionSummary['values']))); ?>)</span>
                </p>
            <?php elseif ($questionSummary['kind'] == 'duration' && $questionSummary['average'] !== null) : ?>
                <p class="survey-session-summary__average">
                    <?php echo $langs->trans('SurveySessionAverage'); ?> : <strong><?php echo digiquali_format_duration((int) round($questionSummary['average'])); ?></strong>
                </p>
            <?php elseif ($questionSummary['kind'] == 'text' && !empty($questionSummary['texts'])) : ?>
                <ul class="survey-session-summary__verbatims">
                    <?php foreach ($questionSummary['texts'] as $text) : ?>
                        <li><?php echo dol_nl2br(dol_escape_htmltag($text['text'])); ?><?php echo $isNominative && $text['name'] !== '' ? ' <span class="survey-session-summary__muted">- ' . dol_escape_htmltag($text['name']) . '</span>' : ''; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($questionSummary['kind'] == 'other' && $questionSummary['answered'] > 0) : ?>
                <p class="survey-session-summary__muted"><?php echo $langs->trans('SurveySessionNotSummarized'); ?></p>
            <?php endif; ?>

            <?php if (!empty($questionSummary['comments'])) : ?>
                <h3 class="survey-session-summary__comments-title"><?php echo $langs->trans('Comments'); ?></h3>
                <ul class="survey-session-summary__verbatims">
                    <?php foreach ($questionSummary['comments'] as $comment) : ?>
                        <li><?php echo dol_nl2br(dol_escape_htmltag($comment['text'])); ?><?php echo $isNominative && $comment['name'] !== '' ? ' <span class="survey-session-summary__muted">- ' . dol_escape_htmltag($comment['name']) . '</span>' : ''; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if ($isNominative || !empty(array_filter(array_column($summary['respondents'], 'respondent_name')))) : ?>
        <section class="survey-session-summary__question survey-session-summary__respondents">
            <h2 class="survey-session-summary__question-title"><?php echo $langs->trans('SurveySessionRespondentList'); ?></h2>
            <table class="survey-session-summary__table">
                <tr>
                    <th><?php echo $langs->trans('SurveyRespondentName'); ?></th>
                    <th><?php echo $langs->trans('SurveyRespondentEmail'); ?></th>
                    <th><?php echo $langs->trans('DateCreation'); ?></th>
                    <th><?php echo $langs->trans('Status'); ?></th>
                </tr>
                <?php foreach ($summary['respondents'] as $respondent) : ?>
                    <tr>
                        <td><?php echo dol_escape_htmltag((string) $respondent->respondent_name); ?></td>
                        <td><?php echo dol_escape_htmltag((string) $respondent->respondent_email); ?></td>
                        <td><?php echo dol_print_date($respondent->date_creation, 'dayhour', 'tzuserrel'); ?></td>
                        <td><?php echo $respondent->getLibStatut(1); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>
    <?php endif; ?>
</div>
