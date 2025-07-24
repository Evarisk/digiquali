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
 * \file    core/tpl/digiquali_riskassessment_list_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for riskassessment lines
 */

/**
 * The following vars must be defined:
 * Global    : $db, $langs, $user
 * Objects   : $riskAssessment
 * variables : $activityInfos, $riskAssessmentInfos
 */

// Permission
$permissionToAddTask  = $user->hasRight('projet', 'creer') || $user->hasRight('projet', 'all', 'creer');
$permissionToReadTask = $user->hasRight('projet', 'lire') || $user->hasRight('projet', 'all', 'lire'); ?>

<div class="riskassessment-list__container gridw-2" id="riskassessment_list_container_<?php echo $activityInfos['id']; ?>">
    <div class="riskassessment-list__level <?php echo $riskAssessmentInfos[$riskAssessment->element]['risk']; ?>"></div>

    <div class="riskassessment__content">
        <?php $riskAssessment->displayRiskAssessmentView($riskAssessmentInfos[$riskAssessment->element]); ?>

        <div class="riskassessment-list__actions">
            <div class="wpeo-button button-square-40 button-rounded modal-open">
                <input type="hidden" class="modal-options" data-modal-to-open="riskassessment_create" data-from-id="<?php echo $activityInfos['id']; ?>" data-from-type="<?php echo $activityInfos['element']; ?>">
                <i class="fas fa-plus"></i>
            </div>
            <?php if ($riskAssessmentInfos[$riskAssessment->element]['id'] > 0) : ?>
                <div class="wpeo-button button-square-40 button-rounded modal-open">
                    <input type="hidden" class="modal-options" data-modal-to-open="riskassessment_update" data-from-id="<?php echo $riskAssessmentInfos[$riskAssessment->element]['id']; ?>" data-from-module="<?php echo $riskAssessment->module; ?>">
                    <i class="fas fa-pen"></i>
                </div>
                <div class="wpeo-button button-square-40 button-rounded modal-open">
                    <input type="hidden" class="modal-options" data-modal-to-open="riskassessment_list" data-from-id="<?php echo $activityInfos['id']; ?>" data-from-type="<?php echo $activityInfos['element']; ?>" data-from-module="<?php echo $riskAssessment->module; ?>">
                    <i class="fas fa-list"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($permissionToReadTask)) : ?>
        <div class="riskassessment-task__content" id="riskassessment_task_list_container_<?php echo $riskAssessmentInfos[$riskAssessment->element]['id']; ?>">
            <?php $riskAssessment->displayRiskAssessmentTaskView($riskAssessmentInfos['project_task']);

            if (getDolGlobalInt('DIGIQUALI_MAPPING_PROJECT') && !empty($permissionToAddTask)) : ?>
                <div class="riskassessment-task__actions">
                    <div class="wpeo-button button-square-40 button-rounded modal-open">
                        <input type="hidden" class="modal-options" data-modal-to-open="riskassessment_task_create" data-from-id="<?php echo $riskAssessmentInfos[$riskAssessment->element]['id']; ?>" data-from-type="<?php echo $riskAssessment->element; ?>">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
