<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \file    public/control/public_control_history.php
 * \ingroup digiquali
 * \brief   Public page to view control history
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOLOGIN')) {     // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) { // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {   // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Better performance by disabling some features not used in this page
if (!defined('DISABLE_CKEDITOR')) {
    define('DISABLE_CKEDITOR', 1);
}
if (!defined('DISABLE_JQUERY_TABLEDND')) {
    define('DISABLE_JQUERY_TABLEDND', 1);
}
if (!defined('DISABLE_JS_GRAPH')) {
    define('DISABLE_JS_GRAPH', 1);
}
if (!defined('DISABLE_MULTISELECT')) {
    define('DISABLE_MULTISELECT', 1);
}

// Load DigiQuali environment
if (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} elseif (file_exists('../../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../../lib/digiquali_control.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Better performance by disabling some features not used in this page provide by conf
if (isModEnabled('multicompany')) {
    unset($conf->modules_parts['css']['multicompany']); // To avoid loading multicompany CSS
    unset($conf->modules_parts['js']['multicompany']);  // To avoid loading multicompany JS
}

if (isModEnabled('saturne')) {
    unset($conf->modules_parts['css']['saturne']); // To avoid loading saturne CSS
    unset($conf->modules_parts['js']['saturne']);  // To avoid loading saturne JS
}

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$trackId = GETPOST('track_id', 'alpha');
$action  = GETPOST('action', 'aZ09');
$entity  = GETPOST('entity');
$route   = GETPOSTISSET('route') ? GETPOST('route') : 'linkedObjectAndControl';

// Initialize technical objects
$object = new Control($db);
$sheet  = new Sheet($db);
$user   = new User($db);

$hookmanager->initHooks(['publiccontrol', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

// Load user
if (!isset($_SESSION['dol_login'])) {
    $user->loadDefaultValues();
} else {
    $user->fetch('', $_SESSION['dol_login'], '', 1);
    $user->getrights();
}

// Load entity
if (!isModEnabled('multicompany')) {
    $entity = $conf->entity;
}
$conf->setEntityValues($db, $entity);

// Load linkable elements
$linkableElements = saturne_get_objects_metadata();

// Load object
$objectDataJson = base64_decode($trackId);
$objectData     = json_decode($objectDataJson);
$objectType     = $objectData->type;
$objectId       = $objectData->id;

$linkedObject = new $objectType($db);

$linkedObject->fetch($objectId);

$linkableElement = $linkableElements[$linkedObject->element];
$linkedObject->fetchObjectLinked($objectId, $linkedObject->element, '', 'digiquali_control');

// Routes to display different views
$routes = [
    'linkedObjectAndControl' => '/../../core/tpl/frontend/linked_object_and_control_frontend_view.tpl.php',
    'controlList'            => '/../../core/tpl/frontend/control_item_frontend_view.tpl.php',
    'controlDocumentation'   => '/../../core/tpl/frontend/control_documentation_frontend_view.tpl.php'
];
$defaultRoute = 'linkedObjectAndControl';
$externals    = [];

/*
 * Actions
 */

$parameters = ['trackId' => $trackId, 'entity' => $entity, 'linkedObject' => $linkedObject, 'linkableElements' => $linkableElements, 'linkableElement' => $linkableElement];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $project may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

/*
 * View
 */

$title = $langs->transnoentities('PublicControl');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

$controlInfoArray = get_control_infos($linkedObject);
$showNavigation   = getDolGlobalInt('DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY');

saturne_header(0, '', $title, '', '', 0, 0, [], [], '', 'template-pwa page-public-control' . (!$showNavigation ? ' page-public-control--no-nav' : ''));

$pwaHeaderTitle = $langs->transnoentities('PublicControlTracking');
require_once __DIR__ . '/../../core/tpl/frontend/digiquali_public_pwa_header.tpl.php'; ?>

<div id="publicControlHistory">
    <?php
    // The navigation runs the digiqualiPublicControlTab hook, which registers the views of other modules: it
    // must come before the view is picked
    if ($showNavigation) {
        require __DIR__ . '/../../core/tpl/frontend/public_control_nav.tpl.php';
    } ?>

    <div class="public-control-content">
        <?php
        if (!isset($routes[$route])) {
            $route = $defaultRoute;
        }
        if (in_array($route, $externals)) {
            // A view of another module renders without its own container: it gets the one of this page
            $fromExternModule = true;
            print '<div class="pwa-container public-control-screen"><div class="public-control-sheet">';
            require_once __DIR__ . $routes[$route];
            print '</div></div>';
        } else {
            require_once __DIR__ . $routes[$route];
        } ?>
    </div>
</div><?php

llxFooter('', 'public');
$db->close();
