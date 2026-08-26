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
 * \file    core/tpl/frontend/digiquali_pwa_list.tpl.php
 * \ingroup digiquali
 * \brief   Shared mobile list renderer for the DigiQuali PWA object pages.
 *
 * Expected variables (set by the parent page):
 * - string $pwaListType   Object type slug (sheets|controls|surveys).
 * - string $pwaListSearch  Current search string (already retrieved via GETPOST).
 */

require_once __DIR__ . '/../../../lib/digiquali_pwa.lib.php';

global $langs;

$selfUrl = $_SERVER['PHP_SELF'];
$types   = digiquali_pwa_get_object_types();
$listCfg = $types[$pwaListType] ?? null;
$ajaxUrl = dol_buildpath('/custom/digiquali/ajax/pwa_search.php', 1);
?>
<div class="pwa-container">
    <form class="pwa-search" method="GET" action="<?php echo dol_escape_htmltag($selfUrl); ?>">
        <input type="hidden" name="source" value="pwa">
        <i class="fas fa-search pwa-search-icon"></i>
        <input type="search" name="search" class="pwa-search-input"
               data-pwa-search
               data-pwa-object="<?php echo dol_escape_htmltag($pwaListType); ?>"
               data-pwa-url="<?php echo dol_escape_htmltag($ajaxUrl); ?>"
               placeholder="<?php echo dol_escape_htmltag($langs->trans('Search') . '...'); ?>"
               value="<?php echo dol_escape_htmltag($pwaListSearch); ?>" autocomplete="off">
        <?php if (!empty($pwaListSearch)) { ?>
        <a href="<?php echo dol_escape_htmltag($selfUrl); ?>?source=pwa" class="pwa-search-clear" aria-label="<?php echo dol_escape_htmltag($langs->trans('Delete')); ?>"><i class="fas fa-times"></i></a>
        <?php } ?>
    </form>

    <div class="pwa-list" data-pwa-list>
        <?php
        if ($listCfg) {
            echo digiquali_pwa_render_list_items($listCfg['class'], $listCfg['card'], $pwaListSearch, $listCfg['icon'], $listCfg['card_params']);
        }
        ?>
    </div>
</div>
