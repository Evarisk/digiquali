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
 * \file    core/tpl/frontend/pwa_control_create_object.tpl.php
 * \ingroup digiquali
 * \brief   Card of the object a control is being created for, on both screens of the PWA control creation.
 *
 * Rendered by the pwa_control_create_* templates (inherits their scope). Expects: $fixedObject, $fixedObjectType,
 * $objectsMetadata.
 */

$fixedObjectMetadata = $objectsMetadata[$fixedObjectType];
if (method_exists($fixedObject, 'getNomUrl')) {
    $fixedObjectName = $fixedObject->getNomUrl(1, 'nolink');
} else {
    $fixedObjectName = img_picto('', $fixedObjectMetadata['picto'], 'class="pictofixedwidth"') . dol_escape_htmltag($fixedObject->{$fixedObjectMetadata['name_field']} ?? $fixedObject->ref);
} ?>

<div class="pwa-control-create__object">
    <span class="pwa-control-create__icon pwa-control-create__icon--object"><?php echo img_picto('', $fixedObjectMetadata['picto']); ?></span>
    <span class="pwa-card-body">
        <span class="pwa-control-create__label"><?php echo $langs->transnoentities($fixedObjectMetadata['langs']); ?></span>
        <span class="pwa-card-title"><?php echo $fixedObjectName; ?></span>
    </span>
</div>
