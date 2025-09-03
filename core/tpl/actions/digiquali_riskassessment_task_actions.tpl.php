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
 * \file    core/tpl/actions/digiquali_riskassessment_task_actions.tpl.php
 * \ingroup digiquali
 * \brief   Template page for risk assessment task actions in risk assessment object
 */

/**
 * The following vars must be defined:
 * Globals    : $langs, $user
 * Parameters : $action
 * Objects    : $riskAssessmentTask
 */

// Permission
//$permissionToAddRiskAssessmentTask    = $user->hasRight($riskAssessmentTask->module, $riskAssessmentTask->element, 'read');
//$permissionToDeleteRiskAssessmentTask = $user->hasRight($riskAssessmentTask->module, $riskAssessmentTask->element, 'write');
$permissionToAddRiskAssessmentTask    = 1;
$permissionToDeleteRiskAssessmentTask = 1;


// Risk assessment action
if ($action == 'create_riskassessment_task' && !empty($permissionToAddRiskAssessmentTask)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $riskAssessmentTask->ref                                                       = 'TK0001-001';
    $riskAssessmentTask->label                                                     = $data['label'];
    $riskAssessmentTask->fk_project                                                = getDolGlobalInt('DIGIQUALI_MAPPING_PROJECT');
//    $riskAssessmentTask->date_start                                                = $data['date_start'];
//    $riskAssessmentTask->date_end                                                  = $data['date_end'];
    $riskAssessmentTask->budget_amount                                             = $data['budget_amount'];
//    $riskAssessmentTask->array_options['options_fk_' . $data['fk_object_element']] = $data['fk_object_id'];

    $riskAssessmentTask->create($user);
    // @todo manage error
}

if ($action == 'fetch_riskassessment_task') {
    $data = json_decode(file_get_contents('php://input'), true);

    $riskAssessmentTask->fetch($data['from_id']);
    // @todo manage error
}

if ($action == 'update_riskassessment_task' && !empty($permissionToAddRiskAssessmentTask)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $riskAssessmentTask->fetch($data['object_id']);

    $riskAssessmentTask->comment              = $data['comment'];
    $riskAssessmentTask->gravity_percentage   = $data['gravity_percentage'];
    $riskAssessmentTask->frequency_percentage = $data['frequency_percentage'];
    $riskAssessmentTask->control_percentage   = $data['control_percentage'];

    $riskAssessmentTask->update($user);
    // @todo manage error
}

if ($action == 'delete_riskassessment_task' && !empty($permissionToDeleteRiskAssessmentTask)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $riskAssessmentTask->fetch($data['object_id']);

    $riskAssessmentTask->delete($user);
    // @todo manage error
}
