<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_question.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Question.
 */

// Load Saturne libraries.
dol_include_once('/saturne/lib/object.lib.php');

/**
 * Prepare array of tabs for question.
 *
 * @param  Question $object Question object.
 * @return array            Array of tabs.
 * @throws Exception
 */
function question_prepare_head(Question $object): array
{
    return saturne_object_prepare_head($object);
}

/**
 * Render the help of the question types whose answer works unlike a choice, to print under the type selector
 *
 * One block per such type, the one of the picked type shown : question.js swaps them when another type is picked
 *
 * @param  string $selectedType Type picked on the form, or type of the question
 * @return string               HTML output
 */
function digiquali_question_type_help(string $selectedType): string
{
    global $langs;

    $typeHelps = [Question::TYPE_PHOTO_MULTIPLE => 'PhotoMultipleTypeHelp'];

    $out = '';
    foreach ($typeHelps as $type => $helpKey) {
        $out .= '<div class="question-type-help opacitymedium' . ($type == $selectedType ? '' : ' hidden') . '" data-question-type="' . dol_escape_htmltag($type) . '">';
        $out .= '<i class="fas fa-info-circle" aria-hidden="true"></i> ' . $langs->trans($helpKey);
        $out .= '</div>';
    }

    return $out;
}
