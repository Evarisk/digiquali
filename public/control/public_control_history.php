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

$linkableElement = $linkableElements[$linkedObject->element] ?? [];
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

saturne_header(0,'', $title, '', '', 0, 0, [], [], '', 'template-pwa page-public-card');

// Fixed PWA header (company logo + controlled object identity)
$headerType = $langs->transnoentities(!empty($linkableElement['langs']) ? $linkableElement['langs'] : dol_ucfirst($linkedObject->element));

// Build a readable name from the element's name_field(s) - avoids showing raw ids
// (e.g. ProductLot::fetch() sets ->ref to the rowid, which is meaningless here).
$headerName = '';
if (!empty($linkableElement['name_field'])) {
    foreach (explode(',', $linkableElement['name_field']) as $nameField) {
        $nameField = trim($nameField);
        if ($nameField !== '' && !empty($linkedObject->$nameField)) {
            $headerName .= ($headerName !== '' ? ' ' : '') . $linkedObject->$nameField;
        }
    }
}
if (empty($headerName)) {
    $headerName = !empty($linkedObject->batch) ? $linkedObject->batch : (!empty($linkedObject->label) ? $linkedObject->label : '');
}

$pwaHeaderCenterHtml  = '<span class="pwa-header-type">' . dol_escape_htmltag($headerType) . '</span>';
$pwaHeaderCenterHtml .= '<span class="pwa-header-name">' . dol_escape_htmltag($headerName) . '</span>';
require __DIR__ . '/../../core/tpl/frontend/digiquali_pwa_header.tpl.php'; ?>

<div id="publicControlHistory">
    <?php if (getDolGlobalInt('DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY')) : ?>
        <nav class="public-card__tab pwa-bottom-nav">
            <div class="tab switch-public-control-view <?php echo ($route == 'linkedObjectAndControl' ? 'tab-active' : ''); ?>" data-route="linkedObjectAndControl">
                <i class="fas fa-clipboard-check"></i>
                <span><?php echo $langs->transnoentities('Status'); ?></span>
            </div>
            <?php if (is_array($linkedObject->linkedObjects['digiquali_control'] ?? null) && !empty($linkedObject->linkedObjects['digiquali_control'])) : ?>
                <div class="tab switch-public-control-view <?php echo ($route == 'controlList' ? 'tab-active' : ''); ?>" data-route="controlList">
                    <i class="fas fa-list"></i>
                    <span><?php echo $langs->transnoentities('ControlList'); ?></span>
                    <?php
                        $controlInfoArray = get_control_infos($linkedObject);
                        echo '<span class="badge badge-secondary">' . count($controlInfoArray['control']) . '</span>';
                    ?>
                </div>
            <?php endif; ?>
            <div class="tab switch-public-control-view <?php echo ($route == 'controlDocumentation' ? 'tab-active' : ''); ?>" data-route="controlDocumentation">
                <i class="fas fa-folder-open"></i>
                <span><?php echo $langs->transnoentities('Documentation'); ?></span>
            </div>
            <?php
                $parameters = ['trackId' => $trackId, 'entity' => $entity, 'linkedObject' => $linkedObject, 'linkableElements' => $linkableElements, 'linkableElement' => $linkableElement, 'objectType' => $objectType, 'objectId' => $objectId, 'routes' => &$routes, 'route' => $route, 'externals' => &$externals];
                $hookmanager->executeHooks('digiqualiPublicControlTab', $parameters, $object);
                print $hookmanager->resPrint;
            ?>
        </nav>
    <?php endif; ?>

    <div class="public-card__container">
        <?php
            if (isset($routes[$route])) {
                if (in_array($route, $externals)) {
                    $fromExternModule = true;
                }
                require_once __DIR__ . $routes[$route];
            } else {
                require_once __DIR__ . $routes[$defaultRoute];
            }
        ?>
    </div>
</div><?php

llxFooter('', 'public');
$db->close();
