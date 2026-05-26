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
 * \file    core/tpl/frontend/control_answer_public_header.tpl.php
 * \ingroup digiquali
 * \brief   Template for linked object header on public answer interface
 */

/**
 * Variables requises :
 * $object           - Control (avec linkedObjects chargés)
 * $linkedObject     - CommonObject lié (Product, ProductLot, Project…)
 * $linkableElements - tableau retourné par saturne_get_objects_metadata()
 * $sheet            - Sheet (déjà fetchée dans public_answer.php)
 * $langs            - Traductions
 */

$linkedObjectInfoArray = get_linked_object_infos($linkedObject, $linkableElements); ?>

<div class="public-answer-header">
    <div class="public-answer-header__object">
        <?php if (!empty($linkedObjectInfoArray['images']) && strpos($linkedObjectInfoArray['images'], 'nophoto') === false) : ?>
            <div class="public-answer-header__thumbnail"><?php echo $linkedObjectInfoArray['images']; ?></div>
        <?php else : ?>
            <div class="public-answer-header__thumbnail public-answer-header__thumbnail--placeholder">
                <?php echo img_picto('', $linkableElements[$linkedObject->element]['picto'] ?? 'generic'); ?>
            </div>
        <?php endif; ?>
        <div class="public-answer-header__info">
            <div class="public-answer-header__type"><?php echo $linkedObjectInfoArray['linkedObject']['title']; ?></div>
            <div class="public-answer-header__name"><?php echo $linkedObjectInfoArray['linkedObject']['name_field']; ?></div>
            <?php if (!empty($linkedObjectInfoArray['linkedObject']['label'])) : ?>
                <div class="public-answer-header__label"><?php echo $linkedObjectInfoArray['linkedObject']['label']; ?></div>
            <?php endif; ?>
            <?php if (!empty($linkedObjectInfoArray['linkedObject']['description'])) : ?>
                <div class="public-answer-header__description"><?php echo $linkedObjectInfoArray['linkedObject']['description']; ?></div>
            <?php endif; ?>
            <?php if (!empty($linkedObjectInfoArray['parentLinkedObject']['title'])) : ?>
                <div class="public-answer-header__type"><?php echo $linkedObjectInfoArray['parentLinkedObject']['title']; ?></div>
                <div class="public-answer-header__name"><?php echo $linkedObjectInfoArray['parentLinkedObject']['name_field']; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="public-answer-header__control">
        <div class="public-answer-header__type"><?php echo $langs->transnoentities('Control'); ?></div>
        <div class="public-answer-header__name"><?php echo $object->ref; ?></div>
        <div class="public-answer-header__type"><?php echo $langs->transnoentities('Sheet'); ?></div>
        <div class="public-answer-header__name"><?php echo $sheet->label ?: $sheet->ref; ?></div>
    </div>
</div>
