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
 * \file    core/tpl/frontend/linked_object_and_control_frontend_view.tpl.php
 * \ingroup digiquali
 * \brief   Template page for control public view
 */

/**
 * The following vars must be defined:
 * Variable : $linkedObject, $linkableElements
 */

$linkedObjectInfoArray = get_linked_object_infos($linkedObject, $linkableElements);
$controlInfoArray      = get_control_infos($linkedObject); ?>

<div class="public-card__header wpeo-gridlayout grid-2">
    <div class="header-information">
        <div class="information-thumbnail"><?php echo $linkedObjectInfoArray['images']; ?></div>
        <div>
            <?php
            $parameters = ['linkedObjectInfoArray' => $linkedObjectInfoArray, 'linkableElements' => $linkableElements];
            $resHook    = $hookmanager->executeHooks('printPublicControlLinkedObjectIdentity', $parameters, $linkedObject);
            if ($resHook > 0) {
                print $hookmanager->resPrint;
            } else { ?>
                <div class="information-type"><?php echo $linkedObjectInfoArray['linkedObject']['title']; ?></div>
                <div class="information-label size-l"><?php echo $linkedObjectInfoArray['linkedObject']['name_field']; ?></div>
            <?php } ?>
            <?php if (!empty($linkedObjectInfoArray['linkedObject']['qc_frequency'])) : ?>
                <div class="information-label objet-label"><?php echo $linkedObjectInfoArray['linkedObject']['qc_frequency']; ?></div>
            <?php endif;
            if (!empty($linkedObjectInfoArray['parentLinkedObject']['title'])) : ?>
                <div class="information-type"><?php echo $linkedObjectInfoArray['parentLinkedObject']['title']; ?></div>
                <div class="information-label"><?php echo $linkedObjectInfoArray['parentLinkedObject']['name_field']; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="header-objet">
        <div class="objet-container">
            <div class="objet-info">
                <div class="objet-type">
                    <?php echo $controlInfoArray['nextControl']['title']; ?>
                </div>
                <div class="objet-label size-l">
                    <?php echo $controlInfoArray['nextControl']['next_control_date']; ?>
                </div>
                <div class="objet-label" style="color: <?php echo $controlInfoArray['nextControl']['next_control_date_color']; ?>;">
                    <?php echo $controlInfoArray['nextControl']['next_control']; ?>
                </div>
            </div>
            <div class="objet-actions">
                <?php echo $controlInfoArray['nextControl']['create_button']; ?>
                <?php echo $controlInfoArray['nextControl']['verdict']; ?>
            </div>
        </div>
    </div>
</div>
