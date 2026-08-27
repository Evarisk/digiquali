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
 * \file    core/tpl/control/control_product_lot_list.tpl.php
 * \ingroup digiquali
 * \brief   Template page for the second list of the controls tab of a product : the controls carried
 *          out on its lots/serials, rendered by the saturne list templates
 */

/**
 * The following vars must be defined :
 * Globals    : $conf, $db, $hookmanager, $langs, $user
 * Objects    : $extrafields, $form, $object
 * Variables  : $arrayfields, $excludeFields, $fromId, $fromType, $objectsMetadata, $search, and every
 *              other variable the saturne list templates read
 */

// Load DigiQuali libraries
require_once __DIR__ . '/../../../lib/digiquali_control.lib.php';

$langs->loadLangs(['productbatch', 'stocks']);

$productLotStock = digiquali_get_product_lot_stock((int) $fromId);

// Nothing to show for a product holding no lot/serial
if (!empty($productLotStock)) {
    // The lot restriction reaches the query through the cache instead of through $search : a search key
    // would land in the URL of every link of the page and would filter the list above as well
    $conf->cache['digiqualiProductLotStock'] = $productLotStock;

    // The product filter belongs to the list above : a control carried out on a lot is not linked to the
    // product itself, so keeping it would leave this list empty
    $search[$objectsMetadata['product']['post_name']] = '';

    // A control has no warehouse of its own : the column is filled by the loop hook from the lot the
    // control concerns. It has no column in the control table either, hence its exclusion from the SELECT
    $object->fields['warehouse'] = ['type' => 'varchar(255)', 'label' => 'Warehouse', 'enabled' => 1, 'position' => 19, 'visible' => 2, 'csslist' => 'tdoverflowmax150', 'disablesort' => 1, 'disablesearch' => 1];
    $arrayfields['t.warehouse']  = ['label' => 'Warehouse', 'checked' => 1, 'enabled' => 1, 'position' => 19];
    $excludeFields[]             = 'warehouse';
    $object->fields              = dol_sort_array($object->fields, 'position');
    $arrayfields                 = dol_sort_array($arrayfields, 'position');

    // What the saturne list templates read to render a list : a title and a saved column layout of its
    // own, and a query rebuilt from the state set above
    $title        = $langs->trans('ControlsOnProductLots');
    $listLayoutId = $object->element . '_productlot';
    unset($listColumnWidths, $useSideFilterPanel);

    // The wrapper marks this second list for the stylesheet, which gives the list above back its natural
    // height : the theme reserves 392px under a list it believes ends the page
    print '<div class="control-product-lot-list">';

    // The header appends to these what it prints next to the title : left as they are, this list would
    // repeat the buttons of the one above
    unset($cardButton, $newCardButton);

    // require, not require_once : these very templates already rendered the list above
    require __DIR__ . '/../../../../saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php';
    require __DIR__ . '/../../../../saturne/core/tpl/list/objectfields_list_header.tpl.php';

    // The displayed columns are saved per page and not per list, so the header just unchecked a column
    // the saved selection cannot know about. It belongs to this list only : put it back
    $arrayfields['t.warehouse']['checked'] = 1;

    require __DIR__ . '/../../../../saturne/core/tpl/list/objectfields_list_search_input.tpl.php';
    require __DIR__ . '/../../../../saturne/core/tpl/list/objectfields_list_search_title.tpl.php';
    require __DIR__ . '/../../../../saturne/core/tpl/list/objectfields_list_loop_object.tpl.php';
    require __DIR__ . '/../../../../saturne/core/tpl/list/objectfields_list_footer.tpl.php';

    print '</div>';

    // The cache drives a hook shared by every control list : leave nothing behind
    unset($conf->cache['digiqualiProductLotStock']);
}
