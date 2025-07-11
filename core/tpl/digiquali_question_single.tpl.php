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
 * \file    core/tpl/digiquali_question_single.tpl.php
 * \ingroup digiquali
 * \brief   Template page for question lines
 */

if (!$user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER or empty($questionAnswer)) : ?>
    <div class="question table-id-<?php echo $question->id ?>" data-autoSave="<?php echo getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . 'DET_AUTO_SAVE_ACTION'); ?>">
        <?php if ($question->show_photo > 0 && getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . '_DISPLAY_MEDIAS') && !empty($user->conf->DIGIQUALI_SHOW_OK_KO_PHOTOS)) { ?>
            <div class="question__header-medias">
                <div class="question__photo-ref-ok">
                    <i class="question__photo-ref-icon fas fa-check"></i>
                    <?php print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/' . $question->ref . '/photo_ok', 'small', '', 0, 0, 0, 200, 200, 0, 0, 1, 'question/' . $question->ref . '/photo_ok', $question, 'photo_ok', 0, 0, 0, 1, 'photo-ok', 0); ?>
                </div>
                <div class="question__photo-ref-ko">
                    <i class="question__photo-ref-icon fas fa-times"></i>
                    <?php print saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/question/' . $question->ref . '/photo_ko', 'small', '', 0, 0, 0, 200, 200, 0, 0, 1, 'question/' . $question->ref . '/photo_ko', $question, 'photo_ko', 0, 0, 0, 1, 'photo-ko', 0); ?>
                </div>
            </div>
        <?php } ?>
        <div class="question__container">
            <div class="question__header">
                <div class="question__header-content">
                    <div class="question-title"><?php echo $question->getNomUrl(1, '', 0, '', -1, 1); ?></div>
                    <div class="question-description"><?php echo $question->description; ?></div>
                </div>
                <div class="question__header-answer">
                    <?php print show_answer_from_question($question, $object, $questionAnswer, $questionGroupId); ?>
                </div>
            </div>
            <div class="question__footer">
                <?php if ($question->enter_comment > 0) : ?>
                    <label class="question__footer-comment">
                        <i class="far fa-comment-dots question-comment-icon"></i>
                        <textarea class="question-textarea question-comment" name="comment<?php echo $question->id . "_" . $questionGroupId; ?>" placeholder="<?php echo $langs->transnoentities('WriteComment'); ?>" <?php echo ($object->status == 2 ? 'disabled' : ''); ?>><?php echo $comment; ?></textarea>
                    </label>
                <?php endif; ?>
                <?php if ($question->authorize_answer_photo > 0) : ?>
                    <div class="question__footer-linked-medias">
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
                    </div>
                <?php endif; ?>
                <?php if (!empty($object->project) && !empty($permissionToAddTask)) : ?>
                    <div class="wpeo-button button-square-50 add-action modal-open">
                        <input type="hidden" class="modal-options" data-modal-to-open="answer_task_add" data-from-id="<?php echo $objectLine->id ?>" data-from-type="<?php echo $objectLine->element ?>"/>
                        <i class="fas fa-list"></i><i class="fas fa-plus-circle button-add"></i>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($question->authorize_answer_photo > 0) : ?>
                <div class="question__list-medias">
                    <?php echo saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, 'small', '', 0, 0, 0, 50, 50, 0, 0, 0, $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, $question, '', 0, $object->status == 0, 1); ?>
                </div>
            <?php endif;
            if (!empty($permissionToReadTask)) :
                require __DIR__ . '/answers/answers_task_view.tpl.php';
            endif; ?>
        </div>
    </div>
<?php endif;
