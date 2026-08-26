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
 * \file    view/frontend/pwa_home.php
 * \ingroup digiquali
 * \brief   PWA home dashboard (counters + quick access + recent controls).
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
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/survey.class.php';
require_once __DIR__ . '/../../lib/digiquali_pwa.lib.php';

global $conf, $db, $langs, $user;

saturne_load_langs();

$rights = [
    'sheets'   => !empty($user->rights->digiquali->sheet->read),
    'controls' => !empty($user->rights->digiquali->control->read),
    'surveys'  => !empty($user->rights->digiquali->survey->read),
];

if (!$rights['sheets'] && !$rights['controls'] && !$rights['surveys']) {
    accessforbidden();
}

$title    = $langs->trans('Home');
$help_url = 'FR:Module_DigiQuali';
$moreJS   = ['/custom/saturne/js/saturne.min.js', '/custom/digiquali/js/digiquali.min.js'];
$moreCSS  = ['/custom/digiquali/css/digiquali.min.css'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

// Counters (per object, only when the user can read it)
$counts = [
    'sheets'   => $rights['sheets']   ? digiquali_pwa_count('Sheet')   : 0,
    'controls' => $rights['controls'] ? digiquali_pwa_count('Control') : 0,
    'surveys'  => $rights['surveys']  ? digiquali_pwa_count('Survey')  : 0,
];

// Recent controls
$recentControls = [];
if ($rights['controls']) {
    $recentControls = saturne_fetch_all_object_type('Control', 'DESC', 't.date_creation', 5, 0, [], 'AND', true);
    if (!is_array($recentControls)) {
        $recentControls = [];
    }
}

$navItems = digiquali_pwa_nav_get_items();

llxHeader('', $title, $help_url, '', 0, 0, $moreJS, $moreCSS, '', 'template-pwa pwa-home');

$pwaHeaderCenterHtml = '<div class="pwa-header-indicator"><i class="fas fa-home"></i> ' . ucfirst($langs->trans('Home')) . '</div>';
require_once __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_header.tpl.php';

print '<div class="pwa-container pwa-home">';

// Stat / quick-access cards
print '<div class="pwa-stat-grid">';
foreach (['sheets', 'controls', 'surveys'] as $slug) {
    if (!$rights[$slug]) {
        continue;
    }
    $item = $navItems[$slug];
    print '<a href="' . $item['url'] . '" class="pwa-stat-card pwa-stat-card--' . $slug . '">';
    print '<i class="fas ' . $item['icon'] . ' pwa-stat-icon"></i>';
    print '<span class="pwa-stat-value">' . $counts[$slug] . '</span>';
    print '<span class="pwa-stat-label">' . dol_escape_htmltag($item['label']) . '</span>';
    print '</a>';
}
print '</div>';

// Recent controls
if ($rights['controls']) {
    print '<div class="pwa-section">';
    print '<h2 class="pwa-section-title"><i class="fas fa-clock"></i> ' . $langs->trans('LastControls') . '</h2>';
    print '<div class="pwa-list">';
    if (empty($recentControls)) {
        print '<div class="pwa-empty"><i class="fas fa-clipboard-check"></i><p>' . $langs->trans('NoRecordFound') . '</p></div>';
    } else {
        foreach ($recentControls as $control) {
            print digiquali_pwa_render_card($control, '/custom/digiquali/view/frontend/pwa_answer.php', false, 'object_type=control');
        }
    }
    print '</div>';
    print '</div>';
}

print '</div>';

require_once __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
