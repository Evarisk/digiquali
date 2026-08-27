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
 * \brief   Template page listing the controls done on the lots/serials of a product, grouped by warehouse
 */

/**
 * The following vars must be defined :
 * Globals    : $conf, $db, $langs
 * Variables  : $fromId (id of the product whose controls tab is displayed)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../../class/sheet.class.php';
require_once __DIR__ . '/../../../lib/digiquali_control.lib.php';

$productLotGroups = digiquali_get_product_lot_controls((int) $fromId);

// A product holding no lot/serial at all has nothing to show here
if (!empty($productLotGroups)) {
    $showProject = isModEnabled('project');

    // Sheets and projects are shared by many controls : fetch each of them only once
    $sheets   = [];
    $projects = [];

    $columns = [
        ['label' => $langs->trans('Batch'),                 'css' => ''],
        ['label' => $langs->trans('Qty'),                   'css' => 'center'],
        ['label' => $langs->trans('Ref'),                   'css' => ''],
        ['label' => $langs->trans('Sheet'),                 'css' => ''],
        ['label' => $langs->trans('DateCreation'),          'css' => 'center'],
        ['label' => $langs->trans('ControlDate'),           'css' => 'center'],
        ['label' => $langs->trans('NextControlDate'),       'css' => 'center'],
        ['label' => $langs->trans('DaysBeforeNextControl'), 'css' => 'center'],
        ['label' => $langs->trans('Verdict'),               'css' => 'center'],
        ['label' => $langs->trans('Status'),                'css' => 'center']
    ];
    if ($showProject) {
        array_splice($columns, 4, 0, [['label' => $langs->trans('Project'), 'css' => '']]);
    }
    $nbColumns = count($columns);

    print '<div class="control-product-lot-list">';
    print load_fiche_titre($langs->trans('ControlsOnProductLots'), '', 'lot');

    print '<div class="div-table-responsive-no-min">';
    print '<table class="tagtable nobottomiftotal noborder liste centpercent">';

    print '<tr class="liste_titre">';
    foreach ($columns as $column) {
        print '<th class="liste_titre' . (!empty($column['css']) ? ' ' . $column['css'] : '') . '">' . $column['label'] . '</th>';
    }
    print '</tr>';

    foreach ($productLotGroups as $warehouseGroup) {
        // Warehouse header : the lots out of stock are gathered in a group carrying no warehouse
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="' . $nbColumns . '">';
        print is_object($warehouseGroup['warehouse']) ? img_picto('', 'stock', 'class="pictofixedwidth"') . $warehouseGroup['warehouse']->getNomUrl(0) : '<span class="opacitymedium">' . $langs->trans('ProductLotsWithoutStock') . '</span>';
        print '</td>';
        print '</tr>';

        foreach ($warehouseGroup['lots'] as $lotData) {
            $nbControls = count($lotData['controls']);
            $lotCells   = '<td rowspan="' . max(1, $nbControls) . '">' . $lotData['lot']->getNomUrl(1) . '</td>';
            $lotCells  .= '<td class="center" rowspan="' . max(1, $nbControls) . '">' . (isset($lotData['qty']) ? price2num($lotData['qty'], 'MS') : '') . '</td>';

            if ($nbControls == 0) {
                print '<tr class="oddeven">';
                print $lotCells;
                print '<td colspan="' . ($nbColumns - 2) . '"><span class="opacitymedium">' . $langs->trans('NoControlOnThisProductLot') . '</span></td>';
                print '</tr>';
                continue;
            }

            foreach ($lotData['controls'] as $control) {
                print '<tr class="oddeven">';

                // The lot and its quantity are printed once and span every control it carries
                print $lotCells;
                $lotCells = '';

                print '<td class="nowraponall">' . $control->getNomUrl(1) . '</td>';

                if (!isset($sheets[$control->fk_sheet])) {
                    $sheet                      = new Sheet($db);
                    $sheets[$control->fk_sheet] = ($sheet->fetch($control->fk_sheet) > 0 ? $sheet : null);
                }
                print '<td class="tdoverflowmax200">' . (is_object($sheets[$control->fk_sheet]) ? $sheets[$control->fk_sheet]->getNomUrl(1) : '') . '</td>';

                if ($showProject) {
                    if (!empty($control->projectid) && !isset($projects[$control->projectid])) {
                        $project                       = new Project($db);
                        $projects[$control->projectid] = ($project->fetch($control->projectid) > 0 ? $project : null);
                    }
                    print '<td class="tdoverflowmax200">' . (!empty($control->projectid) && is_object($projects[$control->projectid]) ? $projects[$control->projectid]->getNomUrl(1) : '') . '</td>';
                }

                print '<td class="center nowraponall">' . dol_print_date($control->date_creation, 'dayhour') . '</td>';
                print '<td class="center nowraponall">' . (!empty($control->control_date) ? dol_print_date($control->control_date, 'day') : '') . '</td>';
                print '<td class="center nowraponall">' . (!empty($control->next_control_date) ? dol_print_date($control->next_control_date, 'day') : '') . '</td>';

                print '<td class="center">';
                if (!empty($control->next_control_date)) {
                    $daysBeforeNextControl = (int) round(($control->next_control_date - dol_now('tzuser')) / (3600 * 24));
                    $nextControlDateColor  = $control->getNextControlDateColor();
                    // The colour comes from the module configuration, so it cannot live in a stylesheet
                    print '<div class="wpeo-button" style="background-color: ' . $nextControlDateColor . '; border-color: ' . $nextControlDateColor . ';">' . $daysBeforeNextControl . '</div>';
                }
                print '</td>';

                $verdictColor = $control->verdict == 1 ? 'green' : ($control->verdict == 2 ? 'red' : 'grey');
                print '<td class="center"><div class="wpeo-button button-' . $verdictColor . '">' . $control->fields['verdict']['arrayofkeyval'][!empty($control->verdict) ? $control->verdict : 0] . '</div></td>';

                print '<td class="center">' . $control->getLibStatut(5) . '</td>';

                print '</tr>';
            }
        }
    }

    print '</table>';
    print '</div>';
    print '</div>';
}
