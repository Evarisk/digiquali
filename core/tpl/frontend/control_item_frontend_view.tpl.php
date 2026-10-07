<?php
/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/frontend/control_item_frontend_view.tpl.php
 * \ingroup digiquali
 * \brief   Control list view of the public control page: the locked controls of the object, most recent first.
 */

/**
 * The following vars must be defined:
 * Variable : $controlInfoArray
 */

$controlVerdictDisplays = [
    'ok' => ['icon' => 'fa-check', 'label' => 'ControlStatusOk'],
    'ko' => ['icon' => 'fa-exclamation', 'label' => 'ControlStatusKo']
]; ?>

<div class="pwa-container public-control-screen">
    <h2 class="pwa-section-title"><i class="fas fa-history"></i> <?php echo $langs->transnoentities('ControlList'); ?></h2>

    <?php if (empty($controlInfoArray['control'])) : ?>
        <div class="pwa-empty">
            <i class="fas fa-clipboard-check"></i>
            <p><?php echo $langs->transnoentities('NoControlYet'); ?></p>
        </div>
    <?php else : ?>
        <div class="pwa-list">
            <?php foreach ($controlInfoArray['control'] as $controlInfo) :
                $controlVerdictDisplay = $controlVerdictDisplays[$controlInfo['verdict']]; ?>
                <div class="pwa-card public-control-card">
                    <div class="public-control-card__thumbnail public-control-card__thumbnail--<?php echo $controlInfo['verdict']; ?>">
                        <?php echo !empty($controlInfo['image']) ? $controlInfo['image'] : '<i class="fas ' . $controlVerdictDisplay['icon'] . '"></i>'; ?>
                    </div>
                    <div class="pwa-card-body">
                        <div class="pwa-card-head">
                            <span class="pwa-card-title"><?php echo dol_escape_htmltag($controlInfo['ref']); ?></span>
                            <span class="pwa-card-status">
                                <span class="public-control-badge public-control-badge--<?php echo $controlInfo['verdict']; ?>"><?php echo $langs->transnoentities($controlVerdictDisplay['label']); ?></span>
                            </span>
                        </div>
                        <div class="pwa-card-fields">
                            <div class="pwa-card-field">
                                <span class="pwa-card-field-label"><?php echo $langs->transnoentities('ControlDate'); ?></span>
                                <span class="pwa-card-field-value"><?php echo dol_print_date($controlInfo['control_date'], 'day'); ?></span>
                            </div>
                            <div class="pwa-card-field">
                                <span class="pwa-card-field-label"><?php echo $langs->transnoentities('Sheet'); ?></span>
                                <span class="pwa-card-field-value"><?php echo dol_escape_htmltag($controlInfo['sheet']); ?></span>
                            </div>
                            <?php if (!empty($controlInfo['controller'])) : ?>
                                <div class="pwa-card-field">
                                    <span class="pwa-card-field-label"><?php echo $langs->transnoentities('Controller'); ?></span>
                                    <span class="pwa-card-field-value"><?php echo dol_escape_htmltag($controlInfo['controller']); ?></span>
                                </div>
                            <?php endif;
                            if (!empty($controlInfo['next_control_date'])) : ?>
                                <div class="pwa-card-field">
                                    <span class="pwa-card-field-label"><?php echo $langs->transnoentities('NextControl'); ?></span>
                                    <span class="pwa-card-field-value"><?php echo dol_print_date($controlInfo['next_control_date'], 'day'); ?></span>
                                </div>
                            <?php endif;
                            if (!empty($controlInfo['project'])) : ?>
                                <div class="pwa-card-field">
                                    <span class="pwa-card-field-label"><?php echo $langs->transnoentities('Project'); ?></span>
                                    <span class="pwa-card-field-value"><?php echo dol_escape_htmltag($controlInfo['project']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($controlInfo['note'])) : ?>
                            <div class="public-control-card__note"><?php echo dol_escape_htmltag($controlInfo['note']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($controlInfo['url'])) : ?>
                        <a class="public-control-card__link" href="<?php echo $controlInfo['url']; ?>" target="_blank" aria-label="<?php echo dol_escape_htmltag($controlInfo['ref']); ?>">
                            <i class="fas fa-chevron-right pwa-card-arrow"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
