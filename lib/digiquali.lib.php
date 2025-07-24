<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Admin conf.
 */

/**
 * Prepare array of tabs for admin.
 *
 * @return array Array of tabs.
 */
function digiquali_admin_prepare_head(): array
{
    // Global variables definitions.
    global $conf, $langs;

    // Load translation files required by the page.
    saturne_load_langs();

    // Initialize values.
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('digiquali/admin/question.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-question pictofixedwidth"></i>' . $langs->trans('Question') : '<i class="fas fa-question"></i>';
    $head[$h][2] = 'question';
    $h++;

    $head[$h][0] = dol_buildpath('digiquali/admin/questiongroup.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-folder-open pictofixedwidth"></i>' . $langs->trans('QuestionGroup') : '<i class="fas fa-folder-open"></i>';
    $head[$h][2] = 'questiongroup';
    $h++;

    $head[$h][0] = dol_buildpath('saturne/admin/object.php', 1) . '?module_name=DigiQuali&object_type=answer';
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-arrow-right pictofixedwidth"></i>' . $langs->trans('Answer') : '<i class="fas fa-arrow-right"></i>';
    $head[$h][2] = 'answer';
    $h++;

    $head[$h][0] = dol_buildpath('digiquali/admin/sheet.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-list pictofixedwidth"></i>' . $langs->trans('Sheet') : '<i class="fas fa-list"></i>';
    $head[$h][2] = 'sheet';
    $h++;

    $head[$h][0] = dol_buildpath('digiquali/admin/control.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-tasks pictofixedwidth"></i>' . $langs->trans('Control') : '<i class="fas fa-tasks"></i>';
    $head[$h][2] = 'control';
    $h++;

    $head[$h][0] = dol_buildpath('digiquali/admin/survey.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-marker pictofixedwidth"></i>' . $langs->trans('Survey') : '<i class="fas fa-marker"></i>';
    $head[$h][2] = 'survey';
    $h++;

    $head[$h][0] = dol_buildpath('digiquali/admin/mapping.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-sitemap pictofixedwidth"></i>' . $langs->trans('Mapping') : '<i class="fas fa-sitemap"></i>';
    $head[$h][2] = 'survey';
    $h++;

    $head[$h][0] = dol_buildpath('digiquali/admin/publicinterface.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-globe pictofixedwidth"></i>' . $langs->trans('PublicInterface') : '<i class="fas fa-globe"></i>';
    $head[$h][2] = 'publicinterface';
    $h++;

    $head[$h][0] = dol_buildpath('saturne/admin/documents.php?module_name=DigiQuali', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('YourDocuments') : '<i class="fas fa-file-alt"></i>';
    $head[$h][2] = 'documents';
    $h++;

    $head[$h][0] = dol_buildpath('digiquali/admin/setup.php', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans('ModuleSettings') : '<i class="fas fa-cog"></i>';
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('saturne/admin/pwa.php', 1). '?module_name=DigiQuali&start_url=' . dol_buildpath('custom/digiquali/view/control/control_list.php?source=pwa', 3);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-mobile pictofixedwidth"></i>' . $langs->trans('PWA') : '<i class="fas fa-mobile"></i>';
    $head[$h][2] = 'pwa';
    $h++;

    $head[$h][0] = dol_buildpath('saturne/admin/about.php?module_name=DigiQuali', 1);
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fab fa-readme pictofixedwidth"></i>' . $langs->trans('About') : '<i class="fab fa-readme"></i>';
    $head[$h][2] = 'about';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'digiquali');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'digiquali@digiquali', 'remove');

    return $head;
}
