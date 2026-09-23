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
 * ile    core/tpl/actions/digiquali_media_block_actions.tpl.php
 * \ingroup digiquali
 * \brief   Actions posted by the Saturne media block (js/modules/mediaBlock.js).
 *
 * The block posts on document.URL with action=uploadPhoto|uploadFile|deletePhoto|deleteFile
 * and replays the HTML of the response, so every page hosting the block must handle these
 * four actions and must NOT redirect. Shared by the control card and the PWA answer screen.
 *
 * The following vars must be defined:
 * Global     : $conf, $db, $langs, $user
 * Parameters : $action
 */
// Action to upload a photo via the media block AJAX upload
if ($action == 'uploadPhoto' && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    $uploadModuleName = GETPOST('module_name', 'alpha');
    $uploadSubDir     = GETPOST('sub_dir', 'alpha');

    if (!empty($uploadModuleName)) {
        $uploadModuleNameLowerCase = dol_strtolower($uploadModuleName);
        $uploadDir                = !empty($conf->$uploadModuleNameLowerCase->dir_output)
            ? $conf->$uploadModuleNameLowerCase->dir_output
            : $conf->ecm->dir_output . '/' . $uploadModuleNameLowerCase;
        if (!empty($uploadSubDir)) {
            $uploadDir .= '/' . $uploadSubDir;
        }

        if (!dol_is_dir($uploadDir)) {
            dol_mkdir($uploadDir);
        }

        $uploadedFiles = isset($_FILES['userfile']) ? $_FILES['userfile'] : [];
        $invalidFile   = false;
        if (!empty($uploadedFiles['tmp_name'])) {
            $tmpNames = is_array($uploadedFiles['tmp_name']) ? $uploadedFiles['tmp_name'] : [$uploadedFiles['tmp_name']];
            foreach ($tmpNames as $tmpName) {
                if (empty($tmpName)) {
                    continue;
                }
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpName);
                if (strpos($mimeType, 'image/') !== 0) {
                    $invalidFile = true;
                    break;
                }
            }
        }

        if ($invalidFile) {
            setEventMessages($langs->trans('ErrorFileNotAnImage'), null, 'errors');
        } else {
            $allowOverwrite = GETPOSTINT('overwrite') ? 1 : 0;
            dol_add_file_process($uploadDir, $allowOverwrite, 1, 'userfile', '', null, '', 1);
        }
    }
}

// Action to upload a document into the answer (controldet) linked files via the media block AJAX upload
if ($action == 'uploadFile' && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    $fkControl  = GETPOSTINT('fk_control');
    $fkQuestion = GETPOSTINT('fk_question');

    if ($fkControl > 0 && $fkQuestion > 0) {
        // Resolve the answer line; create it on the fly so a document can be attached before answering
        $answerLine = new ControlLine($db);
        $resLines   = $answerLine->fetchFromParentWithQuestion($fkControl, $fkQuestion);
        if (is_array($resLines) && !empty($resLines)) {
            $answerLine = array_shift($resLines);
        } else {
            $answerLine->fk_control    = $fkControl;
            $answerLine->fk_question   = $fkQuestion;
            $answerLine->status        = ControlLine::STATUS_VALIDATED;
            $answerLine->date_creation = dol_now();
            $answerLine->create($user);
        }

        if ($answerLine->id > 0) {
            $uploadDir = $conf->digiquali->dir_output . '/controldet/' . dol_sanitizeFileName($answerLine->ref);
            if (!dol_is_dir($uploadDir)) {
                dol_mkdir($uploadDir);
            }

            $allowOverwrite = GETPOSTINT('overwrite') ? 1 : 0;
            dol_add_file_process($uploadDir, $allowOverwrite, 1, 'userfile', '', null, '', 1);
        }
    }
}

// Action to delete a document from the answer (controldet) linked files via the media block AJAX upload
// deleteFile = remove an attached document ; deletePhoto = remove an answer photo (same file-removal logic)
if ($action == 'deleteFile' || $action == 'deletePhoto') {
    $uploadModuleName = GETPOST('module_name', 'alpha');
    $uploadSubDir     = GETPOST('sub_dir', 'alpha');
    $fileName         = dol_sanitizeFileName(GETPOST('filename', 'alpha'));

    if (!empty($uploadModuleName) && !empty($fileName) && !empty($uploadSubDir) && strpos($uploadSubDir, '..') === false) {
        $uploadModuleNameLowerCase = dol_strtolower($uploadModuleName);
        $uploadDir                 = !empty($conf->$uploadModuleNameLowerCase->dir_output)
            ? $conf->$uploadModuleNameLowerCase->dir_output
            : $conf->ecm->dir_output . '/' . $uploadModuleNameLowerCase;
        $uploadDir                .= '/' . $uploadSubDir;

        $filePath = $uploadDir . '/' . $fileName;
        if (dol_is_file($filePath)) {
            dol_delete_file($filePath);
        }
    }
}
