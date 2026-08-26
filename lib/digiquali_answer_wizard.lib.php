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
 * \file    lib/digiquali_answer_wizard.lib.php
 * \ingroup digiquali
 * \brief   Library functions for the mobile answer wizard: split a sheet into steps and count progress.
 *
 * The wizard is the shared engine behind both answering surfaces (public QR page and
 * connected PWA screen). It only decides *how the questions are split and counted*;
 * the answer widgets themselves stay in show_answer_from_question().
 */

/**
 * Default number of ungrouped questions gathered in a single step.
 *
 * Overridable with DIGIQUALI_ANSWER_WIZARD_MAX_QUESTIONS_PER_STEP.
 */
const DIGIQUALI_ANSWER_WIZARD_DEFAULT_STEP_SIZE = 8;

/**
 * Split the sheet content into wizard steps.
 *
 * Walks the ordered list returned by Sheet::fetchQuestionsAndGroups() and applies two rules:
 *   - a first-level question group becomes one step (its sub-groups stay nested inside it,
 *     rendered by the existing recursive template);
 *   - consecutive ungrouped questions are gathered into steps of at most
 *     DIGIQUALI_ANSWER_WIZARD_MAX_QUESTIONS_PER_STEP questions.
 *
 * A sheet with no group and few questions therefore yields a single step, which renders
 * exactly like the current flat list.
 *
 * @param  CommonObject $object            Control or Survey being answered (lines are loaded if needed).
 * @param  array        $questionsAndGroups Ordered Question/QuestionGroup list of the sheet.
 * @return array<int,array{type:string,key:string,label:string,picto:string,description:string,items:array,total:int,answered:int}>
 */
function digiquali_answer_wizard_build_steps(CommonObject $object, array $questionsAndGroups): array
{
    global $langs;

    // calculatePoints() and the answered counters both read $object->lines
    if (empty($object->lines) && $object->id > 0) {
        $object->fetchLines();
    }

    $stepSize = getDolGlobalInt('DIGIQUALI_ANSWER_WIZARD_MAX_QUESTIONS_PER_STEP');
    if ($stepSize <= 0) {
        $stepSize = DIGIQUALI_ANSWER_WIZARD_DEFAULT_STEP_SIZE;
    }

    $steps        = [];
    $pendingItems = [];

    foreach ($questionsAndGroups as $questionOrGroup) {
        if ($questionOrGroup->element == 'questiongroup') {
            // Flush the ungrouped questions read so far, so the sheet order is preserved
            $steps = array_merge($steps, digiquali_answer_wizard_pack_questions($pendingItems, $object, count($steps), $stepSize));
            $pendingItems = [];

            [$answered, $total] = $questionOrGroup->calculatePoints($object);

            $steps[] = [
                'type'        => 'group',
                'key'         => 'group-' . $questionOrGroup->id,
                'label'       => $questionOrGroup->label,
                'picto'       => $questionOrGroup->picto,
                'description' => $questionOrGroup->description,
                'items'       => [$questionOrGroup],
                'total'       => (int) $total,
                'answered'    => (int) $answered,
            ];
        } else {
            $pendingItems[] = $questionOrGroup;
        }
    }

    $steps = array_merge($steps, digiquali_answer_wizard_pack_questions($pendingItems, $object, count($steps), $stepSize));

    // A single unnamed step needs no title: the page header already says what is being answered
    if (count($steps) == 1 && $steps[0]['type'] == 'questions') {
        $steps[0]['label'] = '';
    } elseif (!empty($steps)) {
        foreach ($steps as $index => $step) {
            if ($step['type'] == 'questions' && empty($step['label'])) {
                $steps[$index]['label'] = $langs->transnoentities('AnswerWizardOtherQuestions');
            }
        }
    }

    return $steps;
}

/**
 * Pack a run of ungrouped questions into steps of at most $stepSize questions.
 *
 * @param  array        $questions  Consecutive ungrouped questions (may be empty).
 * @param  CommonObject $object     Control or Survey being answered.
 * @param  int          $stepOffset Number of steps already built, used to number the packs.
 * @param  int          $stepSize   Maximum number of questions per step.
 * @return array<int,array<string,mixed>> Built steps (empty when $questions is empty).
 */
function digiquali_answer_wizard_pack_questions(array $questions, CommonObject $object, int $stepOffset, int $stepSize): array
{
    global $langs;

    if (empty($questions)) {
        return [];
    }

    $steps  = [];
    $chunks = array_chunk($questions, $stepSize);

    foreach ($chunks as $chunkIndex => $chunkQuestions) {
        $questionIds = [];
        foreach ($chunkQuestions as $question) {
            $questionIds[] = $question->id;
        }

        $steps[] = [
            'type'        => 'questions',
            'key'         => 'questions-' . ($stepOffset + $chunkIndex),
            'label'       => count($chunks) > 1 ? $langs->transnoentities('AnswerWizardQuestionsPack', $chunkIndex + 1, count($chunks)) : '',
            'picto'       => 'fontawesome_fa-question-circle_fas',
            'description' => '',
            'items'       => $chunkQuestions,
            'total'       => count($chunkQuestions),
            'answered'    => digiquali_answer_wizard_count_answered($questionIds, $object),
        ];
    }

    return $steps;
}

/**
 * Count how many of the given questions already hold an answer on the object.
 *
 * Mirrors the "answered" rule of QuestionGroup::calculatePoints(): a line counts as
 * answered as soon as its answer is not an empty string.
 *
 * @param  array<int,int> $questionIds Question ids to look for.
 * @param  CommonObject   $object      Control or Survey holding the answer lines.
 * @return int                         Number of answered questions.
 */
function digiquali_answer_wizard_count_answered(array $questionIds, CommonObject $object): int
{
    if (empty($questionIds) || empty($object->lines)) {
        return 0;
    }

    $answered = 0;
    foreach ($object->lines as $line) {
        if (in_array($line->fk_question, $questionIds) && isset($line->answer) && $line->answer !== '') {
            $answered++;
        }
    }

    return $answered;
}

/**
 * Sum the per-step counters into the global progress of the wizard.
 *
 * @param  array $steps Steps built by digiquali_answer_wizard_build_steps().
 * @return array{answered:int,total:int,percent:int}
 */
function digiquali_answer_wizard_get_progress(array $steps): array
{
    $answered = 0;
    $total    = 0;

    foreach ($steps as $step) {
        $answered += $step['answered'];
        $total    += $step['total'];
    }

    return [
        'answered' => $answered,
        'total'    => $total,
        'percent'  => $total > 0 ? (int) round($answered * 100 / $total) : 0,
    ];
}

/**
 * Return the index of the first step still holding unanswered questions.
 *
 * Used to resume an interrupted session where the user left off. Falls back to the
 * first step when everything is answered (the user then simply reviews and submits).
 *
 * @param  array $steps Steps built by digiquali_answer_wizard_build_steps().
 * @return int          Zero-based step index.
 */
function digiquali_answer_wizard_get_first_incomplete_step(array $steps): int
{
    foreach ($steps as $index => $step) {
        if ($step['answered'] < $step['total']) {
            return $index;
        }
    }

    return 0;
}

/**
 * Return the questions of a step that still have no answer, for the summary screen.
 *
 * Groups are walked recursively so that a question buried in a sub-group is listed too.
 *
 * @param  array        $step   One step built by digiquali_answer_wizard_build_steps().
 * @param  CommonObject $object Control or Survey holding the answer lines.
 * @return array<int,Question>  Unanswered questions, in sheet order.
 */
function digiquali_answer_wizard_get_unanswered_questions(array $step, CommonObject $object): array
{
    $questions = ($step['type'] == 'group')
        ? digiquali_answer_wizard_flatten_group_questions($step['items'][0])
        : $step['items'];

    $answeredIds = [];
    if (!empty($object->lines)) {
        foreach ($object->lines as $line) {
            if (isset($line->answer) && $line->answer !== '') {
                $answeredIds[] = $line->fk_question;
            }
        }
    }

    $unanswered = [];
    foreach ($questions as $question) {
        if (!in_array($question->id, $answeredIds)) {
            $unanswered[] = $question;
        }
    }

    return $unanswered;
}

/**
 * Flatten a question group into its questions, sub-groups included.
 *
 * @param  QuestionGroup $questionGroup Group to walk.
 * @return array<int,Question>          Questions in position order.
 */
function digiquali_answer_wizard_flatten_group_questions(QuestionGroup $questionGroup): array
{
    $questions = $questionGroup->fetchQuestionsOrderedByPosition();
    $questions = is_array($questions) ? $questions : [];

    $subGroups = $questionGroup->fetchQuestionGroupsOrderedByPosition();
    if (is_array($subGroups)) {
        foreach ($subGroups as $subGroup) {
            $questions = array_merge($questions, digiquali_answer_wizard_flatten_group_questions($subGroup));
        }
    }

    return $questions;
}
