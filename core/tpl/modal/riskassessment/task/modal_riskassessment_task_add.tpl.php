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
 * \file    core/tpl/modal/riskassessment/task/modal_riskassessment_task_add.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal risk assessment task add
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $riskAssessmentTask
 */ ?>

<div class="wpeo-modal modal-riskassessment-task modal-riskassessment-task-add" id="riskassessment_task_create">
    <div class="modal-container wpeo-modal-event">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('RiskAssessmentTaskAdd') . ' ' /*$riskAssessmentTask->getNextNumRef() */; ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <div class="modal-content">
            <div class="answer-task-container">
                <div class="answer-task">
                    <label>
                        <span class="title"><?php echo $langs->trans('Label'); ?></span>
                        <input type="text" class="label input-ajax" name="label">
                    </label>
                    <div class="answer-task-date wpeo-gridlayout grid-3">
                        <div>
                            <label>
                                <span class="title"><?php echo $langs->trans('DateStart'); ?></span>
                                <input type="datetime-local" class="input-ajax" name="date_start" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M'); ?>">
                                <label>
                        </div>
                        <div>
                            <label>
                                <span class="title"><?php echo $langs->trans('Deadline'); ?></span>
                                <input type="datetime-local" class="input-ajax" name="date_end">
                                <label>
                        </div>
                        <div>
                            <label>
                                <span class="title"><?php echo $langs->trans('Budget'); ?></span>
                                <input type="number" class="input-ajax" name="budget_amount" min="0">
                                <label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="wpeo-button button-disable modal-close" id="riskassessment_task_add">
                <span class="fas fa-save pictofixedwidth"></span>
                <?php echo $langs->trans('Save'); ?>
            </button>
        </div>
    </div>
</div>
