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
 * \file    core/tpl/frontend/pwa_control_create_form.tpl.php
 * \ingroup digiquali
 * \brief   Second screen of the PWA control creation: the controlled object and what the sheet lets choose.
 *
 * Rendered by view/frontend/pwa_control_create.php (inherits its scope). Expects: $sheet, $fixedObject,
 * $fixedObjectType, $objectsMetadata, $sheetObjectTypes, $createParameters, $projectId, $categories.
 */

$form = new Form($db);

$selfUrl = $_SERVER['PHP_SELF'];

// The project and the tags come from the last control of the object, else from the sheet
$selectedProjectId  = $projectId > 0 ? $projectId : (int) $sheet->fk_project;
$selectedCategories = !empty($categories) ? $categories : (json_decode($sheet->default_control_tags ?? '[]', true) ?: []); ?>

<form class="pwa-container pwa-control-create" method="POST" action="<?php echo dol_escape_htmltag($selfUrl); ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="fk_sheet" value="<?php echo $sheet->id; ?>">
    <?php foreach (['fromtype', 'fromid', 'backtopage'] as $parameterName) :
        if (!empty($createParameters[$parameterName])) : ?>
            <input type="hidden" name="<?php echo $parameterName; ?>" value="<?php echo dol_escape_htmltag($createParameters[$parameterName]); ?>">
        <?php endif;
    endforeach; ?>

    <h2 class="pwa-section-title"><i class="fas fa-clipboard-list"></i> <?php echo $langs->transnoentities('Sheet'); ?></h2>
    <div class="pwa-control-create__object">
        <span class="pwa-control-create__icon"><i class="fas fa-clipboard-list"></i></span>
        <span class="pwa-card-body">
            <span class="pwa-card-title"><?php echo dol_escape_htmltag($sheet->label ?: $sheet->ref); ?></span>
            <span class="pwa-control-create__muted"><?php echo dol_escape_htmltag($sheet->ref); ?></span>
        </span>
        <a class="pwa-control-create__change" href="<?php echo dol_escape_htmltag($selfUrl . '?' . http_build_query($createParameters)); ?>"><?php echo $langs->transnoentities('Modify'); ?></a>
    </div>

    <h2 class="pwa-section-title"><i class="fas fa-cube"></i> <?php echo $langs->transnoentities('ControlledObject'); ?></h2>
    <?php if (!empty($fixedObject)) {
        require __DIR__ . '/pwa_control_create_object.tpl.php';
    } else { ?>
        <div class="pwa-control-create__panel">
            <?php foreach ($sheetObjectTypes as $objectType) :
                $objectMetadata = $objectsMetadata[$objectType];
                $objectFilter   = !empty($objectMetadata['filter']) ? ['customsql' => $objectMetadata['filter']] : []; ?>
                <div class="pwa-control-create__field">
                    <label for="<?php echo $objectMetadata['post_name']; ?>"><?php echo img_picto('', $objectMetadata['picto'], 'class="pictofixedwidth"') . $langs->transnoentities($objectMetadata['langs']); ?></label>
                    <?php print $form->selectArray($objectMetadata['post_name'], digiquali_get_controllable_object_options($objectType, $objectMetadata, $objectFilter), GETPOSTINT($objectMetadata['post_name']), $langs->trans('Select') . ' ' . strtolower($langs->trans($objectMetadata['langs'])), 0, 0, '', 0, 0, 0, '', 'widthcentpercentminusx'); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php } ?>

    <h2 class="pwa-section-title"><i class="fas fa-info-circle"></i> <?php echo $langs->transnoentities('Informations'); ?></h2>
    <div class="pwa-control-create__panel">
        <div class="pwa-control-create__field">
            <span class="pwa-control-create__label"><?php echo $langs->transnoentities('Controller'); ?></span>
            <span><?php echo $user->getNomUrl(-1, 'nolink'); ?></span>
        </div>

        <?php if (isModEnabled('project') && !empty($sheet->show_project)) :
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
            $formProject = new FormProjets($db); ?>
            <div class="pwa-control-create__field">
                <label for="projectid"><?php echo $langs->transnoentities('Project'); ?></label>
                <?php print $formProject->select_projects(-1, $selectedProjectId, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'widthcentpercentminusx'); ?>
            </div>
        <?php elseif ($selectedProjectId > 0) : ?>
            <input type="hidden" name="projectid" value="<?php echo $selectedProjectId; ?>">
        <?php endif;

        if (isModEnabled('categorie') && !empty($sheet->show_tags)) : ?>
            <div class="pwa-control-create__field">
                <label for="categories"><?php echo $langs->transnoentities('Categories'); ?></label>
                <?php print $form->multiselectarray('categories', $form->select_all_categories('control', '', 'parent', 64, 0, 1), $selectedCategories, 0, 0, 'widthcentpercent'); ?>
            </div>
        <?php else :
            foreach ($selectedCategories as $categoryId) : ?>
                <input type="hidden" name="categories[]" value="<?php echo (int) $categoryId; ?>">
            <?php endforeach;
        endif; ?>
    </div>

    <button type="submit" class="wpeo-button button-primary pwa-control-create__submit">
        <i class="fas fa-play"></i> <?php echo $langs->transnoentities('StartControl'); ?>
    </button>
</form>
