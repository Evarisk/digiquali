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
 * \file    view/survey/survey_answers_export.php
 * \ingroup digiquali
 * \brief   CSV export of the answers of the responses of a collect session: one line per response, one column per
 *          question (and its comment).
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/survey.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../lib/digiquali_answer.lib.php';
require_once __DIR__ . '/../../lib/digiquali_survey.lib.php';

global $db, $langs, $user;

saturne_load_langs();

// Get parameters
$sessionId = GETPOSTINT('session_id');

if (!$user->hasRight('digiquali', 'survey', 'read')) {
    accessforbidden();
}

// Responses to export, oldest first
$sheet   = new Sheet($db);
$session = new Survey($db);
if ($sessionId <= 0 || $session->fetch($sessionId) <= 0 || !digiquali_survey_is_session($session) || $sheet->fetch((int) $session->fk_sheet) <= 0) {
    accessforbidden($langs->trans('ErrorRecordNotFound'), 0);
}
$surveys = array_reverse(digiquali_survey_session_get_responses($session), true);
$name    = 'session_' . $session->ref;

$questions = $sheet->fetchAllQuestions();

// Answers and comments of every survey, in one query
$lines = [];
if (!empty($surveys)) {
    $resql = $db->query('SELECT t.fk_survey, t.fk_question, t.answer, t.comment FROM ' . $db->prefix() . 'digiquali_surveydet as t WHERE t.status > 0 AND t.fk_survey IN (' . implode(',', array_map('intval', array_keys($surveys))) . ')');
    while ($resql && ($obj = $db->fetch_object($resql))) {
        $lines[(int) $obj->fk_survey][(int) $obj->fk_question] = $obj;
    }
}

/**
 * Get the text of an answer: the labels of the chosen options for a choice question, the value otherwise
 *
 * @param  Question    $question Question
 * @param  string|null $answer   Stored answer
 * @return string                Text of the answer, empty when not answered
 */
$answerText = function (Question $question, ?string $answer): string {
    if (digiquali_answer_is_empty($question->type, $answer)) {
        return '';
    }
    if (!digiquali_question_type_is_choice($question->type)) {
        return (string) $answer;
    }

    $options = digiquali_get_question_answers_by_position($question);
    $labels  = [];
    foreach (explode(',', (string) $answer) as $position) {
        $labels[] = isset($options[(int) $position]) ? $options[(int) $position]->value : $position;
    }

    return implode(', ', $labels);
};

/*
 * Output
 */

// The respondents of a nominative session are named, an anonymous one has nothing to show
$withRespondent = !empty(array_filter(array_column($surveys, 'respondent_name')));

$header = [$langs->transnoentities('SurveyAnswersExportRespondent'), $langs->transnoentities('DateCreation'), $langs->transnoentities('Status')];
if ($withRespondent) {
    array_splice($header, 1, 0, [$langs->transnoentities('SurveyRespondentName'), $langs->transnoentities('SurveyRespondentEmail')]);
}
foreach ($questions as $question) {
    $header[] = $question->label ?: $question->ref;
    $header[] = ($question->label ?: $question->ref) . ' - ' . $langs->transnoentities('Comment');
}

// The answers come from anonymous respondents : a cell starting like a formula (=, +, -, @, tab, return) is opened as text
// by the spreadsheet, not run (CSV injection)
$csvCell = function ($value): string {
    $value = (string) $value;
    // A plain number (a negative score, a range answer) stays a number
    return ($value !== '' && !is_numeric($value) && strpos("=+-@\t\r", $value[0]) !== false) ? "'" . $value : $value;
};

top_httphead('text/csv');
header('Content-Disposition: attachment; filename="' . dol_sanitizeFileName($name) . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM, for the spreadsheets
fputcsv($out, array_map($csvCell, $header), ';');
foreach ($surveys as $survey) {
    $row = [$survey->ref, dol_print_date($survey->date_creation, 'dayhour', 'tzuserrel'), html_entity_decode(strip_tags($survey->getLibStatut(1)), ENT_QUOTES, 'UTF-8')];
    if ($withRespondent) {
        array_splice($row, 1, 0, [(string) $survey->respondent_name, (string) $survey->respondent_email]);
    }
    foreach ($questions as $question) {
        $line  = $lines[(int) $survey->id][(int) $question->id] ?? null;
        $row[] = $answerText($question, $line->answer ?? null);
        $row[] = (string) ($line->comment ?? '');
    }
    fputcsv($out, array_map($csvCell, $row), ';');
}
fclose($out);

$db->close();
