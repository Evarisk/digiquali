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
 * \file    core/tpl/digiquali_riskassessment_single_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for riskassessment single view
 */

/**
 * The following vars must be defined:
 * variables : $riskAssessmentInfos
 */ ?>

<div class="riskassessment__content">
    <div class="riskassessment__content-container">
<!--        <div class="linked-medias linked-medias-list answer_photo_--><?php //echo $question->id ?><!--">-->
<!--            <div class="risk__content-medias">-->
<!--                --><?php //echo saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, $question, '', 0, $object->status == 0, 1); ?>
<!--            </div>-->
<!--        </div>-->
        <div class="riskassessment__content-heading">
            <div class="ref"><?php echo $riskAssessmentInfos['ref']; ?></div>
            <div class="date"><i class="fas fa-calendar-alt"></i><?php echo $riskAssessmentInfos['date']; ?></div>
            <div class="control-percentage"><i class="fas fa-shield-alt"></i><?php echo $langs->trans('ControlPercentage'); ?> : <strong><?php echo $riskAssessmentInfos['control_percentage']; ?></strong></div>
            <div class="residual-risk"><i class="fas fa-exclamation-triangle"></i><?php echo $langs->trans('ResidualRisk'); ?> : <strong><?php echo $riskAssessmentInfos['residual_risk']; ?></strong></div>
        </div>
        <div class="riskassessment__content-body">
            <div class="comment"><?php echo $riskAssessmentInfos['comment']; ?></div>
        </div>
    </div>
</div>
