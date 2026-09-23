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
 * \file    core/tpl/frontend/pwa_answer_object_header.tpl.php
 * \ingroup digiquali
 * \brief   Object header of the PWA answer screen (intro screen of the wizard).
 *
 * The following vars must be defined:
 * Global  : $langs
 * Objects : $object, $sheet
 */

$pwaAnswerFields = digiquali_pwa_get_card_fields($object);
?>
<div class="public-answer-header">
    <div class="public-answer-header__object">
        <div class="public-answer-header__thumbnail public-answer-header__thumbnail--placeholder">
            <?php print img_picto('', $object->picto); ?>
        </div>
        <div class="public-answer-header__info">
            <div class="public-answer-header__type"><?php echo $langs->transnoentities(ucfirst($object->element)); ?></div>
            <div class="public-answer-header__name"><?php echo dol_escape_htmltag($object->ref); ?></div>
            <div class="public-answer-header__type"><?php echo $langs->transnoentities('Sheet'); ?></div>
            <div class="public-answer-header__label"><?php echo dol_escape_htmltag($sheet->label ?: $sheet->ref); ?></div>
        </div>
    </div>
    <?php if (!empty($pwaAnswerFields)) { ?>
        <div class="public-answer-header__control">
            <?php foreach (array_slice($pwaAnswerFields, 0, 4) as $pwaAnswerField) { ?>
                <div class="public-answer-header__type"><?php echo $pwaAnswerField['label']; ?></div>
                <div class="public-answer-header__label"><?php echo $pwaAnswerField['html']; ?></div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
