<?php
/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/riskassessment/digiquali_riskassessment_lines_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for the evaluation lines of an activity (one block per current line)
 */

/**
 * The following vars must be defined:
 * Global    : $db, $langs, $user
 * Objects   : $riskAssessment
 * Variables : $activityInfos, $currentAssessments
 */ ?>

<div class="riskassessment-list gridw-2" id="riskassessment_list_container_<?php echo $activityInfos['id']; ?>">
    <div class="riskassessment-list__header">
        <span class="riskassessment-list__title"><?php echo $langs->trans('RiskAssessmentList'); ?></span>
        <div class="wpeo-button button-square-40 button-rounded modal-open riskassessment-new-line" title="<?php echo dol_escape_htmltag($langs->trans('RiskAssessmentNewLine')); ?>">
            <input type="hidden" class="modal-options" data-modal-to-open="riskassessment_create" data-from-id="<?php echo $activityInfos['id']; ?>" data-from-type="<?php echo $activityInfos['element']; ?>">
            <i class="fas fa-plus"></i>
        </div>
    </div>
    <?php if (empty($currentAssessments)) : ?>
        <div class="riskassessment-list__empty opacitymedium"><?php echo $langs->trans('NoRiskAssessment'); ?></div>
    <?php else :
        foreach ($currentAssessments as $riskAssessment) :
            $riskAssessmentInfos = $riskAssessment->getRiskAssessmentInfos();
            require __DIR__ . '/../digiquali_riskassessment_list_view.tpl.php';
        endforeach;
    endif; ?>
</div>
