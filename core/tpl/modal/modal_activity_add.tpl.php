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
 * \file    core/tpl/modal/modal_activity_add.tpl.php
 * \ingroup digiquali
 * \brief   Template page for modal activity add
 */

/**
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $activity
 */ ?>

<div class="wpeo-modal modal-activity-add" id="activity_add">
    <div class="modal-container wpeo-modal-event">
        <!-- Modal-Header -->
        <div class="modal-header">
            <h2 class="modal-title"><?php echo $langs->trans('ActivityCreate') . ' ' . $activity->getNextNumRef(); ?></h2>
            <div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
        </div>
        <!-- Modal-Content -->
        <div class="modal-content">
            <div class="">
                <label>
                    <span class="title"><?php echo $langs->trans('Label'); ?></span>
                    <input type="text" id="myTextareadsf" name="myTextarea">
                </label>
            </div>
        </div>
        <!-- Modal-Footer -->
        <div class="modal-footer">
            <div class="wpeo-button button-disable modal-close" id="activity_create">
                <i class="fas fa-plus pictofixedwidth"></i><?php echo $langs->trans('Add'); ?>
            </div>
        </div>
    </div>
</div>
