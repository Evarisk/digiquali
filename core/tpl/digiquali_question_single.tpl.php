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

if (!isset($user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER) || empty($user->conf->DIGIQUALI_SHOW_ONLY_QUESTIONS_WITH_NO_ANSWER) || empty($questionAnswer)) : ?>
    <?php
        $questionWithCorrectAnswerCssClass = '';
        $questionWithCorrectAnswer = $question->checkAnswerIsCorrect($questionAnswer);
        $showCorrection = ($object->status >= $object::STATUS_LOCKED && !$isFrontend);
        if ($showCorrection) {
            if ($questionWithCorrectAnswer > 0) {
                $questionWithCorrectAnswerCssClass = ' correct';
            } else if ($questionWithCorrectAnswer < 0) {
                $questionWithCorrectAnswerCssClass = ' incorrect';
            }
        }
    ?>
    <div class="question<?php echo $questionWithCorrectAnswerCssClass ?> table-id-<?php echo $question->id ?> <?php echo !empty($objectLine->answer) ? 'question-complete' : ''; ?>" data-autoSave="<?php echo getDolGlobalInt('DIGIQUALI_' . dol_strtoupper($object->element) . 'DET_AUTO_SAVE_ACTION'); ?>">
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
                    <div class="question-title">
                        <span class="question-ref"><?php echo $question->getNomUrl(1, '', 0, '', -1, 1); ?></span>
                        <span class="question-type"><?php echo $langs->trans($question->type); ?></span>
                    </div>
                    <div class="question-description"><?php echo $question->description; ?></div>
                    <div class="question-points"><?php echo ($showCorrection ? $question->formatSingleQuestionScore($questionWithCorrectAnswer, $objectLine->answer) : '') ?></div>
                </div>
                <div class="question__header-answer">
                    <?php print show_answer_from_question($question, $object, $questionAnswer, $questionGroupId, $showCorrection); ?>
                    <?php if ($question->authorize_answer_photo > 0 || !empty($permissionToAddTask)) : ?>
                        <div class="question__answer-sep"></div>
                        <div class="question__answer-actions">
                            <?php if ($question->authorize_answer_photo > 0) : ?>
                                <?php echo saturne_render_media_block('digiquali', $object->element . '/' . $object->ref . '/answer_photo/' . $question->ref, 'answer_photo_' . $question->id, '', [
                                    'show_photo'       => true,
                                    'show_audio'       => false,
                                    'show_file'        => $object->element === 'control',
                                    'file_sub_dir'     => ($objectLine->id > 0 ? 'controldet/' . dol_sanitizeFileName($objectLine->ref) : ''),
                                    'file_upload_data' => ['fk_control' => $object->id, 'fk_question' => $question->id],
                                    'show_upload'      => $object->status == 0,
                                ]); ?>
                            <?php endif; ?>
                            <?php if (!empty($object->project) && !empty($permissionToAddTask)) : ?>
                                <div class="wpeo-button button-square-50 add-action modal-open">
                                    <input type="hidden" class="modal-options" data-modal-to-open="answer_task_add" data-from-id="<?php echo $objectLine->id ?>" data-from-type="<?php echo $objectLine->element ?>"/>
                                    <i class="fas fa-list"></i><i class="fas fa-plus-circle button-add"></i>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($object->project) && !empty($permissionToAddTask)) :
                                print '<div class="wpeo-button button-square-50 wpeo-tooltip-event" aria-label="' . $langs->transnoentities('AddProject') . '" id="task-disable" style="background-color: #ececec; border-color: #ececec; color: rgba(0, 0, 0, 0.4) !important;">';
                                print '    <input type="hidden" class="modal-options" data-modal-to-open="answer_task_add" data-from-id="' . $objectLine->id . '" data-from-type="' . $objectLine->element . '"/>';
                                print '    <i class="fas fa-list"></i><i class="fas fa-plus-circle button-add"></i>';
                                print '</div>';
                            endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($question->enter_comment > 0) : ?>
                <div class="question__footer">
                    <label class="question__footer-comment">
                        <i class="far fa-comment-dots question-comment-icon"></i>
                        <textarea name="comment<?php echo $question->id ?>" class="question-textarea question-comment" placeholder="<?php echo $langs->transnoentities('WriteComment'); ?>" <?php echo ($object->status == $object::STATUS_VALIDATED ? 'disabled' : ''); ?>><?php echo $comment; ?></textarea>
                    </label>
                </div>
            <?php endif; ?>
            <?php
            if (!empty($permissionToReadTask)) :
                require __DIR__ . '/answers/answers_task_view.tpl.php';
            endif; ?>
        </div>
    </div>
<?php endif;
