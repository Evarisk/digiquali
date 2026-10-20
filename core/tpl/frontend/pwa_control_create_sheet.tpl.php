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
 * \file    core/tpl/frontend/pwa_control_create_sheet.tpl.php
 * \ingroup digiquali
 * \brief   First screen of the PWA control creation: the choice of the sheet.
 *
 * Rendered by view/frontend/pwa_control_create.php (inherits its scope). Expects: $fixedObject, $fixedObjectType,
 * $objectsMetadata, $createParameters, $search.
 */

// Only a locked control sheet can be used, and only one able to control the object the page comes from
$sheetFilter = "t.type = 'control' AND t.status = " . Sheet::STATUS_LOCKED;
if (!empty($fixedObjectType)) {
    $sheetFilter .= " AND t.element_linked LIKE '%" . $db->escape('"' . $fixedObjectType . '":1') . "%'";
}
if (!empty($search)) {
    $sheetFilter .= " AND (t.ref LIKE '%" . $db->escape($search) . "%' OR t.label LIKE '%" . $db->escape($search) . "%')";
}
$sheets = saturne_fetch_all_object_type('Sheet', 'ASC', 'label', 0, 0, ['customsql' => $sheetFilter]);

$selfUrl = $_SERVER['PHP_SELF']; ?>

<div class="pwa-container pwa-control-create">
    <?php if (!empty($fixedObject)) {
        require __DIR__ . '/pwa_control_create_object.tpl.php';
    } ?>

    <h2 class="pwa-section-title"><i class="fas fa-clipboard-list"></i> <?php echo $langs->transnoentities('ChooseControlSheet'); ?></h2>

    <form class="pwa-search" method="GET" action="<?php echo dol_escape_htmltag($selfUrl); ?>">
        <?php foreach ($createParameters as $parameterName => $parameterValue) : ?>
            <input type="hidden" name="<?php echo $parameterName; ?>" value="<?php echo dol_escape_htmltag($parameterValue); ?>">
        <?php endforeach; ?>
        <i class="fas fa-search pwa-search-icon"></i>
        <input type="search" name="search" class="pwa-search-input" placeholder="<?php echo dol_escape_htmltag($langs->trans('Search') . '...'); ?>" value="<?php echo dol_escape_htmltag($search); ?>" autocomplete="off">
        <?php if (!empty($search)) : ?>
            <a href="<?php echo dol_escape_htmltag($selfUrl . '?' . http_build_query($createParameters)); ?>" class="pwa-search-clear" aria-label="<?php echo dol_escape_htmltag($langs->trans('Delete')); ?>"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>

    <?php if (!is_array($sheets) || empty($sheets)) : ?>
        <div class="pwa-empty">
            <i class="fas fa-clipboard-list"></i>
            <p><?php echo $langs->transnoentities('NoControlSheetAvailable'); ?></p>
        </div>
    <?php else : ?>
        <div class="pwa-list">
            <?php foreach ($sheets as $sheetSingle) : ?>
                <a class="pwa-card" href="<?php echo dol_escape_htmltag($selfUrl . '?' . http_build_query($createParameters + ['fk_sheet' => $sheetSingle->id])); ?>">
                    <span class="pwa-control-create__icon"><i class="fas fa-clipboard-list"></i></span>
                    <span class="pwa-card-body">
                        <span class="pwa-card-title"><?php echo dol_escape_htmltag($sheetSingle->label ?: $sheetSingle->ref); ?></span>
                        <span class="pwa-control-create__muted"><?php echo dol_escape_htmltag($sheetSingle->ref); ?></span>
                    </span>
                    <i class="fas fa-chevron-right pwa-card-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
