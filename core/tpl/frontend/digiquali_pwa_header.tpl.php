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
 * \file    core/tpl/frontend/digiquali_pwa_header.tpl.php
 * \ingroup digiquali
 * \brief   Fixed mobile PWA header (company logo + controlled object identity) for public control pages
 */

/**
 * The following vars may be defined by the parent script:
 * Variable : $pwaHeaderCenterHtml (string) Page-specific content shown at the center of the header
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

<header id="id-top" class="pwa-header">
    <span class="company-logo-wrapper">
        <?php if (!empty($logoFile)) {
            $logoUrl = DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode($logoFile);
            print '<img class="company-logo" src="' . $logoUrl . '" alt="' . dol_escape_htmltag($mysoc->name) . '">';
        } ?>
    </span>
    <div class="pwa-header-center">
        <?php if (!empty($pwaHeaderCenterHtml)) {
            print $pwaHeaderCenterHtml;
        } ?>
    </div>
    <img class="pwa-header-module-icon" src="<?php echo dol_buildpath('/custom/digiquali/img/digiquali_color.svg', 1); ?>" alt="DigiQuali">
</header>
