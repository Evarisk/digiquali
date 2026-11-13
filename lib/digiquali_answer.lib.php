<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_answer.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Control
 */


/**
 * Format the answer of a Duration question for display
 *
 * The answer is stored as a number of seconds, like a Dolibarr duration extrafield, so every
 * display (card, list, PDF, ODT) has to go through this to read as a time and not as a raw count.
 *
 * @param  string|int|null $answer Answer of the line, in seconds
 * @return string                  Duration as HH:MM:SS, empty string when there is no answer
 */
function digiquali_format_duration($answer): string
{
    // Not loaded by every host page, and this library is included from public pages too
    require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

    if ($answer === null || $answer === '' || !is_numeric($answer)) {
        return '';
    }

    $seconds = (int) $answer;

    // convertSecondToTime() answers '0' instead of a padded time for an empty duration
    if ($seconds <= 0) {
        return '00:00:00';
    }

    return convertSecondToTime($seconds, 'allhourminsec');
}

/**
 * Get the statuses a photo of a PhotoMultiple answer can be given, in the order of their buttons
 *
 * The keys are what the answer stores, the icons and colors those of the answers an OK/KO/To fix/N/A
 * question is created with (the colors are repeated in _photo-multiple.scss). The labels have keys of
 * their own : the OK and KO of Dolibarr translate as Success and Error.
 *
 * @return array Icon, color and translation key of each status, keyed by the stored status
 */
function digiquali_photo_multiple_get_statuses(): array
{
    return [
        'OK'    => ['icon' => 'fa-check', 'color' => '#47e58e', 'label' => 'PhotoMultipleStatusOK'],
        'KO'    => ['icon' => 'fa-times', 'color' => '#e05353', 'label' => 'PhotoMultipleStatusKO'],
        'TOFIX' => ['icon' => 'fa-tools', 'color' => '#e9ad4f', 'label' => 'PhotoMultipleStatusTOFIX'],
        'NA'    => ['icon' => 'fa-ban',   'color' => '#989898', 'label' => 'PhotoMultipleStatusNA'],
    ];
}

/**
 * Get the label of the status of a PhotoMultiple photo
 *
 * @param  string         $status      Status stored in the answer
 * @param  Translate|null $outputLangs Langs of the output, those of the user when null
 * @return string                      Translated label, empty string for a photo without status
 */
function digiquali_photo_multiple_get_status_label(string $status, ?Translate $outputLangs = null): string
{
    global $langs;

    $statuses = digiquali_photo_multiple_get_statuses();
    if (!isset($statuses[$status])) {
        return '';
    }

    return ($outputLangs ?? $langs)->transnoentities($statuses[$status]['label']);
}

/**
 * Read the answer of a PhotoMultiple question
 *
 * The answer is a JSON object keyed by the name of each answer photo, so a row follows its photo
 * whatever the order of the gallery: {"photo_1.jpg": {"comment": "...", "status": "KO"}, ...}.
 * Everything that does not fit that shape is dropped, the answer also comes back from the browser.
 *
 * @param  string|null $answer Answer of the line
 * @return array               Rows keyed by photo file name, each with a comment and a status (one of digiquali_photo_multiple_get_statuses() or empty)
 */
function digiquali_photo_multiple_decode(?string $answer): array
{
    $decodedAnswer = ($answer !== null && $answer !== '') ? json_decode($answer, true) : null;
    if (!is_array($decodedAnswer)) {
        return [];
    }

    $statuses = digiquali_photo_multiple_get_statuses();

    $rows = [];
    foreach ($decodedAnswer as $fileName => $row) {
        $fileName = (string) $fileName;
        // The key is only ever matched against the names of the photo directory : a path is never one of them
        if ($fileName === '' || strpbrk($fileName, '/\\') !== false || strpos($fileName, '..') !== false || !is_array($row)) {
            continue;
        }

        $status = dol_strtoupper((string) ($row['status'] ?? ''));

        $rows[$fileName] = [
            'comment' => dol_substr(dol_string_nohtmltag((string) ($row['comment'] ?? ''), 0, 'UTF-8', 0, 0), 0, 2000),
            'status'  => isset($statuses[$status]) ? $status : '',
        ];
    }

    return $rows;
}

/**
 * Write the answer of a PhotoMultiple question
 *
 * @param  array  $rows Rows keyed by photo file name, as returned by digiquali_photo_multiple_decode()
 * @return string       JSON answer, empty string when there is no photo so the question stays unanswered
 */
function digiquali_photo_multiple_encode(array $rows): string
{
    // The object cast keeps a JSON object even if a photo name would read as a number
    return empty($rows) ? '' : (json_encode((object) $rows) ?: '');
}

/**
 * Clean an answer of a PhotoMultiple question posted by the browser
 *
 * @param  string $answer Posted answer
 * @return string         Answer rebuilt from its valid rows only
 */
function digiquali_photo_multiple_sanitize(string $answer): string
{
    return digiquali_photo_multiple_encode(digiquali_photo_multiple_decode($answer));
}

/**
 * Get the directory, relative to the module output, holding the answer photos of a question
 *
 * @param  CommonObject $object   Answered object (control or survey)
 * @param  Question     $question Question
 * @return string                 Sub-directory, the same the media block of the question uploads into
 */
function digiquali_get_answer_photo_sub_dir(CommonObject $object, Question $question): string
{
    return $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref;
}

/**
 * List the rows of a PhotoMultiple answer : one per answer photo, oldest first, with its comment and status
 *
 * The photos are the reference, the answer only completes them : a photo with nothing written yet
 * still gets its row, and what the answer holds about a photo deleted since is ignored.
 *
 * @param  CommonObject $object   Answered object (control or survey)
 * @param  Question     $question Question
 * @param  string|null  $answer   Answer of the line
 * @return array                  Rows keyed by photo file name : name, path, sub_dir, comment and status
 */
function digiquali_photo_multiple_get_rows(CommonObject $object, Question $question, ?string $answer): array
{
    global $conf;

    // Not loaded by every host page, and this library is included from public pages too
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

    $subDir     = digiquali_get_answer_photo_sub_dir($object, $question);
    $photoDir   = $conf->digiquali->dir_output . '/' . $subDir;
    $answerRows = digiquali_photo_multiple_decode($answer);

    $rows = [];
    if (dol_is_dir($photoDir)) {
        $photoFiles = dol_dir_list($photoDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_ASC);
        foreach ($photoFiles as $photoFile) {
            if (image_format_supported($photoFile['name']) < 0) {
                continue;
            }

            $rows[$photoFile['name']] = [
                'name'    => $photoFile['name'],
                'path'    => $photoFile['fullname'],
                'sub_dir' => $subDir,
                'comment' => $answerRows[$photoFile['name']]['comment'] ?? '',
                'status'  => $answerRows[$photoFile['name']]['status'] ?? '',
            ];
        }
    }

    return $rows;
}

/**
 * Sum up the statuses of PhotoMultiple photos in one line : the number of photos, then of each status given
 *
 * @param  array  $photoStatuses Status of each photo, empty for a photo without status
 * @return string                Summary, e.g. "3 photo(s) - 2 OK - 1 To fix"
 */
function digiquali_photo_multiple_format_summary(array $photoStatuses): string
{
    global $langs;

    $statusCounts = array_count_values($photoStatuses);

    $summary = [$langs->transnoentities('PhotoMultipleSummary', (string) count($photoStatuses))];
    foreach (array_keys(digiquali_photo_multiple_get_statuses()) as $status) {
        // Four statuses at zero would bury the ones that were given
        if (!empty($statusCounts[$status])) {
            $summary[] = $statusCounts[$status] . ' ' . digiquali_photo_multiple_get_status_label($status);
        }
    }

    return implode(' - ', $summary);
}

/**
 * Sum up a PhotoMultiple answer in one line, for the lists and the documents without a photo table
 *
 * @param  string|null $answer Answer of the line
 * @return string              Number of photos and of each status given, empty string when there is no photo
 */
function digiquali_photo_multiple_summary(?string $answer): string
{
    $rows = digiquali_photo_multiple_decode($answer);
    if (empty($rows)) {
        return '';
    }

    return digiquali_photo_multiple_format_summary(array_column($rows, 'status'));
}

/**
 * Write a PhotoMultiple answer as text for an ODT document : the summary, then one line per photo
 *
 * The ODT models have a single answer field per question and print the photos in a segment of their
 * own : the answer can only carry the status and comment of each photo, in the order they were taken.
 *
 * @param  string|null $answer Answer of the line
 * @return string              Text answer, a blank when there is no photo so the ODT field is still replaced
 */
function digiquali_photo_multiple_odt_answer(?string $answer): string
{
    $summary = digiquali_photo_multiple_summary($answer);
    if ($summary === '') {
        return ' ';
    }

    $lines    = [$summary];
    $position = 1;
    foreach (digiquali_photo_multiple_decode($answer) as $row) {
        $lines[] = $position++ . '. ' . ($row['status'] !== '' ? digiquali_photo_multiple_get_status_label($row['status']) : '-') . ($row['comment'] !== '' ? ' : ' . $row['comment'] : '');
    }

    return implode("\n", $lines);
}

/**
 * Get the URLs of a PhotoMultiple photo and of its small thumbnail
 *
 * @param  array $row Row of digiquali_photo_multiple_get_rows()
 * @return array      URL of the photo and of its thumbnail, the photo itself when it has none
 */
function digiquali_photo_multiple_get_photo_urls(array $row): array
{
    global $conf;

    // Without a session document.php answers the login page : the public answer page goes through the
    // image wrapper of Saturne, like the reference photos of the questions
    $imageUrl  = (defined('NOLOGIN') ? dol_buildpath('/saturne/utils/viewimage.php', 1) : DOL_URL_ROOT . '/document.php') . '?modulepart=digiquali&entity=' . $conf->entity . '&file=';
    $thumbName = saturne_get_thumb_name($row['name'], 'small', dirname($row['path']));
    $photoUrl  = $imageUrl . urlencode($row['sub_dir'] . '/' . $row['name']);

    return ['photo' => $photoUrl, 'thumb' => is_string($thumbName) ? $imageUrl . urlencode($row['sub_dir'] . '/thumbs/' . $thumbName) : $photoUrl];
}

/**
 * Render a PhotoMultiple answer to be read, where it is shown as a field like on the answer card :
 * its summary, then the thumbnail, comment and status of each photo
 *
 * @param  Question     $question Question
 * @param  CommonObject $object   Answered object (control or survey)
 * @param  string|null  $answer   Answer of the line
 * @return string                 HTML output
 */
function digiquali_photo_multiple_render_output(Question $question, CommonObject $object, ?string $answer): string
{
    global $langs;

    $rows = digiquali_photo_multiple_get_rows($object, $question, $answer);
    if (empty($rows)) {
        return '<span class="opacitymedium">' . $langs->trans('PhotoMultipleEmpty') . '</span>';
    }

    $statuses = digiquali_photo_multiple_get_statuses();

    $out  = '<div class="question-photo-multiple-output">';
    $out .= '<div class="question-photo-multiple-output__summary">' . dol_escape_htmltag(digiquali_photo_multiple_format_summary(array_column($rows, 'status'))) . '</div>';
    $out .= '<table class="noborder centpercent">';
    $out .= '<tr class="liste_titre">';
    $out .= '<th>' . $langs->trans('PhotoMultipleImage') . '</th>';
    $out .= '<th>' . $langs->trans('PhotoMultipleComment') . '</th>';
    $out .= '<th class="center">' . $langs->trans('PhotoMultipleStatus') . '</th>';
    $out .= '</tr>';

    foreach ($rows as $row) {
        $photoUrls = digiquali_photo_multiple_get_photo_urls($row);

        $out .= '<tr class="oddeven">';
        $out .= '<td class="question-photo-multiple-output__photo">';
        $out .= '<a href="' . dol_escape_htmltag($photoUrls['photo']) . '" target="_blank" rel="noopener">';
        $out .= '<img src="' . dol_escape_htmltag($photoUrls['thumb']) . '" alt="' . dol_escape_htmltag($row['name']) . '" loading="lazy">';
        $out .= '</a>';
        $out .= '</td>';
        $out .= '<td>' . ($row['comment'] !== '' ? dol_nl2br(dol_escape_htmltag($row['comment'], 0, 1)) : '<span class="opacitymedium">-</span>') . '</td>';
        $out .= '<td class="center">';
        if (isset($statuses[$row['status']])) {
            $out .= '<span class="question-photo-multiple-output__status question-photo-multiple-output__status--' . dol_strtolower($row['status']) . '">';
            $out .= '<i class="fas ' . $statuses[$row['status']]['icon'] . '"></i> ' . $langs->trans($statuses[$row['status']]['label']);
            $out .= '</span>';
        } else {
            $out .= '<span class="opacitymedium">-</span>';
        }
        $out .= '</td>';
        $out .= '</tr>';
    }

    $out .= '</table>';
    $out .= '</div>';

    return $out;
}

/**
 * Render the answer widget of a PhotoMultiple question : a table with one row per answer photo
 *
 * The photos themselves are added and deleted by the media block of the question. The answer is
 * carried by the hidden input, the only field named answer<id>, which the save action and the
 * auto-save both read : the comment and status fields of each row are only there to fill it.
 *
 * @param  Question     $question       Question
 * @param  CommonObject $object         Answered object (control or survey)
 * @param  string       $questionAnswer Answer of the line
 * @param  bool         $readOnly       True once the object is no longer a draft : the rows can only be read
 * @return string                       HTML output
 */
function digiquali_render_photo_multiple_answer(Question $question, CommonObject $object, string $questionAnswer, bool $readOnly = false): string
{
    global $langs;

    $disabled = ($readOnly ? ' disabled' : '');
    $rows     = digiquali_photo_multiple_get_rows($object, $question, $questionAnswer);

    // The rows only keep the photos still in the directory : the stored answer is cleaned the same way
    $answerRows = [];
    foreach ($rows as $fileName => $row) {
        $answerRows[$fileName] = ['comment' => $row['comment'], 'status' => $row['status']];
    }

    $out  = '<div class="question-photo-multiple" id="question-photo-multiple-' . $question->id . '" data-question-id="' . $question->id . '" data-sub-dir="' . dol_escape_htmltag(digiquali_get_answer_photo_sub_dir($object, $question)) . '" data-saved-answer="' . dol_escape_htmltag($questionAnswer) . '">';
    $out .= '<input type="hidden" class="question-answer" name="answer' . $question->id . '" value="' . dol_escape_htmltag(digiquali_photo_multiple_encode($answerRows)) . '">';

    if (empty($rows)) {
        $out .= '<p class="question-photo-multiple__empty opacitymedium">' . $langs->trans('PhotoMultipleEmpty') . '</p>';
    } else {
        $out .= '<div class="question-photo-multiple__head">';
        $out .= '<span>' . $langs->trans('PhotoMultipleImage') . '</span>';
        $out .= '<span>' . $langs->trans('PhotoMultipleComment') . '</span>';
        $out .= '<span>' . $langs->trans('PhotoMultipleStatus') . '</span>';
        $out .= '</div>';

        foreach ($rows as $row) {
            $photoUrls = digiquali_photo_multiple_get_photo_urls($row);

            $out .= '<div class="question-photo-multiple__row" data-file-name="' . dol_escape_htmltag($row['name']) . '">';
            $out .= '<a class="question-photo-multiple__photo" href="' . dol_escape_htmltag($photoUrls['photo']) . '" target="_blank" rel="noopener">';
            $out .= '<img src="' . dol_escape_htmltag($photoUrls['thumb']) . '" alt="' . dol_escape_htmltag($row['name']) . '" loading="lazy">';
            $out .= '</a>';
            $out .= '<textarea class="question-textarea question-photo-multiple__comment" rows="2" placeholder="' . $langs->transnoentities('WriteComment') . '"' . $disabled . '>' . dol_escape_htmltag($row['comment']) . '</textarea>';
            $out .= '<div class="question-photo-multiple__status">';
            foreach (digiquali_photo_multiple_get_statuses() as $status => $statusInfo) {
                $out .= '<button type="button" class="question-photo-multiple__status-button question-photo-multiple__status-button--' . dol_strtolower($status) . ($row['status'] == $status ? ' active' : '') . '" data-status="' . $status . '" aria-pressed="' . ($row['status'] == $status ? 'true' : 'false') . '"' . $disabled . '>';
                $out .= '<i class="fas ' . $statusInfo['icon'] . '"></i> ' . $langs->trans($statusInfo['label']);
                $out .= '</button>';
            }
            $out .= '</div>';
            $out .= '</div>';
        }
    }

    $out .= '</div>';

    return $out;
}

/**
 * Load the comment library, the dictionary of comments that can be dropped into a question comment
 *
 * The result is kept in a static : the question template is included once per question, so
 * without it a sheet of eighty questions would run eighty times the same query.
 *
 * @return array Active entries of the dictionary, ordered by position
 */
function digiquali_get_comment_library(): array
{
    static $commentLibrary = null;

    if ($commentLibrary === null) {
        $commentLibrary = [];

        $dictionaryEntries = saturne_fetch_dictionary('c_question_comment');
        if (is_array($dictionaryEntries)) {
            foreach ($dictionaryEntries as $dictionaryEntry) {
                // saturne_fetch_dictionary() filters the entity but keeps the disabled entries
                if (!empty($dictionaryEntry->active)) {
                    $commentLibrary[$dictionaryEntry->id] = $dictionaryEntry;
                }
            }
        }
    }

    return $commentLibrary;
}

/**
 * Get the text a comment library entry drops into the comment
 *
 * The label is the short text shown on the button, the description the full sentence to write down.
 * A library filled with short comments only has no description, so the label is the fallback.
 *
 * @param  stdClass $commentLibraryEntry Entry of the comment library
 * @return string                        Text to add to the question comment
 */
function digiquali_get_comment_library_text(stdClass $commentLibraryEntry): string
{
    return dol_strlen($commentLibraryEntry->description) > 0 ? $commentLibraryEntry->description : $commentLibraryEntry->label;
}

/**
 * Create pictos dropdown string
 *
 * @param  CommonObject $object Object
 * @return string               dropdown html output
 * @throws Exception
 */
function answer_pictos_dropdown($selected = ''): string
{
	$pictosArray = get_answer_pictos_array();

	$out = '<div class="wpeo-dropdown dropdown-large dropdown-grid answer-picto padding">';
	$out .= '<input class="input-hidden-picto" type="hidden" name="answerPicto" value="'. (dol_strlen($selected) > 0 ? $selected : '') .'" />
			<div class="dropdown-toggle dropdown-add-button button-picto">';

	if (dol_strlen($selected) > 0) {
		$out .= '<span class="wpeo-button button-square-50 button-grey">';
		$out .= $pictosArray[$selected]['picto_source'];
		$out .= '</span>';
	} else {
		$out .= '<span class="wpeo-button button-square-50 button-grey"><i class="fas fa-arrow-right button-icon"></i><i class="fas fa-plus-circle button-add"></i></span>';
	}
	$out .= '</div>';
	$out .= '<ul class="saturne-dropdown-content wpeo-gridlayout grid-5 grid-gap-0">';
	if ( ! empty($pictosArray) ) {
		foreach ($pictosArray as $pictoName => $picto) {
			$out .= '<li class="item dropdown-item wpeo-tooltip-event" data-is-preset="" data-id="'. $picto['position'] .'" aria-label="'. $picto['name'] .'" data-label="'. $pictoName.'">';
			$out .= '<div class="wpeo-button button-grey">';
			$out .= $picto['picto_source'];
			$out .= '</div>';
			$out .= '</li>';
		}
	}
	$out .= '</ul>';
	$out .= '</div>';
	return $out;
}

/**
 * Return answer pictos array
 *
 * @param  CommonObject $object Object
 * @return array                Array of pictos
 * @throws Exception
 */
function get_answer_pictos_array(): array
{
	global $langs;

	$pictosArray = [
		'' => [
			'name' => $langs->transnoentities('None'),
			'picto_source' => $langs->transnoentities('None'),
			'position' => 0,
		],
		'check' => [
			'name' => $langs->transnoentities('Ok'),
			'picto_source' => '<i class="fas fa-check"></i>',
			'position' => 1
		],
		'times' => [
			'name' => $langs->transnoentities('Ko'),
			'picto_source' => '<i class="fas fa-times"></i>',
			'position' => 2
		],
		'tools' => [
			'name' => $langs->transnoentities('ToFix'),
			'picto_source' => '<i class="fas fa-tools"></i>',
			'position' => 3
		],
		'N/A' => [
			'name' => $langs->transnoentities('NonApplicable'),
			'picto_source' => 'N/A',
			'position' => 4
		],
		'eye' => [
			'name' => $langs->transnoentities('Observation'),
			'picto_source' => '<i class="fas fa-eye"></i>',
			'position' => 5
		],
		'level-up-alt' => [
			'name' => $langs->transnoentities('ProgressTrack'),
			'picto_source' => '<i class="fas fa-level-up-alt"></i>',
			'position' => 6
		],
		'star' => [
			'name' => $langs->transnoentities('StrongPoint'),
			'picto_source' => '<i class="fas fa-star"></i>',
			'position' => 7
		],
	];
	return $pictosArray;
}

/**
 * Show answer from question object in HTML output format (textarea, range, select, ...)
 *
 * @param  Question     $question       Question object
 * @param  CommonObject $object         Object (Control, Survey, ...)
 * @param  number       $questionGroupId Question group ID
 * @param  string       $questionAnswer Answer of the question (ControlLine, SurveyLine, ...)
 * @param  bool         $showAnwserCorrection Will show the right and wrong answers if true (in combinaison with object status)
 * @return string       $out            HTML output
 * @throws Exception
 */
function show_answer_from_question(Question $question, CommonObject $object, string $questionAnswer, int $questionGroupId = 0, bool $showAnwserCorrection = false): string
{
    global $db, $langs;

    $answer = new Answer($db);

    $out            = '';
    $disabled       = ($object->status > $object::STATUS_DRAFT ? ' disabled' : '');
    $questionConfig = json_decode($question->json, true)['config'] ?? [];
	$showCorrection = ($object->status >= $object::STATUS_LOCKED && $showAnwserCorrection);

    switch ($question->type) {
        case 'Text':
            $out .= '<div>';
            // Opens on a few lines, then grows with its text (see window.digiquali.object.fitTextAnswer)
            $out .= '<textarea class="question-textarea question-answer" name="answer' . $question->id . '" rows="4" placeholder="' . $langs->transnoentities('WriteAnswer') . '"' . $disabled . '>' . $questionAnswer . '</textarea>';
            $out .= '</div>';
            break;
        case 'Percentage':
            $step = 100;
            if (!empty($questionConfig[$question->type]['step'])) {
                // A slider needs at least two positions (0 and 100), and step - 1 is the divisor below
                $step = max(2, (int) $questionConfig[$question->type]['step']);
            }

			if ($showCorrection && $question->type == $question::TYPE_RANGE) {
				$isAnswerCorrect = $question->isAnswerInQuestionRange($questionAnswer);
				$answerCssClass = ($isAnswerCorrect && $questionAnswer !== '' ? ' correct' : ' incorrect');
			}

            $out .= '<div class="percentage-cell' . ($answerCssClass ?? '') . '">';
            $out .= img_picto('', 'fontawesome_fa-frown_fas_#D53C3D_3em', 'class="range-image"');
            $defaultValue = $questionConfig[$question->type]['answer-default-value'] ?? 100;
            $out .= '<input type="range" class="search_component_input range question-answer" name="answer' . $question->id . '" min="0" max="100" step="' . 100/($step - 1) . '" value="' . ($questionAnswer != '' ? $questionAnswer : $defaultValue) . '"' . $disabled . '>';
            $out .= img_picto('', 'fontawesome_fa-grin_fas_#57AD39_3em', 'class="range-image"');
            $out .= '</div>';
            break;
        case 'Range':
			if ($showCorrection) {
				$isAnswerCorrect = $question->isAnswerInQuestionRange($questionAnswer);
				$answerCssClass = ($isAnswerCorrect && $questionAnswer !== '' ? ' correct' : ' incorrect');
			}

            $out .= '<div class="question-number' . ($answerCssClass ?? '') . '">';
            $out .= '<input type="number" step="any" class="question-answer" name="answer' . $question->id . '" placeholder="0" value="' . $questionAnswer . '"' . $disabled . '>';
            $out .= '</div>';
            break;
        case 'Duration':
            // Stored in seconds like a Dolibarr duration extrafield, but split into three fields so
            // the answer is entered as a time. The total is carried by the hidden input, which is the
            // only field named answer<id> : the save action and the auto-save both read that one.
            $hasAnswer    = ($questionAnswer !== '' && $questionAnswer !== null && is_numeric($questionAnswer));
            $totalSeconds = $hasAnswer ? max(0, (int) $questionAnswer) : 0;

            $units = [
                'hour'   => ['value' => (int) floor($totalSeconds / 3600),      'label' => $langs->transnoentities('HourShort'),   'max' => ''],
                'minute' => ['value' => (int) floor(($totalSeconds % 3600) / 60), 'label' => $langs->transnoentities('MinuteShort'), 'max' => ' max="59"'],
                'second' => ['value' => (int) ($totalSeconds % 60),             'label' => $langs->transnoentities('SecondShort'), 'max' => ' max="59"'],
            ];

            $out .= '<div class="question-duration" data-question-id="' . $question->id . '">';
            $out .= '<input type="hidden" class="question-answer" name="answer' . $question->id . '" value="' . ($hasAnswer ? $totalSeconds : '') . '">';
            foreach ($units as $unit => $unitConfig) {
                $out .= '<span class="question-duration__unit">';
                $out .= '<input type="number" min="0"' . $unitConfig['max'] . ' step="1" class="question-duration__input" data-duration-unit="' . $unit . '" placeholder="0" value="' . ($hasAnswer ? $unitConfig['value'] : '') . '"' . $disabled . '>';
                $out .= '<span class="question-duration__label">' . $unitConfig['label'] . '</span>';
                $out .= '</span>';
            }
            $out .= '</div>';
            break;
        case 'UniqueChoice':
        case 'OkKo':
        case 'OkKoToFixNonApplicable':
        case 'MarqueNF':
        case 'Iso9001':
        case 'MultipleChoices':
            $answers = $answer->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = ' . Answer::STATUS_VALIDATED . ' AND t.fk_question = ' . $question->id]);
            $pictos  = get_answer_pictos_array();

            if (strpos($questionAnswer, ',') !== false) {
                $questionAnswers = explode(',', $questionAnswer);
            } else {
                $questionAnswers = [$questionAnswer];
            }

            $out .= '<div class="table-cell select-answer answer-cell" data-questionId="' . $question->id . '">';
            $out .= '<input type="hidden" class="question-answer" name="answer' . $question->id . '" value="0">';
            if (is_array($answers) && !empty($answers)) {
                foreach($answers as $answer) {

					if ($showCorrection) {
						$answerCssClass = ($answer->correct == 1 ? ' correct' : ' incorrect');
					}

                    $out .= '<input type="hidden" class="answer-color answer-color-' . $answer->position . '" value="' . $answer->color . '">';
                    $out .= '<span class="answer' . ($answerCssClass ?? '') . (!empty($answer->pictogram) ? ' answer-icon wpeo-tooltip-event' : '') . ($question->type == 'MultipleChoices' ? ' multiple-answers square' : ' single-answer') . (in_array($answer->position, $questionAnswers) ? ' active' : '') . ($object->status > 0 ? ' disable' : '') . '" style="' . (in_array($answer->position, $questionAnswers) ? 'background:' . $answer->color . '; ' : '') . 'color:' . $answer->color . '; box-shadow: 0 0 0 3px ' . $answer->color . ';" value="' . $answer->position . '"' . (!empty($answer->pictogram) ? ' aria-label="' . dol_escape_htmltag($answer->value) . '" data-direction="top"' : '') . '>';
                    $out .= !empty($answer->pictogram) ? $pictos[$answer->pictogram]['picto_source'] : $answer->value;
                    $out .= '</span>';
                }
            }
            $out .= '</div>';
            break;
        case 'PhotoMultiple':
            $out .= digiquali_render_photo_multiple_answer($question, $object, $questionAnswer, $disabled !== '');
            break;
    }

    return $out;
}

/**
 * Check that a media block action targets one of the answered object's own directories
 *
 * The public answer page has no authenticated user: the track ID is its only credential, so the
 * directory posted by js/modules/mediaBlock.js must be confined to the answered object. Without
 * this, anyone holding a track ID could write into or delete from any directory of the module.
 *
 * @param  CommonObject $object Answered object (control or survey)
 * @param  string       $action Action posted by the media block
 * @return bool                 True when the posted target belongs to $object
 */
function digiquali_answer_media_dir_is_allowed(CommonObject $object, string $action): bool
{
    global $db;

    $subDir = GETPOST('sub_dir', 'alpha');
    if (dol_strtolower(GETPOST('module_name', 'alpha')) != 'digiquali' || strpos($subDir, '..') !== false) {
        return false;
    }

    switch ($action) {
        case 'uploadPhoto':
        case 'deletePhoto':
            // Answer photos are stored in <element>/<object ref>/answer_photo/<question ref>
            $photoDir    = $object->element . '/' . dol_sanitizeFileName($object->ref) . '/answer_photo/';
            $questionRef = strpos($subDir, $photoDir) === 0 ? substr($subDir, dol_strlen($photoDir)) : '';
            return dol_strlen($questionRef) > 0 && strpos($questionRef, '/') === false;

        case 'uploadFile':
            // The upload resolves its directory from the answer line it creates, not from sub_dir
            return $object->element == 'control' && GETPOSTINT('fk_control') == $object->id && GETPOSTINT('fk_question') > 0;

        case 'deleteFile':
            // Attached documents are stored in controldet/<answer line ref>
            $lineRef = strpos($subDir, 'controldet/') === 0 ? substr($subDir, dol_strlen('controldet/')) : '';
            if ($object->element != 'control' || dol_strlen($lineRef) == 0 || strpos($lineRef, '/') !== false) {
                return false;
            }
            $objectLine = new ControlLine($db);
            return $objectLine->fetch(0, $lineRef) > 0 && $objectLine->fk_control == $object->id;
    }

    return false;
}
