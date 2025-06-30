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
            <div class="modal-section">
                <label class="modal-label">Photo</label>
                <div class="modal-photo-upload">
                    <button class="icon-button"><i class="fas fa-camera"></i></button>
                    <button class="icon-button"><i class="fas fa-folder"></i></button>
                    <button class="icon-button"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <div class="modal-section">
                <label class="modal-label" for="risk_tags">Tags</label>
                <input type="text" id="risk_tags" name="risk_tags" value="Nom du tag">
            </div>

            <div class="modal-section">
                <label class="modal-label" for="risk_description">Description</label>
                <textarea id="risk_description" name="risk_description" rows="4"></textarea>
            </div>

            <div class="modal-section modal-row">
                <label class="modal-label">Gravité</label>
                <div class="input-group">
                    <div class="mood-icons">
                        <button class="mood-button"><i class="far fa-smile"></i></button>
                        <button class="mood-button"><i class="far fa-meh"></i></button>
                        <button class="mood-button active"><i class="far fa-frown"></i></button>
                        <button class="mood-button"><i class="fas fa-skull"></i></button>
                    </div>
                    <input type="number" value="80" class="small-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-section modal-row">
                <label class="modal-label">Fréquence</label>
                <div class="input-group">
                    <div class="frequency-buttons">
                        <button class="frequency-button">1D</button>
                        <button class="frequency-button active">1W</button>
                        <button class="frequency-button">1M</button>
                        <button class="frequency-button">1Y</button>
                    </div>
                    <input type="number" value="80" class="small-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-section modal-row">
                <label class="modal-label">Maîtrise</label>
                <div class="input-group">
                    <span class="range-value">0</span>
                    <input type="range" min="0" max="100" value="80" class="slider">
                    <span class="range-value">100</span>
                    <input type="number" value="80" class="small-input">
                    <span class="unit">%</span>
                </div>
            </div>

            <div class="modal-summary-boxes">
                <div class="summary-box">
                    <span class="summary-title">Risque résiduel</span>
                    <span class="summary-subtitle">Risque x maitrise</span>
                    <span class="summary-percentage green">80%</span>
                </div>
                <div class="summary-box">
                    <span class="summary-title">Risque</span>
                    <span class="summary-subtitle">Gravité x fréquence</span>
                    <span class="summary-percentage green">80%</span>
                </div>
            </div>

            <div class="modal-last-added-risk">
                <h3>Dernier risque ajouté</h3>
                <div class="risk-item">
                    <div class="risk-thumbnail">
                        <img src="https://via.placeholder.com/60x60" alt="Risk thumbnail">
                    </div>
                    <div class="risk-details">
                        <div class="risk-header">
                            <span class="risk-code">RA10</span>
                            <span class="risk-tag">Nom du tag (10)</span>
                            <span class="risk-date"><i class="far fa-calendar-alt"></i> 26/02/2025</span>
                            <span class="risk-mastery"><i class="fas fa-shield-alt"></i> Maîtrise : 20%</span>
                        </div>
                        <div class="risk-residual">
                            <i class="fas fa-exclamation-triangle"></i> Risque résiduel : 16
                        </div>
                        <div class="risk-description">
                            Manque de compétence
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
