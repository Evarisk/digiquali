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
        <div class="linked-medias linked-medias-list answer_photo_<?php echo $question->id ?>">
            <?php if ($object->status == 0) : ?>
                <input hidden multiple class="fast-upload<?php echo getDolGlobalInt('SATURNE_USE_FAST_UPLOAD_IMPROVEMENT') ? '-improvement' : ''; ?>" id="fast-upload-answer-photo<?php echo $question->id ?>" type="file" name="userfile[]" capture="environment" accept="image/*">
                <input type="hidden" class="question-answer-photo" id="answer_photo_<?php echo $question->id ?>" name="answer_photo_<?php echo $question->id ?>" value=""/>
                <input type="hidden" class="fast-upload-options" data-from-subtype="answer_photo_<?php echo $question->id ?>" data-from-subdir="answer_photo/<?php echo $question->ref ?>"/>
                <label for="fast-upload-answer-photo<?php echo $question->id ?>">
                    <div class="wpeo-button button-square-50">
                        <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
                    </div>
                </label>
                <div class="wpeo-button button-square-50 open-media-gallery add-media modal-open" value="<?php echo $question->id ?>">
                    <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $object->id ?>" data-from-type="<?php echo $object->element ?>" data-from-subtype="answer_photo_<?php echo $question->id ?>" data-from-subdir="answer_photo/<?php echo $question->ref ?>"/>
                    <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
                </div>
            <?php endif; ?>
            <div class="risk__content-medias">
                <?php echo saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, $question, '', 0, $object->status == 0, 1); ?>
            </div>
        </div>
        <div class="riskassessment__content-container">
            <div class="riskassessment__content-heading">
                <div class="ref"><?php echo $riskAssessmentInfos[$riskAssessment->element]['ref'] ?></div>
<!--                <div class="tags">Nom du tag--><?php //echo @todo: Add tags $risk->description; ?><!--</div>-->
                <div class="date"><i class="fas fa-calendar-alt"></i><?php echo $riskAssessmentInfos[$riskAssessment->element]['date'] ?></div>
                <div class="control-percentage"><i class="fas fa-shield-alt"></i><?php echo $langs->trans('ControlPercentage'); ?> : <strong><?php echo $riskAssessmentInfos[$riskAssessment->element]['control_percentage'] ?></strong></div>
                <div class="residual-risk"><i class="fas fa-exclamation-triangle"></i><?php echo $langs->trans('ResidualRisk'); ?> : <strong><?php echo $riskAssessmentInfos[$riskAssessment->element]['residual_risk'] ?></strong></div>
            </div>
            <div class="riskassessment__content-body">
                <div class="comment"><?php echo $riskAssessmentInfos[$riskAssessment->element]['comment'] ?></div>
            </div>
        </div>

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
    <div class="task__content">
        <input type="checkbox" />
        <div class="task__content-container">
            <div class="task__content-heading">
                <div class="ref"><?php echo $riskAssessmentInfos['project_task']['ref']; ?></div>
                <div class="date"><i class="fas fa-calendar-alt"></i><?php echo $riskAssessmentInfos['project_task']['date']; ?></div>
            </div>
            <div class="task__content-body">
                <div class="label"><?php echo $riskAssessmentInfos['project_task']['label']; ?></div>
            </div>
        </div>

        <?php if (!empty($object->project) && !empty($permissionToAddTask)) : ?>
            <div class="risk-list__actions">
                <div class="wpeo-button button-square-40 button-rounded add-action modal-open">
                    <input type="hidden" class="modal-options" data-modal-to-open="answer_task_add" data-from-id="<?php echo $objectLine->id ?>" data-from-type="<?php echo $objectLine->element ?>"/>
                    <i class="fas fa-list"></i><i class="fas fa-plus-circle button-add"></i>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($permissionToReadTask)) :
            require __DIR__ . '/answers/answers_task_view.tpl.php';
        endif; ?>
    </div>
</div>
