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
 * \file    public/survey/public_survey_session.php
 * \ingroup digiquali
 * \brief   Public page of a collect session: the one link shown as a QR code, which gives each respondent a survey of
 *          his own, then sends him to its public answer page.
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOLOGIN')) {     // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) { // The respondents come from a QR code : the start button posts a token of this page instead
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {   // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Load DigiQuali environment
if (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} elseif (file_exists('../../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries: a response is cloned from the session with its tags
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load Saturne libraries
dol_include_once('/saturne/class/saturnesignature.class.php');

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/survey.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../lib/digiquali_survey.lib.php';

global $conf, $db, $langs, $mysoc;

saturne_load_langs();
$langs->load('errors');

// Get parameters
$id     = GETPOSTINT('id');
$key    = GETPOST('key', 'alphanohtml');
$invite = GETPOST('invite', 'aZ09');
$action = GETPOST('action', 'aZ09');
$entity = GETPOSTINT('entity');

// Load entity
if (!isModEnabled('multicompany') || $entity <= 0) {
    $entity = $conf->entity;
}
$conf->setEntityValues($db, $entity);

// The page runs without any user: the responses are created in the name of the owner of the session
$user = new User($db);
$user->loadDefaultValues();

// Initialize technical objects
$session = new Survey($db);
$sheet   = new Sheet($db);

// Only the key of the session opens it, or the personal token of one of its invitees: the track id of the session itself
// stays secret. A token is looked for in this session only: the token of another session opens nothing here
$invitee = null;
$allowed = getDolGlobalInt('SATURNE_ENABLE_PUBLIC_INTERFACE') && $id > 0 && $session->fetch($id) > 0 && digiquali_survey_is_session($session);
if ($allowed && $invite !== '') {
    $invitee = !empty($session->nominative) ? digiquali_survey_session_find_invitee($session, $invite) : null;
    $allowed = $invitee !== null;
} elseif ($allowed) {
    $allowed = hash_equals(digiquali_survey_session_key($session), (string) $key);
}
if (!$allowed) {
    httponly_accessforbidden($langs->trans('ErrorRecordNotFound'), 404);
}
$sheet->fetch($session->fk_sheet);

// The session closes by itself once its planned closing date is past
digiquali_survey_session_close_if_due($session);

$isOpen       = $session->status == Survey::STATUS_DRAFT;
$isNominative = !empty($session->nominative);
$sessionSlot  = 'digiquali_survey_session_' . $session->id;

// The page posts to the link it was opened with: the common one, or the personal one of the invitee
$sessionPageUrl = $_SERVER['PHP_SELF'] . '?id=' . ((int) $session->id) . ($invitee !== null ? '&invite=' . urlencode($invitee['token']) : '&key=' . urlencode((string) $key)) . '&entity=' . ((int) $conf->entity);

// The survey this browser already started, while it is not finished: a reload or a second touch resumes it. An invitee
// gets back the response of his personal link, wherever he opens it, or learns that it has been sent
$response              = null;
$inviteeAnswered       = false;
$inviteeCommonAnswered = false;
if ($invitee !== null) {
    // A response tied to him through the common link is not his for sure : his personal link starts his own. He is
    // only told that one was sent with his e-mail, which he may have done himself
    if (!empty($invitee['response_id']) && !empty($invitee['response_common'])) {
        $inviteeResponse       = new Survey($db);
        $inviteeCommonAnswered = $inviteeResponse->fetch($invitee['response_id']) > 0 && $inviteeResponse->status >= Survey::STATUS_VALIDATED;
    } elseif (!empty($invitee['response_id'])) {
        $inviteeResponse = new Survey($db);
        if ($inviteeResponse->fetch($invitee['response_id']) > 0 && $inviteeResponse->status >= Survey::STATUS_DRAFT) {
            if ($inviteeResponse->status == Survey::STATUS_DRAFT) {
                $response = $inviteeResponse;
            } else {
                $inviteeAnswered = true;
            }
        }
    }
} elseif (!empty($_SESSION[$sessionSlot])) {
    $response = new Survey($db);
    if ($response->fetch((int) $_SESSION[$sessionSlot]) <= 0 || $response->status != Survey::STATUS_DRAFT) {
        $response = null;
    }
}

/*
 * Actions
 */

$error = 0;
// In a nominative session the respondent gives his name, and his e-mail if he wishes; an anonymous one asks nothing
$respondentName  = trim(GETPOST('respondent_name', 'alphanohtml'));
$respondentEmail = trim(GETPOST('respondent_email', 'alphanohtml'));
$fieldErrors     = [];
$startRefused    = '';

// A response is created by the button of the page only (a POST carrying the token of the page this visitor opened) : a
// link preview, a crawler, a QR code reader that follows the URL or a bare request never creates one. A page kept open
// past the life of the PHP session gets a new token : one more touch of the button starts
if ($action == 'start' && $isOpen && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !digiquali_survey_session_check_start_token($session, GETPOST('start_token', 'aZ09'))) {
    $startRefused = 'SurveySessionStartExpired';
    $action       = '';
}

if ($action == 'start' && $isOpen && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $invitee !== null) {
    // An invitee has nothing to type: his response is created in his name, once
    if (!$inviteeAnswered) {
        $response = digiquali_survey_session_start_invitee_response($session, $invitee['token']);
        if (!empty($response)) {
            $_SESSION[$sessionSlot] = $response->id;
            header('Location: ' . digiquali_survey_public_answer_url($response));
            exit;
        }
        $error++;
    }
} elseif ($action == 'start' && $isOpen && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (empty($response) && $isNominative) {
        if ($respondentName === '') {
            $fieldErrors['respondent_name'] = $langs->transnoentities('SurveySessionRespondentNameRequired');
        }
        if ($respondentEmail !== '' && !isValidEmail($respondentEmail)) {
            $fieldErrors['respondent_email'] = $langs->transnoentities('ErrorBadEMail', $respondentEmail);
        }
    }
    // The common link is open to anyone : one connection starts a limited number of responses in a while, a burst is
    // stopped (see digiquali_survey_session_start_allowed())
    if (empty($response) && empty($fieldErrors) && !digiquali_survey_session_start_allowed($session)) {
        $startRefused = 'SurveySessionStartTooMany';
    }
    if (empty($response) && empty($fieldErrors) && $startRefused === '') {
        $response = digiquali_survey_session_create_response($session, $respondentName, $respondentEmail);
        // An invitee who came through the common link and typed his e-mail is tied to this response, so that he is no
        // longer reminded. The page says nothing of it : it goes on the same way for any e-mail
        if (!empty($response) && $isNominative && $respondentEmail !== '') {
            digiquali_survey_session_attach_common_response($session, $response, $respondentEmail);
        }
    }
    if (!empty($response)) {
        $_SESSION[$sessionSlot] = $response->id;
        header('Location: ' . digiquali_survey_public_answer_url($response));
        exit;
    }
    if (empty($fieldErrors) && $startRefused === '') {
        $error++;
    }
}

// Token of the page for the start button of this visitor
$startToken = digiquali_survey_session_start_token($session);

/*
 * View
 */

$title = digiquali_survey_get_title($session, $sheet);

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0, '', $title, '', '', 0, 0, [], [], '', 'template-pwa page-public-control page-public-control--no-nav page-public-survey-session');

$pwaHeaderTitle = $langs->transnoentities('Survey');
$pwaHeaderIcon  = 'fa-list-ol';
require_once __DIR__ . '/../../core/tpl/frontend/digiquali_public_pwa_header.tpl.php';

require __DIR__ . '/../../core/tpl/frontend/public_survey_session.tpl.php';

llxFooter('', 'public');
$db->close();
