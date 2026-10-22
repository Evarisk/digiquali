<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 *  \file       view/control/control_equipment.php
 *  \ingroup    digiquali
 *  \brief      Tab for equipment on Control
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
	die('Include of digiquali main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/digiquali_control.lib.php';
dol_include_once('/saturne/lib/object.lib.php');

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['productbatch']);

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object                 = new Control($db);
$controlEquipment       = new ControlEquipment($db);
$product                = new Product($db);
$productLot             = new ProductLot($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(array('controlequipment', 'globalcard')); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';

$permissiontoread   = $user->rights->digiquali->control->read;
$permissiontoadd    = $user->rights->digiquali->control->write;
$permissiontodelete = $user->rights->digiquali->control->delete || ($permissiontoadd && isset($object->status));

// Security check (enable the most restrictive one)
saturne_check_access($permissiontoread, $object);

/*
 * Action
 */

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks.
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Actions set_thirdparty, set_project
    require_once dol_buildpath('/saturne/core/tpl/actions/banner_actions.tpl.php');

	// Action to add or link equipment to control
	if ($action == 'add_equipment' && $permissiontoadd) {
        $productLotId = GETPOST('productLotId');
        $productId = GETPOST('productId');

        $productLotTmp = $productLot;
        $productTmp    = $product;

		if ($productLotId > 0 || $productId > 0) {

            if ($productLotId > 0) {
                $productLotTmp->fetch($productLotId);
                $productLotTmp->fetch_optionals();
            }

            if ($productLotTmp->fk_product > 0) {
                $productTmp->fetch($productLotTmp->fk_product);
            } else if ($productId > 0) {
                $productTmp->fetch($productId);
            }

			$controlEquipment->ref        = $controlEquipment->getNextNumRef();
            $controlEquipment->fk_lot     = $productLotId;

            $controlEquipment->fk_product = $productTmp->id;
			$controlEquipment->fk_control = $object->id;

            $jsonArray['label']        = $productTmp->label;
            $jsonArray['description']  = $productTmp->description;
			$jsonArray['dluo']         = $productLotTmp->eatby;
			$jsonArray['qc_frequency'] = $productLotTmp->array_options['qc_frequency'];

			$controlEquipment->json    = json_encode($jsonArray);

			$result = $controlEquipment->create($user);

			if ($result > 0) {
				setEventMessages($langs->trans('AddEquipmentLink') . ' ' . $controlEquipment->ref, []);
			} else {
				setEventMessages($langs->trans('ErrorEquipmentLink'), [], 'errors');
			}
		} else {
			setEventMessages($langs->trans('ErrorNoEquipmentSelected'), [], 'errors');
		}
	}

	// Action to unlink equipment from control
	if ($action == 'unlink_equipment' && $permissiontodelete) {
		$equipmentId = GETPOST('equipmentId');

		if ($equipmentId > 0) {
			$controlEquipment->fetch($equipmentId);

			$result = $controlEquipment->delete($user);

			if ($result > 0) {
				setEventMessages($langs->trans('UnlinkEquipmentLink') . ' ' . $controlEquipment->ref, []);
			}
		} else {
			setEventMessages($langs->trans('ErrorNoEquipmentSelected'), [], 'errors');
		}
	}
}

/*
 * View
 */

$help_url = '';
$title    = $langs->trans('ControlEquipment');

saturne_header(0,'', $title, $help_url);

if ($id > 0 || !empty($ref)) {
	// CONTROL EQUIPMENT LINES
	saturne_get_fiche_head($object, 'equipment', $title);
	saturne_banner_tab($object);

	$excludeFilter     = '';
	$controlEquipments = $controlEquipment->fetchFromParent($object->id);
	if (is_array($controlEquipments) && !empty ($controlEquipments)) {
		foreach ($controlEquipments as $equipment) {
			$excludeFilter .= $equipment->fk_lot . ',';
		}
        $excludeFilter = rtrim($excludeFilter, ',');
	}

	$products      = saturne_fetch_all_object_type('Product');
	$productsData  = [];
	if (is_array($products) && !empty($products)) {
		foreach ($products as $productSingle) {
			$productsData[$productSingle->id] = $productSingle->label;
		}
	}

    $productLotFilter = [];

    if (GETPOST('fk_product') > 0) {
        $productLotFilter['fk_product'] = GETPOST('fk_product');
    }
    if (dol_strlen($excludeFilter) > 0) {
        $productLotFilter['customsql'] = 'rowid NOT IN (' . $excludeFilter . ')';
    }

    $productLots      = saturne_fetch_all_object_type('ProductLot', '', '', 0, 0, $productLotFilter);
    $productLotsData  = [];
    if (is_array($productLots) && !empty($productLots)) {
        foreach ($productLots as $productLotSingle) {
            $productLotsData[$productLotSingle->id] = $productLotSingle->batch;
        }
    }

	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans("ControlEquipmentList"), '', '');
	print '<table class="centpercent noborder">';

	// Lines
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Ref') . '</td>';
    print '<td>' . $langs->trans('Product') . '</td>';
    print '<td>' . $langs->trans('Batch') . '</td>';
    print '<td>' . $langs->trans('Label') . '</td>';
    print '<td class="center">' . $langs->trans('OptimalExpirationDate');
    print $form->textwithpicto('', $langs->trans('OptimalExpirationDateDescription')) . '</td>';
    print '<td class="center">' . $langs->trans('RemainingDays') . '</td>';
    print '<td class="center">' . $langs->trans('Action') . '</td>';
    print '</tr>';
	if (is_array($controlEquipments) && !empty($controlEquipments)) {
		foreach ($controlEquipments as $controlEquipment) {

            $product = new Product($db);
            if ($controlEquipment->fk_lot > 0) {
                $productLot->fetch($controlEquipment->fk_lot);
            } else {
                $productLot = new ProductLot($db);
            }

            $product->fetch($controlEquipment->fk_product);

			$jsonArray = json_decode($controlEquipment->json);

			print '<tr id="'. $productLot->id .'" class="line-row oddeven">';
			print '<td>';
			print img_picto('', $controlEquipment->picto, 'class="pictofixedwidth"')  . $controlEquipment->ref;
			print '</td>';

            print '<td>';
            print $product->getNomUrl(1);
            print '</td>';

            print '<td>';
            if ($productLot->id > 0) {
                print $productLot->getNomUrl(1);
            } else {
                print $langs->trans('NoProductLot');
            }
			print '</td>';

			print '<td>';
            print $jsonArray->label;
			print '</td>';

			print '<td class="center">';
            print dol_print_date($jsonArray->dluo);
			print '</td>';

			print '<td class="center">';
            $remainingDays = num_between_day(dol_now(), $jsonArray->dluo > 0 ? $jsonArray->dluo : 0, 1) ?: '- ' . num_between_day($jsonArray->dluo > 0 ? $jsonArray->dluo : 0, dol_now(), 1);
            $remainingDays .= ' ' . strtolower(dol_substr($langs->trans("Day"), 0, 1)) . '.';

            if ($jsonArray->dluo <= dol_now()) {
                print '<span style="color: red;">';
            } elseif ($jsonArray->dluo <= dol_now() + 2592000) {
                print '<span style="color: orange;">';
            } else {
                print '<span style="color: green;">';
            }
            print $jsonArray->dluo > 0 ? $remainingDays : $langs->trans('NoData');
            print '</span></td>';
            print '</td>';

			print '<td class="center">';
			if ($object->status < Control::STATUS_LOCKED) {
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=unlink_equipment&equipmentId=' . $controlEquipment->id . '">';
				print img_delete();
				print '</a>';
			}
			print '</td></tr>';
		}
	}  else {
		print '<tr class="oddeven"><td colspan="7">';
        print '<span class="opacitymedium">' . $langs->trans('NoEquipmentLinked') . '</span>';
		print '</td></tr>';
    }
    print '</table>';

	if ($object->status < Control::STATUS_LOCKED) {
        print '<form id="add_control_equipment" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id='. $id . '">';
        print '<table class="centpercent noborder">';
        print '<tr class="oddeven"><td>';
        print img_object('', 'product') . ' ' . $form::selectarray('productId', $productsData, '', $langs->transnoentities('SelectProducts'), '', '', '', '', '', '','', 'maxwidth200 widthcentpercentminusx');
		print '</td>';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="add_equipment">';
        print '<td class="product-lot" colspan="5">';
        print img_object('', 'object_lot') . ' ' . $form::selectarray('productLotId', $productLotsData, '', $langs->transnoentities('SelectProductLots'), '', '', '', '', '', '','', 'maxwidth200 widthcentpercentminusx');
        print '</td>';
		print '<td class="center">';
		print '<input type="submit" id="add_equipment" class="button" name="add_equipment" value="' . $langs->trans('Add') . '">';
		print '</td></tr>';
		print '</tr>';
        print '</table></form>';
    }

	print '</div>';
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
