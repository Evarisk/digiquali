<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_survey.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Survey
 */

// Load Saturne libraries
dol_include_once('/saturne/lib/object.lib.php');

/**
 * Prepare array of tabs for survey
 *
 * @param  Survey $object Survey object
 * @return array          Array of tabs
 * @throws Exception
 */
function survey_prepare_head(Survey $object): array
{
    // Global variables definitions
    global $conf, $langs;

    $head[1][0] = dol_buildpath('/digiquali/view/object_medias.php', 1) . '?id=' . $object->id . '&object_type=survey';
    $head[1][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-image pictofixedwidth"></i>' . $langs->trans('Medias') : '<i class="fas fa-file-image"></i>';
    $head[1][2] = 'medias';

    $moreparam['documentType']       = 'SurveyDocument';
    $moreparam['attendantTableMode'] = 'simple';

    return saturne_object_prepare_head($object, $head, $moreparam, true);
}

/**
 * Get the image of a QR code encoding an URL, drawn on the fly: no file is left behind
 *
 * @param  string $url   URL to encode
 * @param  int    $size  Width and height of the image, in pixels
 * @param  string $class CSS class of the image
 * @return string        Image tag (an inline SVG), empty when the barcode library is missing
 */
function digiquali_qrcode_img(string $url, int $size = 180, string $class = 'digiquali-qrcode'): string
{
    $svg = digiquali_qrcode_svg($url);
    if ($svg === '') {
        return '';
    }

    return '<img class="' . dol_escape_htmltag($class) . '" src="' . digiquali_qrcode_data_uri($url) . '" width="' . $size . '" height="' . $size . '" alt="' . dol_escape_htmltag($url) . '">';
}

/**
 * Get the data URI of a QR code encoding an URL, to show it or download it
 *
 * @param  string $url URL to encode
 * @return string      Data URI of an SVG image, empty when the barcode library is missing
 */
function digiquali_qrcode_data_uri(string $url): string
{
    $svg = digiquali_qrcode_svg($url);

    return $svg === '' ? '' : 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Get the SVG code of a QR code encoding an URL
 *
 * @param  string $url URL to encode
 * @return string      SVG code, empty when the barcode library is missing
 */
function digiquali_qrcode_svg(string $url): string
{
    if (!class_exists('TCPDF2DBarcode')) {
        if (!file_exists(DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf_barcodes_2d.php')) {
            return '';
        }
        require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
    }

    $barcode = new TCPDF2DBarcode($url, 'QRCODE,M');

    return (string) $barcode->getBarcodeSVGcode(6, 6, 'black');
}

/**
 * Get the URL a respondent opens to answer a survey on the public interface
 *
 * @param  Survey $survey Survey
 * @return string         Absolute URL
 */
function digiquali_survey_public_answer_url(Survey $survey): string
{
    global $conf;

    return dol_buildpath('/digiquali/public/public_answer.php', 3) . '?track_id=' . urlencode($survey->track_id) . '&object_type=' . $survey->element . '&document_type=SurveyDocument&entity=' . ((int) $conf->entity);
}

/*
 * Collect sessions
 *
 * A session is a survey used as a template: one link, shown as a QR code, gives each respondent a survey of his own,
 * cloned from it, so that no respondent ever writes over another one. No table of its own: the session is marked by a
 * link of the survey to itself, and each response by a link to its session, in llx_element_element. The session is
 * open while the survey is a draft, closing it validates it, by hand or once its planned closing date (date_end) is past.
 * An anonymous session asks nothing of its respondents ; a nominative one (nominative = 1) asks their name and, if they
 * wish, their e-mail, kept on their response (respondent_name, respondent_email).
 */

/**
 * Element type of the links of a collect session in llx_element_element
 *
 * @return string Element type
 */
function digiquali_survey_session_link_type(): string
{
    return 'digiquali_surveysession';
}

/**
 * Tell whether a survey is a collect session
 *
 * @param  Survey $survey Survey
 * @return bool           True for a session
 */
function digiquali_survey_is_session(Survey $survey): bool
{
    return !empty($survey->id) && in_array((int) $survey->id, digiquali_survey_get_session_ids([$survey->id]), true);
}

/**
 * Get the ids of the collect sessions among a set of surveys, in one query
 *
 * @param  int[] $surveyIds Survey ids
 * @return int[]            Ids of the sessions
 */
function digiquali_survey_get_session_ids(array $surveyIds): array
{
    global $db;

    $surveyIds = array_filter(array_map('intval', $surveyIds));
    if (empty($surveyIds)) {
        return [];
    }

    $sql   = 'SELECT fk_source FROM ' . $db->prefix() . "element_element WHERE sourcetype = '" . digiquali_survey_session_link_type() . "' AND targettype = '" . digiquali_survey_session_link_type() . "'";
    $sql  .= ' AND fk_source = fk_target AND fk_source IN (' . implode(',', $surveyIds) . ')';
    $resql = $db->query($sql);

    $sessionIds = [];
    while ($resql && ($obj = $db->fetch_object($resql))) {
        $sessionIds[] = (int) $obj->fk_source;
    }

    return $sessionIds;
}

/**
 * Get the collect session a survey was handed out by, when it is the response of one
 *
 * @param  Survey      $response Survey
 * @return Survey|null           The session, null for a survey outside any session
 */
function digiquali_survey_get_response_session(Survey $response): ?Survey
{
    global $db;

    if (empty($response->id)) {
        return null;
    }

    $sql   = 'SELECT fk_source FROM ' . $db->prefix() . "element_element WHERE sourcetype = '" . digiquali_survey_session_link_type() . "'";
    $sql  .= " AND targettype = 'digiquali_survey' AND fk_target = " . ((int) $response->id);
    $resql = $db->query($sql);
    $obj   = $resql ? $db->fetch_object($resql) : null;
    if (empty($obj)) {
        return null;
    }

    $session = new Survey($db);

    return $session->fetch((int) $obj->fk_source) > 0 ? $session : null;
}

/**
 * Turn a draft survey into a collect session
 *
 * @param  Survey $survey Survey, a draft
 * @return int            > 0 when done, < 0 on error
 */
function digiquali_survey_open_session(Survey $survey): int
{
    global $db;

    if ($survey->status != Survey::STATUS_DRAFT) {
        return -1;
    }
    if (digiquali_survey_is_session($survey)) {
        return 1;
    }

    $sql  = 'INSERT INTO ' . $db->prefix() . 'element_element (fk_source, sourcetype, fk_target, targettype)';
    $sql .= ' VALUES (' . ((int) $survey->id) . ", '" . digiquali_survey_session_link_type() . "', " . ((int) $survey->id) . ", '" . digiquali_survey_session_link_type() . "')";

    return $db->query($sql) ? 1 : -1;
}

/**
 * Get the key of the public link of a session: the track id of the survey stays secret, so that nobody answers the
 * template itself
 *
 * @param  Survey $session Session
 * @return string          Key
 */
function digiquali_survey_session_key(Survey $session): string
{
    return substr(hash('sha256', $session->id . '|' . $session->track_id . '|digiquali_survey_session'), 0, 24);
}

/**
 * Get the time zone a date of a session is printed in. 'tzuserrel' reads the time zone of the browser kept in the session
 * of a logged user, else MAIN_DOLIBARR_USER_TIMEZONE, else UTC : the visitor of a public page, or an e-mail sent by a
 * scheduled job, got the closing hour in UTC (17:00 for 18:00 in Paris). Out of a user session, the time of the server is
 * the one of the company
 *
 * @return string 'tzuserrel' or 'tzserver', for dol_print_date()
 */
function digiquali_survey_session_tz(): string
{
    return (!empty($_SESSION['dol_tz_string']) || getDolGlobalString('MAIN_DOLIBARR_USER_TIMEZONE')) ? 'tzuserrel' : 'tzserver';
}

/**
 * Get the public link of a session, the one shown as a QR code
 *
 * @param  Survey $session Session
 * @return string          Absolute URL
 */
function digiquali_survey_session_url(Survey $session): string
{
    global $conf;

    return dol_buildpath('/digiquali/public/survey/public_survey_session.php', 3) . '?id=' . ((int) $session->id) . '&key=' . digiquali_survey_session_key($session) . '&entity=' . ((int) $conf->entity);
}

/**
 * Get the token of the welcome page of a session for this visitor: a random value kept in his PHP session and written
 * in the form of the page. The start button posts it back, so that a response is only ever created by someone who
 * opened the page, never by a bare request
 *
 * @param  Survey $session Session
 * @return string          Token
 */
function digiquali_survey_session_start_token(Survey $session): string
{
    $slot = 'digiquali_survey_session_start_token_' . ((int) $session->id);
    if (empty($_SESSION[$slot]) || !is_string($_SESSION[$slot])) {
        $_SESSION[$slot] = bin2hex(random_bytes(16));
    }

    return $_SESSION[$slot];
}

/**
 * Tell whether a token posted by the start button is the one of the welcome page this visitor opened
 *
 * @param  Survey $session Session
 * @param  string $token   Token posted
 * @return bool            True when it matches
 */
function digiquali_survey_session_check_start_token(Survey $session, string $token): bool
{
    $slot = 'digiquali_survey_session_start_token_' . ((int) $session->id);

    return $token !== '' && !empty($_SESSION[$slot]) && is_string($_SESSION[$slot]) && hash_equals($_SESSION[$slot], $token);
}

/**
 * Get the largest number of responses one connection starts in a session through its common link, within
 * digiquali_survey_session_start_window() seconds: a whole room behind one router fits in, a burst does not
 *
 * @return int Number of responses
 */
function digiquali_survey_session_start_max(): int
{
    return 40;
}

/**
 * Get the time window of digiquali_survey_session_start_max()
 *
 * @return int Seconds
 */
function digiquali_survey_session_start_window(): int
{
    return 600;
}

/**
 * Tell whether the visitor may start one more response in a session through its common link, and count it if so
 *
 * A guard against creations in bursts: one connection starts at most digiquali_survey_session_start_max() responses in
 * digiquali_survey_session_start_window() seconds. The connections are kept hashed with the key of the session, for the
 * time of the window only, in a file of the temporary directory of the module: no table, and nothing of the respondent
 * kept with his response, so that an anonymous session stays anonymous.
 *
 * @param  Survey $session Session
 * @return bool            True when the start is allowed (and counted), false when the connection has started too many
 */
function digiquali_survey_session_start_allowed(Survey $session): bool
{
    global $conf;

    $dir = !empty($conf->digiquali->dir_temp) ? $conf->digiquali->dir_temp : DOL_DATA_ROOT . '/digiquali/temp';
    if (!is_dir($dir) && dol_mkdir($dir) < 0) {
        return true; // Nowhere to count: the page goes on, the token of the page still stops the bare requests
    }

    $handle = @fopen($dir . '/survey_session_starts_' . ((int) $session->id) . '.json', 'c+');
    if ($handle === false) {
        return true;
    }

    $now        = dol_now();
    $connection = substr(hash('sha256', getUserRemoteIP() . '|' . digiquali_survey_session_key($session)), 0, 16);
    $allowed    = true;
    if (flock($handle, LOCK_EX)) {
        $starts = json_decode((string) stream_get_contents($handle), true);
        $starts = is_array($starts) ? $starts : [];

        // Only the starts of the window are kept
        foreach ($starts as $hash => $dates) {
            $starts[$hash] = array_values(array_filter((array) $dates, function ($date) use ($now): bool {
                return (int) $date > $now - digiquali_survey_session_start_window();
            }));
            if (empty($starts[$hash])) {
                unset($starts[$hash]);
            }
        }

        $allowed = count($starts[$connection] ?? []) < digiquali_survey_session_start_max();
        if ($allowed) {
            $starts[$connection][] = $now;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode($starts));
        fflush($handle);
        flock($handle, LOCK_UN);
    }
    fclose($handle);

    return $allowed;
}

/**
 * Get the responses of a session, most recent first
 *
 * @param  Survey   $session Session
 * @return Survey[]          Responses, by id
 */
function digiquali_survey_session_get_responses(Survey $session): array
{
    global $db;

    $sql   = 'SELECT ee.fk_target FROM ' . $db->prefix() . "element_element as ee WHERE ee.sourcetype = '" . digiquali_survey_session_link_type() . "' AND ee.targettype = 'digiquali_survey'";
    $sql  .= ' AND ee.fk_source = ' . ((int) $session->id);
    $resql = $db->query($sql);

    $ids = [];
    while ($resql && ($obj = $db->fetch_object($resql))) {
        $ids[] = (int) $obj->fk_target;
    }
    if (empty($ids)) {
        return [];
    }

    $responses = saturne_fetch_all_object_type('Survey', 'DESC', 't.rowid', 0, 0, ['customsql' => 't.rowid IN (' . implode(',', $ids) . ') AND t.status >= 0']);

    return is_array($responses) ? $responses : [];
}

/**
 * Create the survey of a new respondent of a session: a clone of the session, without answers
 *
 * It is created in the name of the owner of the session, never of another user: the public page has no user.
 *
 * @param  Survey      $session         Session, open
 * @param  string      $respondentName  Name the respondent gave, kept in a nominative session only
 * @param  string      $respondentEmail E-mail the respondent gave, kept in a nominative session only
 * @return Survey|null                  Survey of the respondent, null on error
 */
function digiquali_survey_session_create_response(Survey $session, string $respondentName = '', string $respondentEmail = ''): ?Survey
{
    global $db;

    $owner = digiquali_survey_session_owner($session);

    $responseId = $session->createFromClone($owner, (int) $session->id, ['answers' => 0, 'attendants' => 0, 'photos' => 0]);
    if ($responseId <= 0) {
        return null;
    }

    // A response is no session: no closing date nor mode of its own. It keeps a name only in a nominative session, an
    // anonymous one keeps nothing of its respondent
    $isNominative    = !empty($session->nominative);
    $respondentName  = $isNominative ? dol_trunc(trim($respondentName), 255, 'right', 'UTF-8', 1) : '';
    $respondentEmail = $isNominative ? dol_trunc(trim($respondentEmail), 255, 'right', 'UTF-8', 1) : '';

    $sql  = 'UPDATE ' . $db->prefix() . 'digiquali_survey SET date_end = NULL, nominative = 0';
    $sql .= ', respondent_name = ' . ($respondentName !== '' ? "'" . $db->escape($respondentName) . "'" : 'NULL');
    $sql .= ', respondent_email = ' . ($respondentEmail !== '' ? "'" . $db->escape($respondentEmail) . "'" : 'NULL');
    $sql .= ' WHERE rowid = ' . ((int) $responseId);
    $db->query($sql);

    $response = new Survey($db);
    if ($response->fetch($responseId) <= 0) {
        return null;
    }
    $response->add_object_linked(digiquali_survey_session_link_type(), $session->id, $owner, 1);

    return $response;
}

/**
 * Get the user a collect session acts in the name of when nobody is logged in: its owner, never another user
 *
 * @param  Survey $session Session
 * @return User            Owner of the session, an empty user when it no longer exists
 */
function digiquali_survey_session_owner(Survey $session): User
{
    global $db;

    $owner = new User($db);
    if (empty($session->fk_user_creat) || $owner->fetch((int) $session->fk_user_creat) <= 0) {
        $owner = new User($db);
    }

    return $owner;
}

/**
 * Close a collect session once its planned closing date is past
 *
 * Checked when the public page or the card of the session is opened, so that no scheduled job is needed: the session is
 * closed as by the button of its card, in the name of its owner.
 *
 * @param  Survey $session Session
 * @return bool            True when the session has just been closed
 */
function digiquali_survey_session_close_if_due(Survey $session): bool
{
    if ($session->status != Survey::STATUS_DRAFT || empty($session->date_end) || $session->date_end > dol_now() || !digiquali_survey_is_session($session)) {
        return false;
    }

    // validate() gives the session its definitive reference, a closed session kept its (PROV) one
    return $session->validate(digiquali_survey_session_owner($session)) > 0;
}

/**
 * Tell whether the collect session a response belongs to no longer takes answers, closing it first when its planned
 * closing date is past
 *
 * Checked by every page an answer goes through (the public answer page, the auto-save, the final submit, the app, the
 * card), so that a planned closing date applies the very moment it is past, even when nobody opens the page of the
 * session: an answer posted afterwards is refused, and the session gets closed then.
 *
 * @param  Survey $response Survey
 * @return bool             True for a response of a closed session, false otherwise (a survey outside any session too)
 */
function digiquali_survey_response_session_closed(Survey $response): bool
{
    $session = digiquali_survey_get_response_session($response);
    if ($session === null) {
        return false;
    }
    digiquali_survey_session_close_if_due($session);

    return (int) $session->status !== Survey::STATUS_DRAFT;
}

/**
 * Tell whether a survey is scored: a success rate to reach, and answers marked correct in the questions of its sheet (see
 * Survey::isScored())
 *
 * A survey that is not, a satisfaction survey, shows no score, no result OK / KO and no success rate anywhere: they would
 * read as a failure, or as a success at 0 %. Kept by sheet and success rate for the page : a session or a list asks it
 * for many surveys of the same sheet.
 *
 * @param  Survey $survey Survey
 * @return bool           True when its score and its result are to be shown
 */
function digiquali_survey_is_scored(Survey $survey): bool
{
    static $scored = [];

    $key = ((int) $survey->fk_sheet) . '|' . ((float) $survey->success_rate);
    if (!isset($scored[$key])) {
        $scored[$key] = $survey->isScored();
    }

    return $scored[$key];
}

/**
 * Get the title of a survey: its label, else the label of its sheet
 *
 * @param  Survey     $survey Survey
 * @param  Sheet|null $sheet  Sheet of the survey, when loaded
 * @return string             Title, not escaped
 */
function digiquali_survey_get_title(Survey $survey, ?Sheet $sheet = null): string
{
    if (dol_strlen(trim((string) $survey->label)) > 0) {
        return trim((string) $survey->label);
    }

    return $sheet !== null && !empty($sheet->id) ? ($sheet->label ?: $sheet->ref) : (string) $survey->ref;
}

/**
 * Get the summary of a collect session: its respondents, then for each question the answers counted, the average of the
 * scores and the comments
 *
 * A respondent is a response sent: a survey begun then left as a draft is not one, its answers are counted nowhere (an
 * answer may still change until it is sent). The responses begun and not sent are counted apart, as "started".
 *
 * @param  Sheet    $sheet     Sheet of the session
 * @param  Survey[] $responses Responses of the session (see digiquali_survey_session_get_responses())
 * @return array{responses: int, respondents: array<int, Survey>, completed: int, started: int, questions: array<int, array>}
 */
function digiquali_survey_session_get_summary(Sheet $sheet, array $responses): array
{
    global $db;

    require_once __DIR__ . '/digiquali_answer.lib.php';

    $questions = $sheet->fetchAllQuestions();
    $questions = is_array($questions) ? $questions : [];

    // Answers and comments of every response, in one query
    $lines = [];
    if (!empty($responses)) {
        $resql = $db->query('SELECT t.fk_survey, t.fk_question, t.answer, t.comment FROM ' . $db->prefix() . 'digiquali_surveydet as t WHERE t.status > 0 AND t.fk_survey IN (' . implode(',', array_map('intval', array_keys($responses))) . ')');
        while ($resql && ($obj = $db->fetch_object($resql))) {
            $lines[(int) $obj->fk_survey][(int) $obj->fk_question] = $obj;
        }
    }

    $summary = ['responses' => count($responses), 'respondents' => [], 'completed' => 0, 'started' => 0, 'questions' => []];

    // Only the responses sent are counted : the others are told apart, when at least one of their questions is answered
    $sentResponses = [];
    foreach ($responses as $responseId => $response) {
        if ((int) $response->status >= Survey::STATUS_VALIDATED) {
            $sentResponses[$responseId]                 = $response;
            $summary['respondents'][(int) $response->id] = $response;
            continue;
        }
        foreach ($questions as $question) {
            if (!digiquali_answer_is_empty($question->type, $lines[(int) $response->id][(int) $question->id]->answer ?? null)) {
                $summary['started']++;
                break;
            }
        }
    }
    $summary['completed'] = count($summary['respondents']);

    foreach ($questions as $question) {
        $kind = 'other';
        if (digiquali_question_type_is_choice($question->type)) {
            $kind = 'choice';
        } elseif (in_array($question->type, ['Range', 'Percentage'], true)) {
            $kind = 'score';
        } elseif ($question->type == 'Duration') {
            $kind = 'duration';
        } elseif ($question->type == 'Text') {
            $kind = 'text';
        }

        $questionSummary = ['question' => $question, 'kind' => $kind, 'answered' => 0, 'options' => [], 'values' => [], 'average' => null, 'min' => null, 'max' => null, 'texts' => [], 'comments' => []];
        if ($kind == 'choice') {
            foreach (digiquali_get_question_answers_by_position($question) as $position => $option) {
                $questionSummary['options'][$position] = ['label' => $option->value, 'count' => 0];
            }
        }
        if ($kind == 'score') {
            $bounds = $question->type == 'Percentage' ? ['min' => 0, 'max' => 100] : digiquali_get_question_range_bounds($question);
            $questionSummary['min'] = $bounds['min'];
            $questionSummary['max'] = $bounds['max'];
        }

        foreach ($sentResponses as $response) {
            $line   = $lines[(int) $response->id][(int) $question->id] ?? null;
            $answer = $line->answer ?? null;
            $name   = (string) $response->respondent_name;

            if (dol_strlen(trim((string) ($line->comment ?? ''))) > 0) {
                $questionSummary['comments'][] = ['text' => trim((string) $line->comment), 'name' => $name];
            }
            if (digiquali_answer_is_empty($question->type, $answer)) {
                continue;
            }

            $questionSummary['answered']++;

            if ($kind == 'choice') {
                foreach (array_unique(array_map('intval', explode(',', (string) $answer))) as $position) {
                    if ($position <= 0) {
                        continue;
                    }
                    if (!isset($questionSummary['options'][$position])) {
                        $questionSummary['options'][$position] = ['label' => (string) $position, 'count' => 0];
                    }
                    $questionSummary['options'][$position]['count']++;
                }
            } elseif (in_array($kind, ['score', 'duration'], true) && is_numeric($answer)) {
                $questionSummary['values'][] = (float) $answer;
            } elseif ($kind == 'text') {
                $questionSummary['texts'][] = ['text' => trim((string) $answer), 'name' => $name];
            }
        }

        if (!empty($questionSummary['values'])) {
            $questionSummary['average'] = array_sum($questionSummary['values']) / count($questionSummary['values']);
        }
        foreach ($questionSummary['options'] as $position => $option) {
            $questionSummary['options'][$position]['percent'] = $questionSummary['answered'] > 0 ? (int) round($option['count'] / $questionSummary['answered'] * 100) : 0;
        }

        $summary['questions'][(int) $question->id] = $questionSummary;
    }

    return $summary;
}

/*
 * Invitees of a nominative collect session
 *
 * A nominative session can invite people by e-mail. Each invitee (name, e-mail) gets a personal link: an unpredictable
 * token of his own, which opens the session in his name, and the response he starts is tied to him, so that the
 * reminders go only to those who have not answered. The common link of the session stays usable : an invitee who goes
 * through it (QR code shown in the room) and types his e-mail gets his response tied to him as well, when he has none
 * yet, so that he is not reminded (response_common = 1). Typing an e-mail proves nothing, so such a response never
 * takes the place of the personal link : the personal link of the invitee starts a response of his own, which then takes
 * the tie over (see digiquali_survey_session_attach_common_response()).
 * The list is kept as JSON in the column invitees of the session, read and written by these functions only: it is no
 * field of the class, so that a clone, a list, an export or the API never carry the tokens, and so that an update of the
 * session never writes back a list read before a respondent started.
 */

/**
 * Get the largest number of invitees of a session
 *
 * @return int Number of invitees
 */
function digiquali_survey_session_invitees_max(): int
{
    return 200;
}

/**
 * Get the invitees of a session
 *
 * @param  Survey $session   Session
 * @param  bool   $forUpdate True to lock the row of the session until the end of the transaction
 * @return array<int, array{id: int, token: string, name: string, email: string, response_id: int, response_common: int, date_invited: int, date_reminded: int, reminders: int}> Invitees, in the order they were added
 */
function digiquali_survey_session_get_invitees(Survey $session, bool $forUpdate = false): array
{
    global $db;

    if (empty($session->id)) {
        return [];
    }

    $resql = $db->query('SELECT invitees FROM ' . $db->prefix() . 'digiquali_survey WHERE rowid = ' . ((int) $session->id) . ($forUpdate ? ' FOR UPDATE' : ''));
    $obj   = $resql ? $db->fetch_object($resql) : null;
    $data  = !empty($obj->invitees) ? json_decode((string) $obj->invitees, true) : [];

    $invitees = [];
    foreach (is_array($data) ? $data : [] as $invitee) {
        if (!is_array($invitee) || empty($invitee['token']) || empty($invitee['email'])) {
            continue;
        }
        $invitees[] = [
            'id'            => (int) ($invitee['id'] ?? 0),
            'token'         => (string) $invitee['token'],
            'name'          => (string) ($invitee['name'] ?? ''),
            'email'         => (string) $invitee['email'],
            'response_id'     => (int) ($invitee['response_id'] ?? 0),
            'response_common' => (int) ($invitee['response_common'] ?? 0),
            'date_invited'    => (int) ($invitee['date_invited'] ?? 0),
            'date_reminded'   => (int) ($invitee['date_reminded'] ?? 0),
            'reminders'       => (int) ($invitee['reminders'] ?? 0),
        ];
    }

    return $invitees;
}

/**
 * Change the invitees of a session, its row locked meanwhile: two invitees who start at the same time never write over
 * each other
 *
 * @param  Survey   $session Session
 * @param  callable $change  Function that gets the invitees by reference, changes them and returns what this one returns
 * @return mixed             What $change returned, false when the invitees could not be saved
 */
function digiquali_survey_session_change_invitees(Survey $session, callable $change)
{
    global $db;

    if (empty($session->id)) {
        return false;
    }

    $db->begin();
    $invitees = digiquali_survey_session_get_invitees($session, true);
    $result   = $change($invitees);

    // Without flags : the names are escaped (\u00e9) in the column, which json_decode() reads back the same
    $json = empty($invitees) ? '' : json_encode(array_values($invitees));
    if ($json === false) {
        $db->rollback();
        return false;
    }

    // tms = tms: the invitees are no change of the session itself, whose closing date is read in tms
    $sql = 'UPDATE ' . $db->prefix() . 'digiquali_survey SET invitees = ' . ($json === '' ? 'NULL' : "'" . $db->escape($json) . "'") . ', tms = tms WHERE rowid = ' . ((int) $session->id);
    if (!$db->query($sql)) {
        $db->rollback();
        return false;
    }
    $db->commit();

    return $result;
}

/**
 * Read people to invite from a text, one per line: "Name ; e-mail", but also "e-mail ; Name", "Name <e-mail>" or a row
 * pasted from a spreadsheet
 *
 * @param  string $text Text
 * @return array{people: array<int, array{name: string, email: string}>, errors: string[]} People read, and the lines without a valid e-mail
 */
function digiquali_survey_session_parse_invitees(string $text): array
{
    $result = ['people' => [], 'errors' => []];

    foreach (preg_split('/\R/u', $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $email = preg_match('/[^\s<>;,"\']+@[^\s<>;,"\']+/u', $line, $matches) ? $matches[0] : '';
        if ($email === '' || !isValidEmail($email)) {
            $result['errors'][] = dol_trunc($line, 80);
            continue;
        }

        // The name is what is left once the e-mail and the separators are taken out
        $name = preg_replace('/<?\s*' . preg_quote($email, '/') . '\s*>?/u', ' ', $line, 1);
        $name = trim(preg_replace('/\s+/u', ' ', str_replace([';', "\t", '<', '>', '"'], ' ', (string) $name)), " ,'");

        $result['people'][] = ['name' => dol_substr($name !== '' ? $name : $email, 0, 255), 'email' => $email];
    }

    return $result;
}

/**
 * Add invitees to a session, each with a personal token: an e-mail already invited is not invited twice
 *
 * @param  Survey $session Session
 * @param  array  $people  People to invite, see digiquali_survey_session_parse_invitees()
 * @return array{added: int, duplicates: string[], overflow: int}|false What was done, false on error
 */
function digiquali_survey_session_add_invitees(Survey $session, array $people)
{
    return digiquali_survey_session_change_invitees($session, function (array &$invitees) use ($people): array {
        $result = ['added' => 0, 'duplicates' => [], 'overflow' => 0];
        $emails = array_map(function (array $invitee): string {
            return dol_strtolower($invitee['email']);
        }, $invitees);
        $nextId = empty($invitees) ? 1 : max(array_column($invitees, 'id')) + 1;

        foreach ($people as $person) {
            $email = trim((string) ($person['email'] ?? ''));
            if (in_array(dol_strtolower($email), $emails, true)) {
                $result['duplicates'][] = $email;
                continue;
            }
            if (count($invitees) >= digiquali_survey_session_invitees_max()) {
                $result['overflow']++;
                continue;
            }

            $invitees[] = [
                'id'            => $nextId++,
                'token'         => bin2hex(random_bytes(16)),
                'name'          => trim((string) ($person['name'] ?? '')) ?: $email,
                'email'         => $email,
                'response_id'     => 0,
                'response_common' => 0,
                'date_invited'    => 0,
                'date_reminded'   => 0,
                'reminders'       => 0,
            ];
            $emails[] = dol_strtolower($email);
            $result['added']++;
        }

        return $result;
    });
}

/**
 * Remove an invitee from a session: his personal link no longer opens it, the response he may have given stays
 *
 * @param  Survey     $session   Session
 * @param  int        $inviteeId Id of the invitee in the session
 * @return array|null            Invitee removed, null when there is none with this id
 */
function digiquali_survey_session_remove_invitee(Survey $session, int $inviteeId): ?array
{
    $removed = digiquali_survey_session_change_invitees($session, function (array &$invitees) use ($inviteeId): ?array {
        foreach ($invitees as $index => $invitee) {
            if ($invitee['id'] === $inviteeId) {
                unset($invitees[$index]);
                return $invitee;
            }
        }
        return null;
    });

    return is_array($removed) ? $removed : null;
}

/**
 * Find the invitee a personal token belongs to, in a session
 *
 * @param  Survey     $session Session
 * @param  string     $token   Token of the personal link
 * @return array|null          Invitee, null when the token is none of this session
 */
function digiquali_survey_session_find_invitee(Survey $session, string $token): ?array
{
    if (!preg_match('/^[0-9a-f]{32}$/', $token)) {
        return null;
    }

    foreach (digiquali_survey_session_get_invitees($session) as $invitee) {
        if (hash_equals($invitee['token'], $token)) {
            return $invitee;
        }
    }

    return null;
}

/**
 * Get the personal link of an invitee: it opens the session in his name
 *
 * @param  Survey $session Session
 * @param  array  $invitee Invitee
 * @return string          Absolute URL
 */
function digiquali_survey_session_invitee_url(Survey $session, array $invitee): string
{
    global $conf;

    return dol_buildpath('/digiquali/public/survey/public_survey_session.php', 3) . '?id=' . ((int) $session->id) . '&invite=' . urlencode($invitee['token']) . '&entity=' . ((int) $conf->entity);
}

/**
 * Get the response of an invitee, created the first time he starts: his personal link always leads back to it
 *
 * @param  Survey      $session Session, open
 * @param  string      $token   Token of the personal link of the invitee
 * @return Survey|null          Response of the invitee, null on error
 */
function digiquali_survey_session_start_invitee_response(Survey $session, string $token): ?Survey
{
    global $db;

    // Under the lock of the session: the same invitee in two tabs gets one response
    $response = digiquali_survey_session_change_invitees($session, function (array &$invitees) use ($db, $session, $token): ?Survey {
        foreach ($invitees as &$invitee) {
            if (!hash_equals($invitee['token'], $token)) {
                continue;
            }

            // A response tied through the common link was started by whoever typed this e-mail : the personal link never
            // opens it, it starts the response of the invitee, which takes the tie over
            if (!empty($invitee['response_id']) && empty($invitee['response_common'])) {
                $response = new Survey($db);
                if ($response->fetch($invitee['response_id']) > 0 && $response->status >= Survey::STATUS_DRAFT) {
                    return $response;
                }
            }

            $response = digiquali_survey_session_create_response($session, $invitee['name'], $invitee['email']);
            if ($response !== null) {
                $invitee['response_id']     = (int) $response->id;
                $invitee['response_common'] = 0;
            }
            return $response;
        }
        return null;
    });

    return $response instanceof Survey ? $response : null;
}

/**
 * Tie a response started through the common link of a session to the invitee whose e-mail the respondent typed, so that
 * he is counted as a respondent and not reminded
 *
 * Only an invitee without a response is concerned : a response is never tied to two invitees (the e-mails of the
 * invitees are unique) and an invitee never gets a second one. Nothing is shown of it to the respondent, who could have
 * typed the e-mail of someone else : the page goes on the same way whether the e-mail is the one of an invitee or not,
 * and the personal link of the invitee still starts a response of his own (see
 * digiquali_survey_session_start_invitee_response()).
 *
 * @param  Survey $session  Session, nominative
 * @param  Survey $response Response just created through the common link
 * @param  string $email    E-mail typed by the respondent
 * @return bool             True when the response has been tied to an invitee
 */
function digiquali_survey_session_attach_common_response(Survey $session, Survey $response, string $email): bool
{
    global $db;

    $email = dol_strtolower(trim($email));
    if (empty($session->nominative) || empty($response->id) || $email === '' || !isValidEmail($email)) {
        return false;
    }

    // Under the lock of the session : two respondents who type the same e-mail at the same time do not both get it
    $attached = digiquali_survey_session_change_invitees($session, function (array &$invitees) use ($db, $response, $email): bool {
        foreach ($invitees as &$invitee) {
            if (dol_strtolower(trim($invitee['email'])) !== $email) {
                continue;
            }

            // The invitee already has a response of his own, or one tied before : it stays his only one
            if (!empty($invitee['response_id'])) {
                $tiedResponse = new Survey($db);
                if ($tiedResponse->fetch($invitee['response_id']) > 0 && $tiedResponse->status >= Survey::STATUS_DRAFT) {
                    return false;
                }
            }

            $invitee['response_id']     = (int) $response->id;
            $invitee['response_common'] = 1;
            return true;
        }
        return false;
    });

    return $attached === true;
}

/**
 * Tell where an invitee stands
 *
 * @param  array    $invitee   Invitee
 * @param  Survey[] $responses Responses of the session, by id (see digiquali_survey_session_get_responses())
 * @return string              'answered' (response sent), 'started' (response begun, not sent), 'invited' (link sent,
 *                             no response), 'new' (link not sent yet)
 */
function digiquali_survey_session_invitee_state(array $invitee, array $responses): string
{
    $response = !empty($invitee['response_id']) ? ($responses[$invitee['response_id']] ?? null) : null;
    if ($response !== null) {
        return $response->status >= Survey::STATUS_VALIDATED ? 'answered' : 'started';
    }

    return !empty($invitee['date_invited']) ? 'invited' : 'new';
}

/**
 * Get the time an invitee is left alone after a reminder: a second click on the button reminds nobody twice the same day
 *
 * @return int Seconds
 */
function digiquali_survey_session_reminder_delay(): int
{
    return 86400;
}

/**
 * Tell whether an invitee was reminded less than digiquali_survey_session_reminder_delay() ago
 *
 * @param  array    $invitee Invitee
 * @param  int|null $now     Current time, dol_now() when null
 * @return bool              True when he is not to be reminded yet
 */
function digiquali_survey_session_invitee_reminded_recently(array $invitee, ?int $now = null): bool
{
    $now = $now ?? dol_now();

    return !empty($invitee['date_reminded']) && (int) $invitee['date_reminded'] > $now - digiquali_survey_session_reminder_delay();
}

/**
 * Count the invitees of a session by where they stand
 *
 * @param  array    $invitees  Invitees
 * @param  Survey[] $responses Responses of the session, by id
 * @return array{total: int, new: int, invited: int, started: int, answered: int, to_remind: int, reminded_recently: int, remindable: int, next_reminder: int}
 *         to_remind counts those who have not sent their response, remindable those of them who may be reminded now (the
 *         others were reminded less than a day ago), next_reminder is when the first of these may be reminded again
 */
function digiquali_survey_session_count_invitees(array $invitees, array $responses): array
{
    $now    = dol_now();
    $counts = ['total' => count($invitees), 'new' => 0, 'invited' => 0, 'started' => 0, 'answered' => 0, 'reminded_recently' => 0, 'next_reminder' => 0];
    foreach ($invitees as $invitee) {
        $state = digiquali_survey_session_invitee_state($invitee, $responses);
        $counts[$state]++;
        if (in_array($state, ['invited', 'started'], true) && digiquali_survey_session_invitee_reminded_recently($invitee, $now)) {
            $counts['reminded_recently']++;
            $nextReminder           = (int) $invitee['date_reminded'] + digiquali_survey_session_reminder_delay();
            $counts['next_reminder'] = empty($counts['next_reminder']) ? $nextReminder : min($counts['next_reminder'], $nextReminder);
        }
    }
    $counts['to_remind']  = $counts['invited'] + $counts['started'];
    $counts['remindable'] = $counts['to_remind'] - $counts['reminded_recently'];

    return $counts;
}

/**
 * Format a name and an e-mail as an address of an e-mail header
 *
 * @param  string $name  Name, the characters that would break the header are taken out
 * @param  string $email E-mail
 * @return string        Address
 */
function digiquali_survey_mail_address(string $name, string $email): string
{
    $name = trim(preg_replace('/\s+/u', ' ', str_replace([',', ';', '<', '>', '"', "\r", "\n"], ' ', $name)));

    return $name !== '' ? $name . ' <' . $email . '>' : $email;
}

/**
 * Get the text of the e-mail that brings an invitee his personal link: the link is written in clear
 *
 * @param  Survey $session  Session
 * @param  string $title    Title of the session
 * @param  array  $invitee  Invitee
 * @param  User   $sender   User who sends it and signs it
 * @param  bool   $reminder True for the reminder, false for the invitation
 * @return string           Text of the e-mail
 */
function digiquali_survey_session_invitation_text(Survey $session, string $title, array $invitee, User $sender, bool $reminder): string
{
    global $langs, $mysoc;

    $paragraphs   = [];
    $paragraphs[] = $langs->transnoentitiesnoconv('SurveySessionMailGreeting', $invitee['name']);
    $paragraphs[] = $reminder ? $langs->transnoentitiesnoconv('SurveySessionMailReminderIntro', $title) : $langs->transnoentitiesnoconv('SurveySessionMailInvitationIntro', $mysoc->name, $title);
    $paragraphs[] = $langs->transnoentitiesnoconv('SurveySessionMailLinkIntro') . "\n" . digiquali_survey_session_invitee_url($session, $invitee);
    if (!empty($session->date_end)) {
        $paragraphs[] = $langs->transnoentitiesnoconv('SurveySessionMailDeadline', dol_print_date($session->date_end, 'dayhour', digiquali_survey_session_tz(), $langs));
    }
    $paragraphs[] = $langs->transnoentitiesnoconv('SurveySessionMailPersonal');
    $paragraphs[] = trim($langs->transnoentitiesnoconv('SurveySessionMailSignature') . "\n" . $sender->getFullName($langs) . "\n" . $mysoc->name);

    return implode("\n\n", $paragraphs);
}

/**
 * Send their personal link by e-mail to the invitees of a session: the invitation to those who have not had it yet, or
 * the reminder to those who had it and have not sent their response
 *
 * @param  Survey $session  Session, open and nominative
 * @param  Sheet  $sheet    Sheet of the session
 * @param  User   $sender   User who sends, signs the e-mails and gets the replies
 * A person reminded less than digiquali_survey_session_reminder_delay() ago is not reminded again: he is counted in
 * skipped, and next_reminder tells when the first of them may be reminded.
 *
 * @param  bool   $reminder True for the reminder, false for the invitation
 * @return array{sent: int, errors: string[], skipped: int, next_reminder: int} Number of e-mails sent, the errors (not
 *         escaped), the number of people left out as reminded recently, and when the first of them may be reminded again
 */
function digiquali_survey_session_send_invitations(Survey $session, Sheet $sheet, User $sender, bool $reminder): array
{
    global $conf, $langs, $mysoc;

    require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

    $result    = ['sent' => 0, 'errors' => [], 'skipped' => 0, 'next_reminder' => 0];
    $responses = digiquali_survey_session_get_responses($session);
    $title     = digiquali_survey_get_title($session, $sheet);
    $from      = digiquali_survey_mail_address((string) $mysoc->name, (string) $conf->email_from);
    $replyTo   = isValidEmail((string) $sender->email) ? digiquali_survey_mail_address($sender->getFullName($langs), (string) $sender->email) : '';
    $subject   = $langs->transnoentitiesnoconv($reminder ? 'SurveySessionMailReminderSubject' : 'SurveySessionMailInvitationSubject', $title);
    $now       = dol_now();

    $recipients = [];
    if ($reminder) {
        // The people to remind are taken under the lock of the session, their reminder dated at once : two clicks at the
        // same time, or one after the other, never remind the same person twice. A person reminded less than a day ago is
        // left alone, whoever clicks
        $claimed = digiquali_survey_session_change_invitees($session, function (array &$invitees) use ($responses, $now, &$result): array {
            $claimed = [];
            foreach ($invitees as &$invitee) {
                if (!in_array(digiquali_survey_session_invitee_state($invitee, $responses), ['invited', 'started'], true)) {
                    continue;
                }
                if (digiquali_survey_session_invitee_reminded_recently($invitee, $now)) {
                    $result['skipped']++;
                    $nextReminder            = (int) $invitee['date_reminded'] + digiquali_survey_session_reminder_delay();
                    $result['next_reminder'] = empty($result['next_reminder']) ? $nextReminder : min($result['next_reminder'], $nextReminder);
                    continue;
                }
                $claimed[]                = $invitee + ['previous_date_reminded' => $invitee['date_reminded']];
                $invitee['date_reminded'] = $now;
                $invitee['reminders']++;
            }
            return $claimed;
        });
        $recipients = is_array($claimed) ? $claimed : [];
    } else {
        foreach (digiquali_survey_session_get_invitees($session) as $invitee) {
            if (digiquali_survey_session_invitee_state($invitee, $responses) == 'new') {
                $recipients[] = $invitee;
            }
        }
    }

    $sentIds   = [];
    $failedIds = [];
    foreach ($recipients as $invitee) {
        $text = digiquali_survey_session_invitation_text($session, $title, $invitee, $sender, $reminder);
        $mail = new CMailFile($subject, digiquali_survey_mail_address($invitee['name'], $invitee['email']), $from, $text, [], [], [], '', '', 0, 0, '', '', 'survey' . $session->id, '', 'standard', $replyTo);
        if (empty($mail->error) && $mail->sendfile()) {
            $sentIds[] = $invitee['id'];
        } else {
            $failedIds[$invitee['id']] = (int) ($invitee['previous_date_reminded'] ?? 0);
            $result['errors'][]        = $langs->transnoentities('SurveySessionMailFailed', $invitee['email'], dol_string_nohtmltag($mail->error ?: implode(', ', (array) $mail->errors), 1));
        }
    }

    // The invitations are dated once the e-mails have left (the session is not locked while they are being sent), a
    // reminder that could not leave gets its former date back
    if ((!$reminder && !empty($sentIds)) || ($reminder && !empty($failedIds))) {
        $now = dol_now();
        digiquali_survey_session_change_invitees($session, function (array &$invitees) use ($sentIds, $failedIds, $reminder, $now): void {
            foreach ($invitees as &$invitee) {
                if ($reminder && array_key_exists($invitee['id'], $failedIds)) {
                    $invitee['date_reminded'] = $failedIds[$invitee['id']];
                    $invitee['reminders']     = max(0, $invitee['reminders'] - 1);
                } elseif (!$reminder && in_array($invitee['id'], $sentIds, true)) {
                    $invitee['date_invited'] = $now;
                }
            }
        });
    }
    $result['sent'] = count($sentIds);

    return $result;
}
