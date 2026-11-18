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
