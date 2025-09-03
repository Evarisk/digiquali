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
 * \file    core/tpl/digiquali_answers_save_action.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers save action
 */

/**
 * The following vars must be defined:
 * Global     : $conf, $langs, $user
 * Parameters : $action
 * Objects    : $object, $objectLine, $sheet
 */

if ($action == 'save') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sheet->fetch($object->fk_sheet);

    $questions = $sheet->fetchAllQuestions();

    if (!empty($questions)) {
        foreach ($questions as $question) {
            if (!empty($object->lines)) {
                foreach ($object->lines as $line) {
                    if ($line->fk_question === $question->id) {

                        // Save answer value
                        if ($data['autoSave'] && $question->id == $data['questionId']) {
                            $questionAnswer = $data['answer'];
                        } else {
                            $questionAnswer = GETPOST('answer' . $question->id);
                        }

                        if (!empty($questionAnswer)) {
                            $line->answer = $questionAnswer;
                        }

                        // Save answer comment
                        if ($data['autoSave'] && $question->id == $data['questionId']) {
                            $comment = $data['comment'];
                        } else {
                            $comment = GETPOST('comment' . $question->id);
                        }
                        if (dol_strlen($comment) > 0) {
                            $line->comment = $comment;
                        }

                        $line->update($user);
                    }
                }
            }
        }
    }

    if (GETPOSTISSET('public_interface')) {
        $object->validate($user);
        if ($sheet->type == 'survey') {
            $object->setLocked($user);
        }
    }

    $object->call_trigger(dol_strtoupper($object->element) . '_SAVEANSWER', $user);
    setEventMessages($langs->trans('AnswerSaved'), []);
    header('Location: ' . $_SERVER['PHP_SELF'] . (GETPOSTISSET('track_id') ? '?track_id=' . GETPOST('track_id', 'alpha')  . '&object_type=' . GETPOST('object_type', 'alpha') . '&document_type=' . GETPOST('document_type', 'alpha') . '&entity=' . $conf->entity : '?id=' . GETPOST('id', 'int')));
    exit;
}
