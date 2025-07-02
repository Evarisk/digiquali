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
                <label class="modal-label" for="risk_description">Description</label>
                <div>
                    <textarea id="risk_description" name="risk_description" rows="4"></textarea>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label">Gravité</label>
                <div class="input-group">
                    <div class="mood-icons">
                        <button class="mood-button button-grey"><i class="button-icon fas fa-smile"></i></button>
                        <button class="mood-button button-yellow"><i class="button-icon fas fa-meh"></i></button>
                        <button class="mood-button button-red active"><i class="button-icon fas fa-frown"></i></button>
                        <button class="mood-button button-black"><i class="button-icon fas fa-skull"></i></button>
                    </div>
                    <input type="number" value="80" class="small-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label">Fréquence</label>
                <div class="input-group">
                    <div class="frequency-buttons">
                        <button class="frequency-button button-grey">1D</button>
                        <button class="frequency-button button-yellow active">1W</button>
                        <button class="frequency-button button-red">1M</button>
                        <button class="frequency-button button-black">1Y</button>
                    </div>
                    <input type="number" value="80" class="small-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-section modal-row wpeo-grid grid-2">
                <label class="modal-label">Maîtrise</label>
                <div class="input-group">
                    <span class="range-value">0</span>
                    <input type="range" min="0" max="100" value="80" class="slider">
                    <span class="range-value">100</span>
                    <input type="number" value="80" class="small-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-summary-boxes wpeo-gridlayout grid-2">
                <div class="summary-box">
                    <div class="summary-box-content">
                        <span class="summary-title">Risque résiduel</span>
                        <span class="summary-subtitle">Risque x maitrise</span>
                    </div>
                    <span class="summary-percentage green">80%</span>
                </div>
                <div class="summary-box">
                    <div class="summary-box-content">
                        <span class="summary-title">Risque</span>
                        <span class="summary-subtitle">Gravité x fréquence</span>
                    </div>
                    <span class="summary-percentage green">80%</span>
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
            <button class="wpeo-button modal-close" id="risk_create">
                <span class="fas fa-save pictofixedwidth"></span>
                <?php echo $langs->trans('Save'); ?>
            </button>
        </div>
    </div>
</div>
