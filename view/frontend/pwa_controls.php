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
 * \file    view/frontend/pwa_controls.php
 * \ingroup digiquali
 * \brief   PWA mobile list of Controls.
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/digiquali_pwa.lib.php';

global $conf, $db, $langs, $user;

saturne_load_langs();

if (empty($user->rights->digiquali->control->read)) {
    accessforbidden();
}

$title    = $langs->trans('Controls');
$help_url = 'FR:Module_DigiQuali';
$moreJS   = ['/custom/saturne/js/saturne.min.js', '/custom/digiquali/js/digiquali.min.js'];
$moreCSS  = ['/custom/digiquali/css/digiquali.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

$pwaListType   = 'controls';
$pwaListSearch = GETPOST('search', 'alphanohtml');

llxHeader('', $title, $help_url, '', 0, 0, $moreJS, $moreCSS, '', 'template-pwa pwa-controls-list');

$pwaHeaderCenterHtml = '<div class="pwa-header-indicator"><i class="fas fa-clipboard-check"></i> ' . $langs->trans('Controls') . '</div>';
require_once __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_header.tpl.php';

require __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_list.tpl.php';

require_once __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
