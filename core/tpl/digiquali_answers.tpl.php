<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiquali_answers.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers lines
 */

/**
 * The following vars must be defined:
 * Global    : $conf, $langs, $user
 * Objects   : $object, $sheet
 * Variables : $permissionToAddTask, $permissionToReadTask
 */
if (is_array($questionsAndGroups) && !empty($questionsAndGroups)) {
    foreach ($questionsAndGroups as $questionOrGroup) {
        $questionAnswer = '';
        $comment        = '';

        $questionGroupId = 0;
        if ($questionOrGroup->element == 'questiongroup') {
            $questionGroupId = $questionOrGroup->id;

            $questionGroup->fetch($questionGroupId);
            $groupQuestions = $questionGroup->fetchQuestionsOrderedByPosition();
            print '<div class="digiquali-question-group">';
            print '<h3>' . img_picto('', $questionGroup->picto) . ' ' . htmlspecialchars($questionGroup->label) . '</h3>';
            if (!empty($questionGroup->description)) {
                print '<p class="group-description">' . nl2br(htmlspecialchars($questionGroup->description)) . '</p>';
            }

            if (is_array($groupQuestions) && !empty($groupQuestions)) {
                print '<div class="group-questions">';
                foreach ($groupQuestions as $question) {
                    $result = $objectLine->fetchFromParentWithQuestion($object->id, $question->id, $questionGroupId);
                    if (is_array($result) && !empty($result)) {
                        $objectLine = array_shift($result);
                        $questionAnswer = $objectLine->answer;
                        $comment = $objectLine->comment;
                        $objectLine->fetchObjectLinked($objectLine->id, $objectLine->element);
                    }

                    $question = $question;
                    include __DIR__ . '/digiquali_question_single.tpl.php';
                }
                print '</div>';
            }
            print '</div>';
        } else {
            $result = $objectLine->fetchFromParentWithQuestion($object->id, $questionOrGroup->id, 0);
            if (is_array($result) && !empty($result)) {
                $objectLine = array_shift($result);
                $questionAnswer = $objectLine->answer;
                $comment = $objectLine->comment;
                $objectLine->fetchObjectLinked($objectLine->id, $objectLine->element);
            }
            $question = $questionOrGroup;

            include __DIR__ . '/digiquali_question_single.tpl.php';
        }
    }
}
?>
