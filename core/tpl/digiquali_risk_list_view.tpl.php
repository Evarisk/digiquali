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

?>

<div class="risk-list__container" id="risk_list_container_<?php echo $activitySingle->id ?>">
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
        </div>
        <div class="risk__content-medias">
            <?php echo saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, $question, '', 0, $object->status == 0, 1); ?>
        </div>
        <div class="risk-ref"><?php echo $risk->getNomUrl(1, '', 0, '', -1, 1); ?></div>
        <div class="risk-tags"><?php echo $risk->description; ?></div>
        <div class="risk-date"><?php echo $risk->description; ?></div>
        <div class="risk-control-percentage"><?php echo $risk->control_percentage; ?></div>
        <div class="risk-residual-risk"><?php echo $risk->description; ?></div>
        <div class="risk-description"><?php echo $risk->description; ?></div>
        <div class="wpeo-button modal-open">
            <input type="hidden" class="modal-options" data-modal-to-open="risk_add" data-from-id="<?php echo $activitySingle->id; ?>" data-from-type="<?php echo $activitySingle->element; ?>">
            <i class="fas fa-plus"></i>
        </div>
    </div>
    <div class="task__content">
        <div class="task-ref"><?php echo $risk->getNomUrl(1, '', 0, '', -1, 1); ?></div>
        <div class="task-date"><?php echo $risk->description; ?></div>
        <div class="task-description"><?php echo $risk->description; ?></div>
        <?php if (!empty($object->project) && !empty($permissionToAddTask)) : ?>
            <div class="wpeo-button button-square-50 add-action modal-open">
                <input type="hidden" class="modal-options" data-modal-to-open="answer_task_add" data-from-id="<?php echo $objectLine->id ?>" data-from-type="<?php echo $objectLine->element ?>"/>
                <i class="fas fa-list"></i><i class="fas fa-plus-circle button-add"></i>
            </div>
        <?php endif; ?>
        <?php if (!empty($permissionToReadTask)) :
            require __DIR__ . '/answers/answers_task_view.tpl.php';
        endif; ?>
    </div>
</div>
