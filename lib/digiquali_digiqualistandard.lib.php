<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_digiqualistandard.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for digiquali standard
 */

/**
 * Prepare digiquali standard pages header
 *
 * @param  DigiqualiStandard $object Digiquali standard
 * @return array            $head   Array of tabs
 * @throws Exception
 */
function digiqualistandard_prepare_head(DigiqualiStandard $object): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('digiquali/view/digiqualistandard/digiqualistandard_card.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Informations') : '<i class="fas fa-info-circle"></i>';
    $head[$h][2] = 'card';
    $h++;

    $head[$h][0] = dol_buildpath('saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiQuali&object_type=digiqualistandard&show_nav=0&handle_photo=true';
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events') : '<i class="fas fa-calendar-alt"></i>';
    $head[$h][2] = 'agenda';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiqualistandard@digiquali');

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiqualistandard@digiquali', 'remove');

    return $head;
}
