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
 * \file    core/tpl/frontend/public_control_nav.tpl.php
 * \ingroup digiquali
 * \brief   Bottom navigation of the public control page, one item per view.
 *
 * Rendered by public/control/public_control_history.php (inherits its scope). Expects: $route, $routes, $externals,
 * $controlInfoArray, $trackId, $entity, $linkedObject, $linkableElements, $linkableElement, $objectType, $objectId.
 * Other modules add their own items through the digiqualiPublicControlTab hook: they print a .tab element, a
 * .switch-public-control-view one for a view of this page (registered in $routes) or a plain link.
 */

$publicControlNavItems = [
    'linkedObjectAndControl' => ['icon' => 'fa-clipboard-check', 'label' => $langs->transnoentities('Status')],
    'controlList'            => ['icon' => 'fa-history', 'label' => $langs->transnoentities('Controls'), 'count' => count($controlInfoArray['control'])],
    'controlDocumentation'   => ['icon' => 'fa-folder-open', 'label' => $langs->transnoentities('Documentation')]
]; ?>

<nav class="pwa-bottom-nav public-control-nav">
    <?php foreach ($publicControlNavItems as $navRoute => $navItem) : ?>
        <div class="pwa-nav-item switch-public-control-view<?php echo ($route == $navRoute ? ' active' : ''); ?>" data-route="<?php echo $navRoute; ?>" role="button" tabindex="0">
            <i class="fas <?php echo $navItem['icon']; ?>"></i>
            <span><?php echo $navItem['label']; ?></span>
            <?php if (!empty($navItem['count'])) : ?>
                <span class="public-control-nav__count"><?php echo $navItem['count']; ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach;

    $parameters = ['trackId' => $trackId, 'entity' => $entity, 'linkedObject' => $linkedObject, 'linkableElements' => $linkableElements, 'linkableElement' => $linkableElement, 'objectType' => $objectType, 'objectId' => $objectId, 'routes' => &$routes, 'route' => $route, 'externals' => &$externals];
    $hookmanager->executeHooks('digiqualiPublicControlTab', $parameters, $object);
    print $hookmanager->resPrint; ?>
</nav>
