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
 * \file    core/tpl/frontend/control_documentation_frontend_view.tpl.php
 * \ingroup digiquali
 * \brief   Documentation view of the public control page: the shared files and links of the object.
 */

/**
 * The following vars must be defined:
 * Variable : $linkedObject, $linkableElements
 */

$linkedObjectInfoArray = get_linked_object_infos($linkedObject, $linkableElements);

$documentIcons = [
    'pdf'  => 'fa-file-pdf',
    'jpg'  => 'fa-file-image',
    'jpeg' => 'fa-file-image',
    'png'  => 'fa-file-image',
    'gif'  => 'fa-file-image',
    'webp' => 'fa-file-image',
    'doc'  => 'fa-file-word',
    'docx' => 'fa-file-word',
    'odt'  => 'fa-file-word',
    'xls'  => 'fa-file-excel',
    'xlsx' => 'fa-file-excel',
    'ods'  => 'fa-file-excel',
    'csv'  => 'fa-file-excel'
]; ?>

<div class="pwa-container public-control-screen">
    <h2 class="pwa-section-title"><i class="fas fa-folder-open"></i> <?php echo $langs->transnoentities('Documentation'); ?></h2>

    <?php if (empty($linkedObjectInfoArray['files']) && empty($linkedObjectInfoArray['links'])) : ?>
        <div class="pwa-empty">
            <i class="fas fa-folder-open"></i>
            <p><?php echo $langs->transnoentities('NoDocumentation'); ?></p>
        </div>
    <?php else : ?>
        <div class="pwa-list">
            <?php foreach ($linkedObjectInfoArray['files'] as $file) :
                $documentIcon = $documentIcons[strtolower(pathinfo($file->filename, PATHINFO_EXTENSION))] ?? 'fa-file-alt'; ?>
                <div class="pwa-card public-control-document">
                    <div class="public-control-document__icon"><i class="fas <?php echo $documentIcon; ?>"></i></div>
                    <div class="pwa-card-body">
                        <span class="pwa-card-title"><?php echo dol_escape_htmltag($file->filename); ?></span>
                        <span class="public-control-document__source"><?php echo $file->name_field; ?></span>
                    </div>
                    <a class="public-control-card__link" href="<?php echo DOL_URL_ROOT . '/document.php?hashp=' . $file->share; ?>" target="_blank" aria-label="<?php echo dol_escape_htmltag($langs->transnoentities('Download') . ' ' . $file->filename); ?>">
                        <i class="fas fa-download pwa-card-arrow"></i>
                    </a>
                </div>
            <?php endforeach;

            foreach ($linkedObjectInfoArray['links'] as $link) : ?>
                <div class="pwa-card public-control-document">
                    <div class="public-control-document__icon public-control-document__icon--link"><i class="fas fa-link"></i></div>
                    <div class="pwa-card-body">
                        <span class="pwa-card-title"><?php echo dol_escape_htmltag($langs->transnoentities($link->label)); ?></span>
                        <span class="public-control-document__source"><?php echo $link->name_field; ?></span>
                    </div>
                    <a class="public-control-card__link" href="<?php echo dol_escape_htmltag($link->url); ?>" target="_blank" rel="noopener" aria-label="<?php echo dol_escape_htmltag($langs->transnoentities($link->label)); ?>">
                        <i class="fas fa-external-link-alt pwa-card-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
