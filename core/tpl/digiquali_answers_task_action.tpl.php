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
 * \file    core/tpl/digiquali_answers_task_action.tpl.php
 * \ingroup digiquali
 * \brief   Template page for answers task action
 */

/**
 * The following vars must be defined:
 * Global     : $langs, $user
 * Parameters : $action
 * Objects    : $task
 * Variables  : $permissionToAddTask, $permissionToDeleteTask, $permissionToManageTaskTimeSpent, $taskNextValue
 */

// Task action
if ($action == 'add_task' && !empty($permissionToAddTask)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $task->ref        = $taskNextValue;
    $task->label      = $data['label'];
    $task->fk_project = $data['fk_project'];
    $task->date_c     = dol_now();
    if (!empty($data['date_start'])) {
        $task->date_start = dol_stringtotime($data['date_start']);
    } else {
        $task->date_start = dol_now('tzuser');
    }
    if (!empty($data['date_end'])) {
        $task->date_end = dol_stringtotime($data['date_end']);
    }
    $task->budget_amount  = $data['budget_amount'] ?? null;
    $task->fk_task_parent = !empty($object->fk_master_task) ? $object->fk_master_task : 0;

    $taskId = $task->create($user);
    if ($taskId > 0 && !empty($data['fk_user_assign'])) {
        $task->add_contact($data['fk_user_assign'], 'TASKEXECUTIVE', 'internal');
    }
    $task->add_object_linked($data['objectLine_element'], $data['objectLine_id']);
    // @todo manage error
}

if ($action == 'fetch_task') {
    $data = json_decode(file_get_contents('php://input'), true);
    $task->fetch($data['from_id']);
}

if ($action == 'update_task' && !empty($permissionToAddTask)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $task->fetch($data['task_id']);

    $task->label = $data['label'];
    if (!empty($data['date_start'])) {
        $task->date_start = dol_stringtotime($data['date_start']);
    } else {
        $task->date_start = dol_now('tzuser');
    }
    if (!empty($data['date_end'])) {
        $task->date_end = dol_stringtotime($data['date_end']);
    }
    $task->budget_amount = $data['budget'];
    if (isset($data['progress'])) {
        $task->progress = max(0, min(100, (int) $data['progress']));
    }

    $task->update($user);

    // Sync the assigned user (responsable) of the task
    if (array_key_exists('fk_user_assign', $data)) {
        $existingContacts = $task->liste_contact(-1, 'internal', 0, 'TASKEXECUTIVE');
        if (is_array($existingContacts)) {
            foreach ($existingContacts as $existingContact) {
                $task->delete_contact($existingContact['rowid']);
            }
        }
        if ($data['fk_user_assign'] > 0) {
            $task->add_contact($data['fk_user_assign'], 'TASKEXECUTIVE', 'internal');
        }
    }
    // @todo manage error
}

if ($action == 'update_task_progress' && !empty($permissionToAddTask)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $task->fetch($data['task_id']);

    $task->progress = max(0, min(100, (int) $data['progress']));

    $result = $task->update($user);
    if ($result < 0) {
        // Update task progress KO
        header('HTTP/1.1 500 Internal Server');
        die(json_encode(['message' => $langs->transnoentities($task->error), 'code' => '1337']));
    }
}

if ($action == 'delete_task' && !empty($permissionToDeleteTask)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $task->fetch($data['task_id']);

    $result = $task->delete($user);
    if ($result > 0) {
        $task->deleteObjectLinked($data['objectLine_id'], $data['objectLine_element'], $data['task_id'], $task->element);
    } else {
        // Delete task KO
        header('HTTP/1.1 500 Internal Server');
        die(json_encode(['message' => $langs->transnoentities($task->error), 'code' => '1337']));
    }
}

if ($action == 'check_task' && !empty($permissionToAddTask)) {
    $taskId = GETPOSTINT('task_id');
    $task->fetch($taskId);

    if ($task->progress == 0) {
        $task->progress = 100;
    } else {
        $task->progress = 0;
    }

    $result = $task->update($user);
    if ($result < 0) {
        // Update task KO
        header('HTTP/1.1 500 Internal Server');
        die(json_encode(['message' => $langs->transnoentities($task->error), 'code' => '1337']));
    }
}

// Task time spent action
if ($action == 'add_task_timespent' && !empty($permissionToManageTaskTimeSpent)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $taskId   = $data['task_id'];
    $comment  = $data['comment'];
    $date     = $data['date'];
    $duration = $data['duration'];

    $task->fetch($taskId);

    if (!empty($date)) {
        $task->timespent_date = dol_stringtotime($date);
    } else {
        $task->timespent_date = dol_now('tzuser');
    }
    $task->timespent_note     = $comment;
    $task->timespent_duration = $duration * 60;
    $task->timespent_fk_user  = $user->id;

    $task->addTimeSpent($user);
    // @todo manage error
}

if ($action == 'fetch_task_timespent') {
    $data = json_decode(file_get_contents('php://input'), true);
    $task->fetchTimeSpent($data['from_id']);
}

if ($action == 'update_task_timespent' && !empty($permissionToManageTaskTimeSpent)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $task->fetchTimeSpent($data['task_timespent_id']);

    $date = $data['date'];
    if (!empty($date)) {
        $task->timespent_date = dol_stringtotime($date);
    } else {
        $task->timespent_date = dol_now('tzuser');
    }
    $task->timespent_note     = $data['comment'];
    $task->timespent_duration = $data['duration'] * 60;

    $task->updateTimeSpent($user);
    // @todo manage error
}

if ($action == 'delete_task_timespent' && !empty($permissionToManageTaskTimeSpent)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $task->fetchTimeSpent($data['task_timespent_id']);

    $task->delTimeSpent($user);
    // @todo manage error
}
