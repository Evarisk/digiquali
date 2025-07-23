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
 * \file    core/tpl/modal/modal_riskassessment_list.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal risk assessment list
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $activity
 */

$activityInfos = $activity->getActivityInfos(); ?>

<div class="wpeo-modal modal-riskassessment modal-riskassessment-list" id="riskassessment_list">
    <div class="modal-container wpeo-modal-event">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('RiskAssessmentList') . ' ' . $activityInfos['ref']; ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <div class="modal-content">
            <?php $riskAssessment->displayRiskAssessmentList($activityInfos); ?>
        </div>
    </div>
</div>
