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
 * \file    core/tpl/frontend/public_survey_session.tpl.php
 * \ingroup digiquali
 * \brief   Welcome screen of a collect session: what is asked, until when, who keeps the answers, and the button to
 *          answer, after the name of the respondent in a nominative session.
 *
 * Expected variables (set by public/survey/public_survey_session.php):
 * - Survey      $session         Session
 * - Sheet       $sheet           Sheet of the session
 * - bool        $isOpen          Whether the collect is open
 * - bool        $isNominative    Whether the respondents give their name
 * - Survey|null $response        Survey this browser (or the personal link of the invitee) already started
 * - int         $error           Whether the survey of the respondent could not be created
 * - string[]    $fieldErrors     Errors on the name and e-mail of the respondent, by field
 * - string      $respondentName  Name posted
 * - string      $respondentEmail E-mail posted
 * - array|null  $invitee         Invitee whose personal link opened the page: only his own name is shown
 * - bool        $inviteeAnswered Whether this invitee has already sent his response
 * - bool        $inviteeCommonAnswered Whether a response typed with his e-mail was sent through the common link
 * - string      $sessionPageUrl  URL the page was opened with, to post to
 * - string      $startToken      Token of the page for this visitor, posted back by the start button
 * - string      $startRefused    Language key of the reason why the last start was refused, empty otherwise
 */

global $conf, $langs, $mysoc;

$sessionTitle = digiquali_survey_get_title($session, $sheet);
$sheetTitle   = $sheet->label ?: $sheet->ref;
$askName      = $isOpen && $isNominative && empty($response) && empty($invitee);
?>
<div class="pwa-container public-control-screen public-survey-session">
    <div class="public-control-sheet">
        <h1 class="public-survey-session__title"><?php echo dol_escape_htmltag($sessionTitle); ?></h1>
        <?php if ($sheetTitle !== $sessionTitle) : ?>
            <div class="public-survey-session__subtitle"><?php echo dol_escape_htmltag($sheetTitle); ?></div>
        <?php endif; ?>
        <?php if (dol_strlen(trim(strip_tags((string) $sheet->description))) > 0) : ?>
            <div class="public-survey-session__description"><?php echo dolPrintHTML($sheet->description); ?></div>
        <?php endif; ?>

        <?php if (!empty($invitee) && $isOpen && empty($inviteeAnswered)) : ?>
            <p class="public-survey-session__deadline">
                <i class="fas fa-user"></i>
                <?php echo $langs->transnoentities('SurveySessionInviteeWelcome', dol_escape_htmltag($invitee['name'])); ?>
            </p>
            <?php if (!empty($inviteeCommonAnswered)) : ?>
                <p class="public-survey-session__deadline">
                    <i class="fas fa-info-circle"></i>
                    <?php echo $langs->transnoentities('SurveySessionInviteeCommonAnswered'); ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($inviteeAnswered)) : ?>
            <div class="public-survey-session__closed">
                <i class="fas fa-check"></i> <?php echo $langs->transnoentities('SurveySessionInviteeAnsweredPublic'); ?>
            </div>
        <?php elseif (!$isOpen) : ?>
            <div class="public-survey-session__closed">
                <i class="fas fa-lock"></i> <?php echo $langs->transnoentities('SurveySessionClosedPublic'); ?>
            </div>
        <?php else : ?>
            <?php if (!empty($session->date_end)) : ?>
                <p class="public-survey-session__deadline">
                    <i class="far fa-clock"></i>
                    <?php echo $langs->transnoentities('SurveySessionOpenUntil', dol_print_date($session->date_end, 'daytext', digiquali_survey_session_tz()), dol_print_date($session->date_end, 'hour', digiquali_survey_session_tz())); ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($error)) : ?>
                <div class="public-survey-session__closed"><?php echo $langs->transnoentities('ErrorFailedToSaveFile'); ?></div>
            <?php endif; ?>
            <?php if (!empty($startRefused)) : ?>
                <div class="public-survey-session__closed" role="alert"><?php echo $langs->transnoentities($startRefused, round(digiquali_survey_session_start_window() / 60)); ?></div>
            <?php endif; ?>
            <form method="POST" class="public-survey-session__form" action="<?php echo dol_escape_htmltag($sessionPageUrl); ?>">
                <input type="hidden" name="action" value="start">
                <input type="hidden" name="start_token" value="<?php echo dol_escape_htmltag($startToken); ?>">
                <?php if ($askName) : ?>
                    <div class="public-survey-session__field">
                        <label for="respondent_name"><?php echo $langs->transnoentities('SurveyRespondentName'); ?> <span class="public-survey-session__required">*</span></label>
                        <input type="text" id="respondent_name" name="respondent_name" maxlength="255" required autocomplete="name" value="<?php echo dol_escape_htmltag($respondentName); ?>"<?php echo !empty($fieldErrors['respondent_name']) ? ' aria-invalid="true" aria-describedby="respondent_name_error"' : ''; ?>>
                        <?php if (!empty($fieldErrors['respondent_name'])) : ?>
                            <span class="public-survey-session__error" id="respondent_name_error"><?php echo dol_escape_htmltag($fieldErrors['respondent_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="public-survey-session__field">
                        <label for="respondent_email"><?php echo $langs->transnoentities('SurveyRespondentEmail'); ?> <span class="public-survey-session__optional">(<?php echo $langs->transnoentities('SurveyRespondentOptional'); ?>)</span></label>
                        <input type="email" id="respondent_email" name="respondent_email" maxlength="255" autocomplete="email" inputmode="email" value="<?php echo dol_escape_htmltag($respondentEmail); ?>"<?php echo !empty($fieldErrors['respondent_email']) ? ' aria-invalid="true" aria-describedby="respondent_email_error"' : ''; ?>>
                        <?php if (!empty($fieldErrors['respondent_email'])) : ?>
                            <span class="public-survey-session__error" id="respondent_email_error"><?php echo dol_escape_htmltag($fieldErrors['respondent_email']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <button type="submit" class="wpeo-button button-primary public-survey-session__start">
                    <i class="fas <?php echo !empty($response) ? 'fa-redo' : 'fa-play'; ?>"></i>
                    <?php echo $langs->transnoentities(!empty($response) ? 'SurveySessionResume' : 'SurveySessionStart'); ?>
                </button>
            </form>
            <p class="public-survey-session__hint"><?php echo $langs->transnoentities('SurveySessionOwnSurvey'); ?></p>
        <?php endif; ?>

        <p class="public-survey-session__privacy">
            <i class="fas fa-info-circle"></i>
            <?php echo $langs->transnoentities(!empty($invitee) ? 'SurveySessionPrivacyInvitee' : ($isNominative ? 'SurveySessionPrivacyNominative' : 'SurveySessionPrivacyAnonymous'), dol_escape_htmltag($mysoc->name)); ?>
        </p>
    </div>
</div>
