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
    global $conf, $langs, $user;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = -10;
    $head = [];

    if ($object->id > 0 && $user->hasRight($object->module, $object->element, 'read')) {
        if ($user->hasRight($object->module, 'activity', 'read')) {
            $head[$h][0] = dol_buildpath($object->module . '/view/' . $object->element . '/' . $object->element . '_view.php', 1) . '?id=' . $object->id;
            $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-list pictofixedwidth"></i>' . $langs->trans('ProcessusActivities') : '<i class="fas fa-list"></i>';

            require_once __DIR__ . '/../class/activity.class.php';
            $nbActivities = Activity::getNbActivities($object);
            if ($nbActivities > 0) {
                $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbActivities . '</span>';
            }

            $head[$h][2] = 'activity';
        }

//        if ($user->hasRight('ticket', 'read')) {
//            $head[$h][0] = dol_buildpath($object->module . '/view/digiriskelement/digiriskelement_register.php', 1) . '?id=' . $object->id;
//            $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fa fa-ticket-alt pictofixedwidth"></i>' . $langs->trans('Tickets') : '<i class="fas fa-ticket-alt"></i>';
//            $head[$h][2] = 'elementRegister';
//        }
    }

    $moreparam['specialName'] = $langs->trans(dol_ucfirst($object->element_type));
    $moreparam['handlePhoto'] = true;

    return saturne_object_prepare_head($object, $head, $moreparam);
}
