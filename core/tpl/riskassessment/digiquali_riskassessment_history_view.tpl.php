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
 * \file    core/tpl/riskassessment/digiquali_riskassessment_history_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for the read-only history of an evaluation line
 */

/**
 * The following vars must be defined:
 * Global    : $db, $langs
 * Variables : $lineHistory
 */ ?>

<div class="riskassessment-history">
    <?php if (empty($lineHistory)) : ?>
        <div class="opacitymedium"><?php echo $langs->trans('NoRiskAssessment'); ?></div>
    <?php else :
        foreach ($lineHistory as $historyLine) :
            $historyInfos = $historyLine->getRiskAssessmentInfos();
            $infos        = $historyInfos[$historyLine->element]; ?>
            <div class="riskassessment-history__item riskassessment-list__container gridw-2">
                <div class="riskassessment-list__level <?php echo $infos['risk']; ?>"></div>
                <div class="riskassessment__content">
                    <div class="riskassessment__content-container">
                        <div class="riskassessment__content-heading">
                            <div class="ref"><?php echo $infos['ref']; ?> <?php echo $historyLine->getLibStatut(3); ?></div>
                            <div class="date"><i class="fas fa-calendar-alt"></i><?php echo $infos['date']; ?></div>
                            <div class="control-percentage"><i class="fas fa-shield-alt"></i><?php echo $langs->trans('ControlPercentage'); ?> : <strong><?php echo $infos['control_percentage']; ?></strong></div>
                            <div class="residual-risk"><i class="fas fa-exclamation-triangle"></i><?php echo $langs->trans('ResidualRisk'); ?> : <strong><?php echo $infos['residual_risk']; ?></strong></div>
                        </div>
                        <div class="riskassessment__content-body">
                            <div class="comment"><?php echo $infos['comment']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
    <?php endforeach;
    endif; ?>
</div>
