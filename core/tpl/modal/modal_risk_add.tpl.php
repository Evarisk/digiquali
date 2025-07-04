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
 * \file    core/tpl/modal/modal_risk_add.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal risk add
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $risk
 */ ?>

<div class="wpeo-modal modal-risk-add" id="risk_add">
    <div class="modal-container wpeo-modal-event">
        <div class="modal-header">
            <h2 class="modal-title">Ajout d'un risque</h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <div class="modal-content">
            <div class="modal-section wpeo-grid grid-2">
                <label class="modal-label">Photo</label>
                <div class="modal-photo-upload">
                    <button class="wpeo-button button-square-40 icon-button"><i class="button-icon fas fa-camera"></i><span class="button-add animated fas fa-plus-circle"></span></button>
                    <button class="wpeo-button button-square-40 icon icon-button"><i class="fas fa-folder"></i><span class="button-add animated fas fa-plus-circle"></span></button>
                    <button class="wpeo-button button-square-40 icon icon-button"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <div class="modal-section wpeo-grid grid-2">
                <label class="modal-label" for="risk_tags">Tags</label>
                <div>
                    <input type="text" id="risk_tags" name="risk_tags" value="Nom du tag">
                </div>
            </div>

            <div class="modal-section wpeo-grid grid-2">
                <label class="modal-label" for="description"><?php echo $langs->trans('Description'); ?></label>
                <div>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label"><?php echo $langs->trans('Gravity'); ?></label>
                <div class="input-group">
                    <div class="gravity-buttons">
                        <button class="gravity-button button-grey selected" data-gravity-value="25"><i class="button-icon fas fa-smile"></i></button>
                        <button class="gravity-button button-yellow" data-gravity-value="50"><i class="button-icon fas fa-meh"></i></button>
                        <button class="gravity-button button-red" data-gravity-value="75"><i class="button-icon fas fa-frown"></i></button>
                        <button class="gravity-button button-black" data-gravity-value="100"><i class="button-icon fas fa-skull"></i></button>
                    </div>
                    <input type="number" min="0" max="100" value="25" class="small-input" id="gravity-percentage-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label"><?php echo $langs->trans('Frequency'); ?></label>
                <div class="input-group">
                    <div class="frequency-buttons">
                        <button class="frequency-button button-grey selected" data-frequency-value="25">1D</button>
                        <button class="frequency-button button-yellow" data-frequency-value="50">1W</button>
                        <button class="frequency-button button-red" data-frequency-value="75">1M</button>
                        <button class="frequency-button button-black" data-frequency-value="100">1Y</button>
                    </div>
                    <input type="number" min="0" max="100" value="25" class="small-input" id="frequency-percentage-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label"><?php echo $langs->trans('ControlPercentage'); ?></label>
                <div class="input-group">
                    <span class="range-value">0</span>
                    <input type="range" min="0" max="100" value="0" id="control-slider">
                    <span class="range-value">100</span>
                    <input type="number" value="0" class="small-input" id="control-percentage-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-summary-boxes wpeo-gridlayout grid-2">
                <div class="summary-box">
                    <div class="summary-box-content">
                        <span class="summary-title"><?php echo $langs->trans('Risk'); ?></span>
                        <span class="summary-subtitle"><?php echo $langs->trans('RiskCalculation'); ?></span>
                    </div>
                    <span class="summary-percentage grey" id="risk-percentage-value">6.25%</span>
                </div>
                <div class="summary-box">
                    <div class="summary-box-content">
                        <span class="summary-title"><?php echo $langs->trans('ResidualRisk'); ?></span>
                        <span class="summary-subtitle"><?php echo $langs->trans('ResidualRiskCalculation'); ?></span>
                    </div>
                    <span class="summary-percentage grey" id="residual-risk-percentage-value">0%</span>
                </div>
            </div>

            <div class="modal-last-added-risk">
                <h3>Dernier risque ajouté</h3>
                <div class="risk-list__container">
                    <div class="risk__content">
                        <div class="risk-thumbnail">
                            <img src="https://via.placeholder.com/60x60" alt="Risk thumbnail">
                        </div>
                        <div class="risk__content-container">
                            <div class="risk__content-heading">
                                <span class="risk-ref">RA10</span>
                                <span class="risk-tags">Nom du tag (10)</span>
                                <span class="risk-date"><i class="fas fa-calendar-alt"></i> 26/02/2025</span>
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
            <button class="wpeo-button button-disable modal-close" id="risk_create">
                <span class="fas fa-save pictofixedwidth"></span>
                <?php echo $langs->trans('Save'); ?>
            </button>
        </div>
    </div>
</div>
