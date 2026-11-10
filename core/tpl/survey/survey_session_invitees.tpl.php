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
 * \file    core/tpl/survey/survey_session_invitees.tpl.php
 * \ingroup digiquali
 * \brief   Invitees of a nominative collect session, on the card of the session: the list to fill (one by one or pasted),
 *          the invitations and the reminders to send, and where each invitee stands.
 *
 * Expected variables (set by view/survey/survey_card.php):
 * - Survey   $object           Session
 * - array    $sessionInvitees  Invitees of the session, see digiquali_survey_session_get_invitees()
 * - Survey[] $sessionResponses Responses of the session, by id
 * - bool     $isNominative     Whether the respondents give their name
 * - int      $permissiontoadd  Whether the user may change the session
 */

global $langs;

$isOpen        = $object->status == Survey::STATUS_DRAFT;
$canInvite     = $permissiontoadd && $isOpen && $isNominative;
$inviteeCounts = digiquali_survey_session_count_invitees($sessionInvitees, $sessionResponses);
$cardUrl       = $_SERVER['PHP_SELF'] . '?id=' . ((int) $object->id);

// An anonymous session invites nobody: its respondents are not known
if (!$isNominative && empty($sessionInvitees)) {
    if ($permissiontoadd && $isOpen) {
        print '<p class="opacitymedium" id="survey-session-invitees"><i class="fas fa-envelope pictofixedwidth"></i>' . $langs->trans('SurveySessionInviteesNominativeOnly') . '</p>';
    }
    return;
}

// Invitations to those who have not had their link yet, reminders to those who had it and have not sent their response
$buttons = '';
if ($permissiontoadd) {
    $sendButtons = [
        'send_invitations' => ['label' => $langs->trans('SurveySessionSendInvitations', $inviteeCounts['new']), 'icon' => 'fa-paper-plane', 'count' => $inviteeCounts['new'], 'none' => 'SurveySessionSendInvitationsNone'],
        // Those reminded less than a day ago are not counted : the button reminds nobody twice the same day
        'remind_invitees'  => ['label' => $langs->trans('SurveySessionRemindNonRespondents', $inviteeCounts['remindable']), 'icon' => 'fa-redo', 'count' => $inviteeCounts['remindable'], 'none' => 'SurveySessionRemindNone'],
    ];
    foreach ($sendButtons as $sendAction => $sendButton) {
        $buttonContent = '<i class="fas ' . $sendButton['icon'] . '"></i> ' . $sendButton['label'];
        if ($canInvite && $sendButton['count'] > 0) {
            $buttons .= '<a class="butAction" href="' . $cardUrl . '&action=' . $sendAction . '&token=' . newToken() . '">' . $buttonContent . '</a>';
        } else {
            if (!$isOpen) {
                $refusedReason = $langs->trans('SurveySessionClosedNoInvitation');
            } elseif ($sendAction == 'remind_invitees' && $inviteeCounts['reminded_recently'] > 0) {
                $refusedReason = $langs->trans('SurveySessionRemindSkippedRecent', $inviteeCounts['reminded_recently'], dol_print_date($inviteeCounts['next_reminder'], 'dayhour', 'tzuserrel'));
            } else {
                $refusedReason = $langs->trans($sendButton['none']);
            }
            $buttons .= '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($refusedReason) . '">' . $buttonContent . '</span>';
        }
    }
}

print '<div class="survey-session__invitees" id="survey-session-invitees">';
print load_fiche_titre($langs->trans('SurveySessionInvitees', $inviteeCounts['total']), $buttons, 'fontawesome_fa-envelope_fas');

print '<div class="survey-session__infos opacitymedium">';
// The response rate counts the invitees who got their link
$invitedCount = $inviteeCounts['total'] - $inviteeCounts['new'];
if ($invitedCount > 0) {
    print '<span>' . $langs->trans('SurveySessionInviteesAnswered', $inviteeCounts['answered'], $invitedCount, round($inviteeCounts['answered'] / $invitedCount * 100) . ' %') . '</span>';
    print '<span>' . $langs->trans('SurveySessionInviteesToRemind', $inviteeCounts['to_remind']) . '</span>';
    // Told in the page, not only in the tooltip of the button, which a tablet does not show
    if ($inviteeCounts['reminded_recently'] > 0 && $isOpen) {
        print '<span>' . $langs->trans('SurveySessionRemindSkippedRecent', $inviteeCounts['reminded_recently'], dol_print_date($inviteeCounts['next_reminder'], 'dayhour', 'tzuserrel')) . '</span>';
    }
}
if ($inviteeCounts['new'] > 0) {
    print '<span>' . $langs->trans('SurveySessionInviteesNotSent', $inviteeCounts['new']) . '</span>';
}
print '<span>' . $langs->trans($isOpen ? 'SurveySessionInviteesHelp' : 'SurveySessionClosedNoInvitation') . '</span>';
print '</div>';

// One invitee typed in the fields, or several pasted, one per line
if ($canInvite) {
    print '<form method="POST" class="survey-session__options survey-session__invitee-form" action="' . $cardUrl . '#survey-session-invitees">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add_invitees">';
    print '<label class="survey-session__option"><span class="survey-session__option-label">' . $langs->trans('SurveyRespondentName') . '</span>';
    print '<input type="text" name="invitee_name" class="flat minwidth200" maxlength="255" autocomplete="off"></label>';
    print '<label class="survey-session__option"><span class="survey-session__option-label">' . $langs->trans('SurveyRespondentEmail') . '</span>';
    print '<input type="email" name="invitee_email" class="flat minwidth200" maxlength="255" autocomplete="off"></label>';
    print '<details class="survey-session__option"><summary class="survey-session__option-label">' . $langs->trans('SurveySessionInviteesPaste') . '</summary>';
    print '<textarea name="invitee_lines" rows="5" cols="60" class="flat" placeholder="' . dol_escape_htmltag($langs->transnoentities('SurveySessionInviteesPastePlaceholder')) . '"></textarea>';
    print '<span class="survey-session__mode-locked">' . $langs->trans('SurveySessionInviteesPasteHelp', digiquali_survey_session_invitees_max()) . '</span>';
    print '</details>';
    print '<input type="submit" class="button button-save" value="' . dol_escape_htmltag($langs->trans('SurveySessionInviteeAdd')) . '">';
    print '</form>';
}

// Scrolls by itself on a phone instead of widening the page
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>' . $langs->trans('SurveySessionInvitee') . '</td><td>' . $langs->trans('SurveyRespondentEmail') . '</td>';
print '<td>' . $langs->trans('SurveySessionInviteeSentOn') . '</td><td>' . $langs->trans('SurveySessionInviteeRemindedOn') . '</td>';
print '<td>' . $langs->trans('SurveySessionInviteeResponse') . '</td>' . ($canInvite ? '<td></td>' : '') . '</tr>';
if (empty($sessionInvitees)) {
    print '<tr><td colspan="' . ($canInvite ? 6 : 5) . '"><span class="opacitymedium">' . $langs->trans('SurveySessionNoInvitee') . '</span></td></tr>';
}
foreach ($sessionInvitees as $invitee) {
    $inviteeState    = digiquali_survey_session_invitee_state($invitee, $sessionResponses);
    $inviteeResponse = $sessionResponses[$invitee['response_id']] ?? null;

    print '<tr class="oddeven">';
    print '<td>' . dol_escape_htmltag($invitee['name']) . '</td>';
    print '<td>' . dol_escape_htmltag($invitee['email']) . '</td>';
    print '<td>' . (!empty($invitee['date_invited']) ? dol_print_date($invitee['date_invited'], 'dayhour', 'tzuserrel') : '<span class="opacitymedium">' . $langs->trans('SurveySessionInviteeNotSent') . '</span>') . '</td>';
    print '<td>';
    if (!empty($invitee['date_reminded'])) {
        print dol_print_date($invitee['date_reminded'], 'dayhour', 'tzuserrel');
        print $invitee['reminders'] > 1 ? ' <span class="opacitymedium">(' . $langs->trans('SurveySessionInviteeReminderCount', $invitee['reminders']) . ')</span>' : '';
    }
    print '</td>';
    print '<td>';
    // A response tied through the common link carries an e-mail the respondent typed, which proves nothing : said here
    $inviteeResponseVia = (!empty($invitee['response_common']) && $inviteeResponse !== null) ? ' <span class="opacitymedium">(' . $langs->trans('SurveySessionInviteeViaCommonLink') . ')</span>' : '';
    if ($inviteeState == 'answered') {
        print dolGetStatus($langs->trans('SurveySessionInviteeAnswered'), '', '', 'status4', 5) . ' ' . $inviteeResponse->getNomUrl(1) . $inviteeResponseVia;
    } elseif ($inviteeState == 'started') {
        print dolGetStatus($langs->trans('SurveySessionInviteeStarted'), '', '', 'status1', 5) . ' ' . $inviteeResponse->getNomUrl(1) . $inviteeResponseVia;
    } elseif ($inviteeState == 'invited') {
        print dolGetStatus($langs->trans('SurveySessionInviteeNoAnswer'), '', '', 'status0', 5);
    }
    print '</td>';
    if ($canInvite) {
        print '<td class="right"><a class="reposition" href="' . $cardUrl . '&action=remove_invitee&invitee_id=' . ((int) $invitee['id']) . '&token=' . newToken() . '">' . img_delete($langs->trans('SurveySessionInviteeRemove')) . '</a></td>';
    }
    print '</tr>';
}
print '</table>';
print '</div>';
print '</div>';
