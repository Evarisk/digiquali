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
 * \file    lib/digiquali_digiqualielement.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for digiquali element
 */

/**
 * Prepare digiquali element pages header
 *
 * @param  DigiQualiElement $object DigiQuali element
 * @return array            $head   Array of tabs
 * @throws Exception
 */
function digiqualielement_prepare_head(DigiQualiElement $object): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_card.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Processus') : '<i class="fas fa-info-circle"></i>';
    $head[$h][2] = 'card';
    $h++;

    $head[$h][0] = dol_buildpath('saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=' . $object->module . '&object_type=' . $object->element . '&show_nav=0&handle_photo=true';
    $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events') : '<i class="fas fa-calendar-alt"></i>';
    $head[$h][2] = 'agenda';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, $object->element . '@' . $object->module);

    complete_head_from_modules($conf, $langs, $object, $head, $h, $object->element . '@' . $object->module, 'remove');

    return $head;
}
