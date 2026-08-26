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
 * \file    ajax/pwa_search.php
 * \ingroup digiquali
 * \brief   AJAX endpoint for the PWA live search: returns the rendered list cards for a search.
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

require_once __DIR__ . '/../lib/digiquali_pwa.lib.php';

global $db, $langs, $user;

saturne_load_langs();

$objectType = GETPOST('object_type', 'aZ09');
$search     = GETPOST('search', 'alphanohtml');

$types = digiquali_pwa_get_object_types();
if (!isset($types[$objectType])) {
    http_response_code(400);
    exit;
}
$listCfg = $types[$objectType];

if (empty($user->rights->digiquali->{$listCfg['right']}->read)) {
    http_response_code(403);
    exit;
}

// Load the requested object class so saturne_fetch_all_object_type() can instantiate it.
require_once __DIR__ . '/../class/' . strtolower($listCfg['class']) . '.class.php';

top_httphead('text/html');

print digiquali_pwa_render_list_items($listCfg['class'], $listCfg['card'], $search, $listCfg['icon'], $listCfg['card_params']);
