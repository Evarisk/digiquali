<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiquali_activity_action.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers activity action
 */

/**
 * The following vars must be defined:
 * Global     : $langs, $user
 * Parameters : $action
 * Objects    : $activity
 * Variables  : $permissionToAddActivity, $permissionToDeleteActivity
 */

// Activity action
if ($action == 'add_activity' && !empty($permissionToAddActivity)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $activity->label      = $data['label'];
    $activity->fk_element = $data['objectLine_id'];

    $activity->create($user);
    // @todo manage error
}

if ($action == 'fetch_activity') {
    $data = json_decode(file_get_contents('php://input'), true);
    $activity->fetch($data['from_id']);
    // @todo manage error
}

if ($action == 'update_activity' && !empty($permissionToAddActivity)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $activity->fetch($data['object_id']);

    $activity->label = $data['label'];

    $activity->update($user);
    // @todo manage error
}

if ($action == 'delete_activity' && !empty($permissionToDeleteActivity)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $activity->fetch($data['activity_id']);

    $activity->delete($user);
    // @todo manage error
}
