<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/control/control_lock_steps.tpl.php
 * \ingroup digiquali
 * \brief   Template page for the notice of a validated control : what validated means, the steps left
 *          before the lock and what the lock does
 */

/**
 * Rendered by view/control/control_card.php (inherits its scope).
 * Expects: $langs, $lockBlockers (Control::getLockBlockers())
 */

// Steps of a validated control, in the order of the buttons of the card. An outdated equipment has its own notice
$lockSteps = ['ControlLockStepVerdict', 'ControlLockStepSign'];
?>
<div class="wpeo-notice notice-info">
    <div class="notice-content">
        <div class="notice-title"><?php echo $langs->trans('ControlValidatedNotLocked'); ?></div>
        <div class="notice-subtitle">
            <div><?php echo $langs->trans('ControlValidatedNotLockedHelp'); ?></div>
            <?php foreach ($lockSteps as $lockStep) : ?>
                <div>
                    <?php if (in_array($lockStep, $lockBlockers, true)) : ?>
                        <i class="far fa-circle pictofixedwidth opacitymedium"></i>
                    <?php else : ?>
                        <i class="fas fa-check-circle pictofixedwidth font-status4"></i>
                    <?php endif; ?>
                    <?php echo $langs->trans($lockStep); ?>
                </div>
            <?php endforeach; ?>
            <div><i class="fas fa-lock pictofixedwidth opacitymedium"></i><?php echo $langs->trans('ControlLockStepLock'); ?></div>
        </div>
    </div>
</div>
