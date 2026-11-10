<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/survey/survey_session_summary.php
 * \ingroup digiquali
 * \brief   Summary of a collect session, to print as the proof of an evaluation: respondents, the answers to each
 *          question counted, the average of the scores and the comments.
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/survey.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../lib/digiquali_answer.lib.php';
require_once __DIR__ . '/../../lib/digiquali_survey.lib.php';

global $conf, $db, $langs, $mysoc, $user;

saturne_load_langs();

// Get parameters
$id = GETPOSTINT('id');

// Initialize technical objects
$session = new Survey($db);
$sheet   = new Sheet($db);

if (!$user->hasRight('digiquali', 'survey', 'read')) {
    accessforbidden();
}
if ($id <= 0 || $session->fetch($id) <= 0 || $session->status < Survey::STATUS_DRAFT || !digiquali_survey_is_session($session)) {
    accessforbidden($langs->trans('ErrorRecordNotFound'), 0);
}
$sheet->fetch($session->fk_sheet);

// The summary tells the true state of the collect
digiquali_survey_session_close_if_due($session);

$responses = digiquali_survey_session_get_responses($session);
$summary   = digiquali_survey_session_get_summary($sheet, $responses);

// Invitees of a nominative session, for its response rate: see digiquali_survey_session_count_invitees()
$inviteeCounts = digiquali_survey_session_count_invitees(digiquali_survey_session_get_invitees($session), $responses);

// What the session is about: a project, a contract, a third party...
$linkedObjectNames = [];
$session->fetchObjectLinked(null, '', $session->id, 'digiquali_survey', 'OR', 1, 'sourcetype', 0);
foreach (saturne_get_objects_metadata() as $objectMetadata) {
    if (empty($objectMetadata['conf']) || empty($session->linkedObjects[$objectMetadata['link_name']])) {
        continue;
    }
    foreach ($session->linkedObjects[$objectMetadata['link_name']] as $linkedObject) {
        $linkedObjectName = $linkedObject->ref ?? '';
        if (!empty($objectMetadata['label_field']) && !empty($linkedObject->{$objectMetadata['label_field']}) && $linkedObject->{$objectMetadata['label_field']} != $linkedObjectName) {
            $linkedObjectName .= ' - ' . $linkedObject->{$objectMetadata['label_field']};
        }
        $linkedObjectNames[$langs->transnoentities($objectMetadata['langs'])][] = $linkedObjectName;
    }
}

/*
 * View
 */

$title = $langs->trans('SurveySessionSummary') . ' - ' . digiquali_survey_get_title($session, $sheet);

saturne_header(0, '', $title, 'FR:Module_DigiQuali', '', 0, 0, [], [], '', 'survey-session-summary-page');

require __DIR__ . '/../../core/tpl/survey/survey_session_summary.tpl.php';

llxFooter();
$db->close();
