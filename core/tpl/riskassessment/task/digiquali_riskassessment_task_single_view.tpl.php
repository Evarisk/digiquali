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
 * \file    core/tpl/digiquali_riskassessment_task_single_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for risk assessment task single view
 */

/**
 * The following vars must be defined:
 * Globals : $langs
 */ ?>

<div class="riskassessment-task__content">
    <input type="checkbox" />
    <div class="riskassessment-task__content-container">
        <div class="riskassessment-task__content-heading">
            <div class="ref"><?php echo $riskAssessmentInfos['ref']; ?></div>
            <div class="date"><i class="fas fa-calendar-alt"></i><?php echo $riskAssessmentInfos['date']; ?></div>
        </div>
        <div class="riskassessment-task__content-body">
            <div class="label"><?php echo $riskAssessmentInfos['label']; ?></div>
        </div>
    </div>
</div>
