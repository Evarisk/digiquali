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
 * \file    core/tpl/actions/digiquali_riskassessment_actions.tpl.php
 * \ingroup digiquali
 * \brief   Template page for risk assessment actions in activity object
 */

/**
 * The following vars must be defined:
 * Global     : $langs, $user
 * Parameters : $action
 * Objects    : $riskAssessment
 */

// Permission
$permissionToAddRiskAssessment    = $user->hasRight($riskAssessment->module, $riskAssessment->element, 'read');
$permissionToDeleteRiskAssessment = $user->hasRight($riskAssessment->module, $riskAssessment->element, 'write');

// Risk assessment action
if ($action == 'create_riskassessment' && !empty($permissionToAddRiskAssessment)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $riskAssessment->comment                              = $data['comment'];
    $riskAssessment->gravity_percentage                   = $data['gravity_percentage'];
    $riskAssessment->frequency_percentage                 = $data['frequency_percentage'];
    $riskAssessment->control_percentage                   = $data['control_percentage'];
    $riskAssessment->{'fk_' . $data['fk_object_element']} = $data['fk_object_id'];

    $riskAssessment->create($user);
    // @todo manage error
}

if ($action == 'fetch_riskassessment') {
    $data = json_decode(file_get_contents('php://input'), true);

    $riskAssessment->fetch($data['from_id']);
    // @todo manage error
}

if ($action == 'update_riskassessment' && !empty($permissionToAddRiskAssessment)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $riskAssessment->fetch($data['object_id']);

    $riskAssessment->comment              = $data['comment'];
    $riskAssessment->gravity_percentage   = $data['gravity_percentage'];
    $riskAssessment->frequency_percentage = $data['frequency_percentage'];
    $riskAssessment->control_percentage   = $data['control_percentage'];

    $riskAssessment->update($user);
    // @todo manage error
}

if ($action == 'delete_riskassessment' && !empty($permissionToDeleteRiskAssessment)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $riskAssessment->fetch($data['object_id']);

    $riskAssessment->delete($user);
    // @todo manage error
}

require_once __DIR__ . '/digiquali_riskassessment_task_actions.tpl.php';
