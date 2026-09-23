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
            $out .= '<textarea class="question-textarea question-answer" name="answer' . $question->id . '" placeholder="' . $langs->transnoentities('WriteAnswer') . '"' . $disabled . '>' . $questionAnswer . '</textarea>';
            $out .= '</div>';
            break;
        case 'Percentage':
            $step = 100;
            if (!empty($questionConfig[$question->type]['step'])) {
                $step = $questionConfig[$question->type]['step'];
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
