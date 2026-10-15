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
 * \file    lib/digiquali_sheet.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Sheet.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Prepare array of tabs for sheet.
 *
 * @param  Sheet $object Sheet object.
 * @return array         Array of tabs.
 * @throws Exception
 */
function sheet_prepare_head(Sheet $object): array
{
    // Global variables definitions
    global $conf, $langs;

    $head[1][0] = dol_buildpath('/digiquali/view/sheet/sheet_stats.php', 1) . '?id=' . $object->id;
    $head[1][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-chart-bar pictofixedwidth"></i>' . $langs->trans('Statistics') : '<i class="fas fa-chart-bar"></i>';
    $head[1][2] = 'stats';

    $head[2][0] = dol_buildpath('/digiquali/view/sheet/sheet_export.php', 1) . '?id=' . $object->id;
    $head[2][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-export pictofixedwidth"></i>' . $langs->trans('Export') : '<i class="fas fa-file-export"></i>';
    $head[2][2] = 'export';

    return saturne_object_prepare_head($object, $head);
}

/**
 * Build the message of a notice sending the user to the sheet configuration.
 *
 * Only a user allowed to change the configuration gets the link: the others would be sent to a
 * page that refuses them or shows its settings read only, they are asked to call an administrator.
 *
 * @param  string $linkMessage     Message shown as a link to the configuration
 * @param  string $askAdminMessage Message shown, without link, to the users who may not change it
 * @param  string $anchor          Anchor of the configuration page, with its leading #
 * @return string                  HTML of the message
 */
function digiquali_sheet_config_notice_message(string $linkMessage, string $askAdminMessage, string $anchor = ''): string
{
    if (saturne_check_admin_write_access()) {
        return '<a href="' . dol_buildpath('custom/digiquali/admin/sheet.php', 1) . $anchor . '">' . $linkMessage . '</a>';
    }

    return $askAdminMessage;
}
