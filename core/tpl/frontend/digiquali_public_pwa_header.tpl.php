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
 * \file    core/tpl/frontend/digiquali_public_pwa_header.tpl.php
 * \ingroup digiquali
 * \brief   Fixed top header of the DigiQuali public pages, the PWA header without its user part.
 *
 * Rendered by the public pages (inherits their scope). Expects: $pwaHeaderTitle, optional $pwaHeaderIcon.
 */

global $conf, $db, $mysoc;

if (empty($mysoc)) {
    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
    $mysoc = new Societe($db);
    $mysoc->setMysoc($conf);
}

$logoFile = '';
if (!empty($mysoc->logo_squarred)) {
    $logoFile = 'logos/' . $mysoc->logo_squarred;
} elseif (!empty($mysoc->logo)) {
    $logoFile = 'logos/' . $mysoc->logo;
} ?>

<header id="id-top" class="pwa-header pwa-header--public">
    <span class="pwa-header-logo">
        <?php if (!empty($logoFile)) : ?>
            <img src="<?php echo DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode($logoFile); ?>" alt="<?php echo dol_escape_htmltag($mysoc->name); ?>">
        <?php else : ?>
            <i class="fas fa-clipboard-check pwa-header-logo-fallback"></i>
        <?php endif; ?>
    </span>
    <div class="pwa-header-center">
        <div class="pwa-header-indicator">
            <i class="fas <?php echo $pwaHeaderIcon ?? 'fa-clipboard-check'; ?>"></i> <?php echo dol_escape_htmltag($pwaHeaderTitle); ?>
        </div>
    </div>
</header>
