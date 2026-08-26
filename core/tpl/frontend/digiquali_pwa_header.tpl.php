<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/frontend/digiquali_pwa_header.tpl.php
 * \ingroup digiquali
 * \brief   Homogeneous fixed top header for all DigiQuali PWA pages.
 *
 * Optional $pwaHeaderCenterHtml may be defined by the parent page to inject a
 * page-specific indicator in the middle of the header.
 */

global $conf, $db, $langs, $mysoc, $user;

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
}

$homeUrl    = dol_buildpath('/custom/digiquali/view/frontend/pwa_home.php?source=pwa', 1);
$profileUrl = dol_buildpath('/user/virtualcard.php', 1) . '?id=' . $user->id;
?>
<header id="id-top" class="pwa-header">
    <?php
    // An installed PWA has no browser back button: a detail page must carry its own way out,
    // so $pwaHeaderBackUrl replaces the logo with a back arrow.
    if (!empty($pwaHeaderBackUrl)) { ?>
        <a href="<?php echo dol_escape_htmltag($pwaHeaderBackUrl); ?>" class="pwa-header-back" aria-label="<?php echo dol_escape_htmltag($langs->trans('BackToList')); ?>">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php } else { ?>
        <a href="<?php echo $homeUrl; ?>" class="pwa-header-logo">
            <?php
            if (!empty($logoFile)) {
                $logoUrl = DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode($logoFile);
                print '<img src="' . $logoUrl . '" alt="' . dol_escape_htmltag($mysoc->name) . '">';
            } else {
                print '<i class="fas fa-clipboard-check pwa-header-logo-fallback"></i>';
            }
            ?>
        </a>
    <?php } ?>

    <div class="pwa-header-center">
        <?php
        if (!empty($pwaHeaderCenterHtml)) {
            print $pwaHeaderCenterHtml;
        }
        ?>
    </div>

    <a href="<?php echo dol_escape_htmltag($profileUrl); ?>" target="_blank" class="pwa-header-user" title="<?php echo dol_escape_htmltag($user->getFullName($langs)); ?>">
        <?php
        $formObj = new Form($db);
        print $formObj->showphoto('userphoto', $user, 0, 0, 0, 'pwa-header-avatar', 'small', 0);
        ?>
    </a>
</header>
