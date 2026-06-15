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
    $isAutoSave = false;
    $data = [];

    $rawPost = file_get_contents('php://input');
    if (!empty($rawPost) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $jsonPost = json_decode($rawPost, true);
        if (isset($jsonPost['autoSave']) && $jsonPost['autoSave'] == true) {
            $isAutoSave = true;
            $data = $jsonPost;
        }
    } elseif (GETPOST('autoSave', 'alpha') === 'true') {
        $isAutoSave = true;
        $data = $_POST;
    }

    $id = GETPOST('id', 'int');

    if ($id > 0 && empty($object->id)) {
        $object->fetch($id);
    }

    $sheet->fetch($object->fk_sheet);
    $questions = $sheet->fetchAllQuestions();

    if (!empty($questions)) {
        $controlLineObj = new ControlLine($db);
        foreach ($questions as $question) {
            // If AutoSave, ONLY process the specific question to save time
            if ($isAutoSave) {
                if (!isset($data['questionId']) || $question->id != $data['questionId']) {
                    continue;
                }
            }

            $line = new ControlLine($db);
            $resLines = $line->fetchFromParentWithQuestion($object->id, $question->id);
            
            $lineExists = false;
            // fetchFromParentWithQuestion usually returns an array of objects
            if (is_array($resLines) && count($resLines) > 0) {
                $line = array_shift($resLines);
                $lineExists = true;
            } else {
                // Init new line properties manually if it doesn't exist
                $line->fk_control = $object->id;
                $line->fk_question = $question->id;
                $line->status = 1;
            }
            
            if ($isAutoSave) {
                if (isset($data['answer'])) {
                    $line->answer = $data['answer'];
                }
                if (isset($data['comment'])) {
                    $line->comment = $data['comment'];
                }
            } else {
                if (isset($_POST['answer' . $question->id])) {
                    $line->answer = GETPOST('answer' . $question->id);
                }
                if (isset($_POST['comment' . $question->id])) {
                    $line->comment = GETPOST('comment' . $question->id);
                }
            }

            if ($lineExists) {
                if ($line->id > 0) {
                    $res = $line->update($user);
                    if ($res < 0) {
                        setEventMessages($line->error, $line->errors, 'errors');
                    }
                }
            } else {
                if ($line->fk_control > 0 && $line->fk_question > 0) {
                    $line->date_creation = dol_now();
                    $res = $line->create($user);
                    if ($res < 0) {
                        setEventMessages($line->error, $line->errors, 'errors');
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

        $object->call_trigger(dol_strtoupper($object->element) . '_SAVEANSWER', $user);

        setEventMessages($langs->trans('AnswerSaved'), []);
        header('Location: ' . $_SERVER['PHP_SELF'] . (GETPOSTISSET('track_id') ? '?track_id=' . GETPOST('track_id', 'alpha')  . '&object_type=' . GETPOST('object_type', 'alpha') . '&document_type=' . GETPOST('document_type', 'alpha') . '&entity=' . $conf->entity : '?id=' . GETPOST('id', 'int')));
        exit;
    }
}
