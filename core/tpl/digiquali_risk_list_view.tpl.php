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
 * \file    core/tpl/digiquali_risk_list_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for risk lines
 */

/**
 * The following vars must be defined:
 * Global  : $langs, $user
 * Objects : $activity
 */

// Permission
$permissionToAddTask  = $user->hasRight('projet', 'creer') || $user->hasRight('projet', 'all', 'creer');
$permissionToReadTask = $user->hasRight('projet', 'lire') || $user->hasRight('projet', 'all', 'lire');

$riskInfos = $risk->getRiskInfos(); ?>

<div class="risk-list__container gridw-2" id="risk_list_container_<?php echo $activitySingle->id ?>">
    <div class="risk-list__level red"></div> <!-- 4 colors: yellow, orange, red, black -->

    <div class="risk__content">
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
        <div class="risk__content-container">
            <div class="risk__content-heading">
                <div class="ref"><?php echo $riskInfos['risk']['ref'] ?></div>
                <div class="risk-tags">Nom du tag<?php echo $risk->description; ?></div>
                <div class="date"><i class="fas fa-calendar-alt"></i><?php echo $riskInfos['risk']['date'] ?></div>
                <div class="control-percentage"><i class="fas fa-clipboard-list"></i><?php echo $langs->trans('ControlPercentage'); ?> : <strong><?php echo $riskInfos['risk']['control_percentage'] ?></strong></div>
                <div class="residual-risk"><i class="fas fa-exclamation-triangle"></i><?php echo $langs->trans('ResidualRisk'); ?> : <strong><?php echo $riskInfos['risk']['residual_risk'] ?></strong></div>
            </div>
            <div class="risk__content-body">
                <div class="description"><?php echo $riskInfos['risk']['description'] ?></div>
            </div>
        </div>

        <div class="risk-list__actions">
            <div class="wpeo-button button-square-40 button-rounded modal-open">
                <input type="hidden" class="modal-options" data-modal-to-open="risk_add" data-from-id="<?php echo $activitySingle->id; ?>" data-from-type="<?php echo $activitySingle->element; ?>">
                <i class="fas fa-plus"></i>
            </div>
        </div>
    </div>
    <div class="task__content">
        <input type="checkbox" />
        <div class="task__content-container">
            <div class="task__content-heading">
                <div class="task-ref"><?php echo $risk->getNomUrl(1, '', 0, '', -1, 1); ?>TIK2111-0111</div>
                <div class="task-date"><i class="fas fa-calendar-alt"></i> 26/02//2025 - 30/02/2025<?php echo $risk->description; ?></div>
            </div>
            <div class="task__content-body">
                <div class="task-description"><?php echo $risk->description; ?>Description de la tâche</div>
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
