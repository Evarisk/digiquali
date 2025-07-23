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
 * \file    core/tpl/modal/modal_riskassessment_add.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal risk assessment add
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $riskAssessment
 */ ?>

<div class="wpeo-modal modal-riskassessment modal-riskassessment-add" id="riskassessment_create">
    <div class="modal-container wpeo-modal-event">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('RiskAssessmentAdd') . ' ' . $riskAssessment->getNextNumRef(); ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <div class="modal-content">
            <div class="modal-section wpeo-grid grid-2">
                <label class="modal-label"><?php echo $langs->trans('Photo'); ?></label>
                <?php echo saturne_show_media_buttons(); ?>
            </div>

            <!-- @todo gestion tags -->
<!--            <div class="modal-section wpeo-grid grid-2">-->
<!--                <label class="modal-label" for="tags">Tags</label>-->
<!--                <div>-->
<!--                    <input type="text" id="tags" name="tags" value="Nom du tag">-->
<!--                </div>-->
<!--            </div>-->

            <!-- @todo gestion wyswigs -->
            <div class="modal-section wpeo-grid grid-2">
                <label class="modal-label" for="comment"><?php echo $langs->trans('Comment'); ?></label>
                <div>
                    <textarea class="comment input-ajax" id="comment" name="comment" rows="4"></textarea>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label" for="gravity-percentage-input"><?php echo $langs->trans('Gravity'); ?></label>
                <div class="input-group">
                    <div class="gravity-buttons">
                        <button class="gravity-button button-grey selected" data-gravity-value="25"><i class="button-icon fas fa-smile"></i></button>
                        <button class="gravity-button button-yellow" data-gravity-value="50"><i class="button-icon fas fa-meh"></i></button>
                        <button class="gravity-button button-red" data-gravity-value="75"><i class="button-icon fas fa-frown"></i></button>
                        <button class="gravity-button button-black" data-gravity-value="100"><i class="button-icon fas fa-skull"></i></button>
                    </div>
                    <input type="number" class="small-input gravity-percentage-input input-ajax" id="gravity-percentage-input" name="gravity_percentage" min="0" max="100" value="25">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label" for="frequency-percentage-input"><?php echo $langs->trans('Frequency'); ?></label>
                <div class="input-group">
                    <div class="frequency-buttons">
                        <button class="frequency-button button-grey selected" data-frequency-value="25"><?php echo $langs->trans('1D'); ?></button>
                        <button class="frequency-button button-yellow" data-frequency-value="50"><?php echo $langs->trans('1W'); ?></button>
                        <button class="frequency-button button-red" data-frequency-value="75"><?php echo $langs->trans('1M'); ?></button>
                        <button class="frequency-button button-black" data-frequency-value="100"><?php echo $langs->trans('1Y'); ?></button>
                    </div>
                    <input type="number" class="small-input frequency-percentage-input input-ajax" id="frequency-percentage-input" name="frequency_percentage" min="0" max="100" value="25">
                    <span class="unit">%</span>
                </div>

            </div>
            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label" for="control-percentage-input"><?php echo $langs->trans('ControlPercentage'); ?></label>
                <div class="input-group">
                    <span class="range-value">0</span>
                    <input type="range" class="control-slider" min="0" max="100" value="0">
                    <span class="range-value">100</span>
                    <input type="number" class="small-input control-percentage-input input-ajax" id="control-percentage-input" name="control_percentage" value="0">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-summary-boxes wpeo-gridlayout grid-2">
                <div class="summary-box">
                    <div class="summary-box-content">
                        <span class="summary-title"><?php echo $langs->trans('Risk'); ?></span>
                        <span class="summary-subtitle"><?php echo $langs->trans('RiskCalculation'); ?></span>
                    </div>
                    <span class="summary-percentage grey risk-percentage-value">6.25%</span>
                </div>
                <div class="summary-box">
                    <div class="summary-box-content">
                        <span class="summary-title"><?php echo $langs->trans('ResidualRisk'); ?></span>
                        <span class="summary-subtitle"><?php echo $langs->trans('ResidualRiskCalculation'); ?></span>
                    </div>
                    <span class="summary-percentage grey residual-risk-percentage-value">0%</span>
                </div>
            </div>

            <div class="modal-last-added-riskassessment">
                <h3>Dernier risque ajouté</h3>
                <div class="riskassessment-list__container">
                    <div class="riskassessment__content">
                        <div class="riskassessment-thumbnail">
                            <img src="" alt="Risk thumbnail">
                        </div>
                        <div class="riskassessment__content-container">
                            <div class="riskassessment__content-heading">
                                <span class="ref">RA10</span>
                                <span class="tags">Nom du tag (10)</span>
                                <span class="date"><i class="fas fa-calendar-alt"></i> 26/02/2025</span>
                                <span class="risk-mastery"><i class="fas fa-shield-alt"></i> Maîtrise : 20%</span>
                                <span class="risk-residual"><i class="fas fa-exclamation-triangle"></i> Risque résiduel : 16</span>
                            </div>
                            <div class="risk__content-body">
                                <div class="risk-description">
                                    Manque de compétence
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="wpeo-button button-disable modal-close" id="riskassessment_add">
                <span class="fas fa-save pictofixedwidth"></span>
                <?php echo $langs->trans('Save'); ?>
            </button>
        </div>
    </div>
</div>
