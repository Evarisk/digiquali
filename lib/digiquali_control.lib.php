<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_control.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for Control.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Prepare array of tabs for control.
 *
 * @param  Control $object Control object.
 * @return array           Array of tabs.
 * @throws Exception
 */
function control_prepare_head(Control $object): array
{
    // Global variables definitions.
    global $conf, $db, $langs;

    $head[1][0] = dol_buildpath('/digiquali/view/object_medias.php', 1) . '?id=' . $object->id . '&object_type=control';
    $head[1][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-image pictofixedwidth"></i>' . $langs->trans('Medias') : '<i class="fas fa-file-image"></i>';
    $head[1][2] = 'medias';

	// Initialize technical objects
	$controlEquipment = new ControlEquipment($db);

	$controlEquipmentArray = $controlEquipment->fetchFromParent($object->id);
	if (is_array($controlEquipmentArray) && !empty($controlEquipmentArray)) {
		$nbEquipment = count($controlEquipmentArray);
	} else {
		$nbEquipment = 0;
	}

	$head[2][0]  = dol_buildpath('/digiquali/view/control/control_equipment.php', 1) . '?id=' . $object->id;
	$head[2][1]  = $conf->browser->layout != 'phone' ? '<i class="fas fa-toolbox pictofixedwidth"></i>' . $langs->trans('ControlEquipment') : '<i class="fas fa-toolbox"></i>';
    $head[2][1] .= '<span class="badge marginleftonlyshort">' . $nbEquipment . '</span>';
	$head[2][2]  = 'equipment';

	$moreparam['documentType']       = 'ControlDocument';
    $moreparam['attendantTableMode'] = 'simple';
    $moreparam['handlePhoto']        = true;

    return saturne_object_prepare_head($object, $head, $moreparam, true);
}

/**
 * Get linked object infos
 *
 * @param  CommonObject $linkedObject     Linked object (product, productlot, project, etc.)
 * @param  array        $linkableElements Array of linkable elements infos (product, productlot, project, etc.)
 * @return array        $out              Array of linked object infos to display on public interface
 * @see    saturne_get_objects_metadata() Get linkable objects for sheet for example (product, productlot, project, etc.)
 */
function get_linked_object_infos(CommonObject $linkedObject, array $linkableElements): array
{
    global $conf, $db, $hookmanager, $langs, $user;

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
    require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

    // Initialize technical objects
    $link     = new Link($db);
    $ecmFiles = new EcmFiles($db);

    $permissionToRead = $user->hasRight('produit', 'lire');

    $linkableElement = $linkableElements[$linkedObject->element] ?? [];

    // Some elements don't share their name with their modulepart nor with their $conf key (product => produit / $conf->product, productlot => product_batch / $conf->productbatch)
    $elementMapping = [
        'product'    => ['modulePart' => 'produit', 'confKey' => 'product'],
        'productlot' => ['modulePart' => 'product_batch', 'confKey' => 'productbatch']
    ];

    $modulePart = $elementMapping[$linkedObject->element]['modulePart'] ?? $linkedObject->element;
    $confKey    = $elementMapping[$linkedObject->element]['confKey'] ?? $linkedObject->element;
    $uploadDir  = $conf->$confKey->multidir_output[$conf->entity] ?? '';

    $out['linkedObject']['images'] = dol_strlen($uploadDir) > 0 ? saturne_show_medias_linked($modulePart, $uploadDir . '/' . $linkedObject->ref . '/', 'small', 1, 0, 0, 0, 100, 100, 0, 0, 1,  $linkedObject->ref . '/', $linkedObject, 'photo', 0, 0,0, 1) : '';
    if ($linkedObject->element == 'productlot') {
        $linkedObject->element = 'product_lot';
    }

    $ecmFiles->fetchAll('', '', 0, 0, 't.share:isnot:null');

    // Filter ecm files by filepath containing linked object element
    $filteredEcmFilesLine = [];
    if (is_array($ecmFiles->lines) && !empty($ecmFiles->lines)) {
        $filteredEcmFilesLine = array_filter($ecmFiles->lines, function ($ecmFilesLine) use ($linkedObject) {
            return $linkedObject->element == $ecmFilesLine->src_object_type && $linkedObject->id == $ecmFilesLine->src_object_id;
        });
    }

    if ($linkedObject->element == 'product_lot') {
        $linkedObject->element = 'productlot';
    }

    $out['linkedObject']['links'] = [];
    $out['linkedObject']['files'] = $filteredEcmFilesLine;
    $out['linkedObject']['title']      = $langs->transnoentities(!empty($linkableElement['langs']) ? $linkableElement['langs'] : dol_ucfirst($linkedObject->element));
    if (method_exists($linkedObject, 'getNomUrl')) {
        $out['linkedObject']['name_field'] = $linkedObject->getNomUrl(1, !$permissionToRead ? 'nolink' : '');
    } else {
        // Some linked objects (e.g. productbatch) don't implement getNomUrl(): fall back to picto + name
        $picto = !empty($linkableElement['picto']) ? img_picto('', $linkableElement['picto'], 'class="pictofixedwidth"') : '';
        $name  = !empty($linkedObject->ref) ? $linkedObject->ref : (!empty($linkedObject->label) ? $linkedObject->label : ($linkedObject->batch ?? ''));
        $out['linkedObject']['name_field'] = $picto . dol_escape_htmltag($name);
    }

    // Per-element label and description field mapping
    $elementFieldsMap = [
        'product'    => ['label' => 'label',  'description' => 'description'],
        'productlot' => ['label' => '',       'description' => 'note_public'],
        'user'       => ['label' => '',       'description' => 'job'],
        'thirdparty' => ['label' => '',       'description' => 'note_public'],
        'contact'    => ['label' => '',       'description' => 'poste'],
        'project'    => ['label' => 'title',  'description' => 'description'],
        'task'       => ['label' => 'label',  'description' => 'description'],
    ];

    $fields = $elementFieldsMap[$linkedObject->element] ?? ['label' => '', 'description' => ''];

    $out['linkedObject']['label']       = ($fields['label'] && !empty($linkedObject->{$fields['label']}))
        ? dol_escape_htmltag($linkedObject->{$fields['label']})
        : '';
    $out['linkedObject']['description'] = ($fields['description'] && !empty($linkedObject->{$fields['description']}))
        ? dol_escape_htmltag($linkedObject->{$fields['description']})
        : '';

    $link->fetchAll($out['linkedObject']['links'], $linkedObject->element, $linkedObject->id);

    foreach ($out['linkedObject']['links'] as $link) {
        $link->name_field = $out['linkedObject']['name_field'];
    }
    foreach ($out['linkedObject']['files'] as $file) {
        $file->name_field = $out['linkedObject']['name_field'];
    }

    $out['linkedObject']['qc_frequency'] = '';

    $qcFrequency = get_parent_linked_object_qc_frequency($linkedObject, $linkableElements);
    if ($qcFrequency > 0 && getDolGlobalInt('DIGIQUALI_SHOW_QC_FREQUENCY_PUBLIC_INTERFACE')) {
        $out['linkedObject']['qc_frequency'] = '<i class="objet-icon fas fa-history"></i>' . $qcFrequency;
    }

    $out['parentLinkedObject']['files']      = [];
    $out['parentLinkedObject']['links']      = [];
    $out['parentLinkedObject']['images']     = '';
    $out['parentLinkedObject']['title']      = '';
    $out['parentLinkedObject']['name_field'] = '';
    if (isset($linkableElement['fk_parent']) && getDolGlobalInt('DIGIQUALI_SHOW_PARENT_LINKED_OBJECT_ON_PUBLIC_INTERFACE')) {
        $linkedObjectParentData = [];
        foreach ($linkableElements as $value) {
            if (isset($value['post_name']) && $value['post_name'] === $linkableElement['fk_parent']) {
                $linkedObjectParentData = $value;
                break;
            }
        }

        if (!empty($linkedObjectParentData['class_path'])) {
            require_once DOL_DOCUMENT_ROOT . '/' . $linkedObjectParentData['class_path'];

            $parentLinkedObject = new $linkedObjectParentData['class_name']($db);

            $parentLinkedObject->fetch($linkedObject->{$linkableElement['fk_parent']});

            $parentModulePart = $elementMapping[$parentLinkedObject->element]['modulePart'] ?? $parentLinkedObject->element;
            $parentConfKey    = $elementMapping[$parentLinkedObject->element]['confKey'] ?? $parentLinkedObject->element;
            $parentUploadDir  = $conf->$parentConfKey->multidir_output[$conf->entity] ?? '';

            $out['parentLinkedObject']['images']     = dol_strlen($parentUploadDir) > 0 ? saturne_show_medias_linked($parentModulePart, $parentUploadDir . '/' . $parentLinkedObject->ref . '/', 'small', 1, 0, 0, 0, 100, 100, 0, 0, 1,  $parentLinkedObject->ref . '/', $parentLinkedObject, 'photo', 0, 0,0, 1) : '';
            $out['parentLinkedObject']['title']      = $langs->transnoentities($linkedObjectParentData['langs']);
            $out['parentLinkedObject']['name_field'] = $permissionToRead ? $parentLinkedObject->getNomUrl(1, '', 0, -1, 1) : img_picto('', $linkedObjectParentData['picto'], 'class="pictofixedwidth"') . $parentLinkedObject->{$linkedObjectParentData['name_field']};

            // Filter ecm files by filepath containing linked object element
            $ecmFiles->fetchAll('', '', 0, 0, 't.share:isnot:null');

            $filteredEcmFilesLine = [];
            if (is_array($ecmFiles->lines) && !empty($ecmFiles->lines)) {
                $filteredEcmFilesLine = array_filter($ecmFiles->lines, function ($ecmFilesLine) use ($parentLinkedObject) {
                    return $parentLinkedObject->element == $ecmFilesLine->src_object_type && $parentLinkedObject->id == $ecmFilesLine->src_object_id;
                });
            }
            $out['parentLinkedObject']['files'] = $filteredEcmFilesLine;
            $link->fetchAll($out['parentLinkedObject']['links'], $parentLinkedObject->element, $parentLinkedObject->id);
            foreach ($out['parentLinkedObject']['links'] as $link) {
                $link->name_field = $out['parentLinkedObject']['name_field'];
            }
            foreach ($out['parentLinkedObject']['files'] as $file) {
                $file->name_field = $out['parentLinkedObject']['name_field'];
            }
        }
    }

    $out['images'] = $out['linkedObject']['images'];
    if (!empty($out['parentLinkedObject']['images']) && strpos($out['parentLinkedObject']['images'], 'nophoto') === false) {
        $out['images'] = $out['parentLinkedObject']['images'];
    }

    $out['files']  = array_merge($out['linkedObject']['files'], $out['parentLinkedObject']['files']);
    $out['links']  = array_merge($out['linkedObject']['links'], $out['parentLinkedObject']['links']);

    // Let other modules append the shared files and links of their own objects bound to the linked object
    $parameters = ['linkableElements' => $linkableElements, 'linkedObjectInfoArray' => $out];
    $resHook    = $hookmanager->executeHooks('digiqualiLinkedObjectDocumentation', $parameters, $linkedObject);
    if ($resHook >= 0) {
        $out['files'] = array_merge($out['files'], $hookmanager->resArray['files'] ?? []);
        $out['links'] = array_merge($out['links'], $hookmanager->resArray['links'] ?? []);
    }

    return $out;
}

/**
 * Get control infos
 *
 * @param  CommonObject $linkedObject Linked object (product, productlot, project, etc.)
 * @return array  $out                Array of control infos to display on public interface
 */
function get_control_infos(CommonObject $linkedObject): array
{
    global $conf, $db, $langs, $user;

    $out               = [];
    $lastControl       = null;

    $permissionToReadSheet    = $user->hasRight('digiquali', 'sheet', 'read');
    $permissionToReadControl  = $user->hasRight('digiquali', 'control', 'read');
    $permissionToWriteControl = $user->hasRight('digiquali', 'control', 'write');

    if (!is_array($linkedObject->linkedObjects['digiquali_control']) || empty($linkedObject->linkedObjects['digiquali_control'])) {
        $out['nextControl']['title'] = $langs->transnoentities('NoControl');
        if (getDolGlobalInt('DIGIQUALI_SHOW_ADD_CONTROL_BUTTON_ON_PUBLIC_INTERFACE') && $permissionToWriteControl) {
            $out['nextControl']['create_button'] = '<a class="wpeo-button button-square-60 button-radius-1 button-primary button-flex" href="' . dol_buildpath('custom/digiquali/view/control/control_card.php?action=create&fromtype=' . $linkedObject->element . '&fromid=' . $linkedObject->id, 1). '" target="_blank"><i class="button-icon fas fa-plus"></i></a>';
        }

        return $out;
    }

    // Remove controls with status < 2 and empty control_date
    $filteredControls = array_filter($linkedObject->linkedObjects['digiquali_control'], function ($control) {
        return $control->status == Control::STATUS_LOCKED && !empty($control->control_date);
    });

    // Sort controls by control_date desc
    usort($filteredControls, function ($a, $b) {
        return $b->control_date - $a->control_date;
    });

    $out['control'] = [];
    foreach ($filteredControls as $control) {
        if ($lastControl === null || $control->control_date > $lastControl->control_date) {
            $lastControl = $control;
        }

        $out['control'][$control->id]['image']        = saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $control->element . '/'. $control->ref . '/photos/', 'small', '', 0, 0, 0, 100, 100, 0, 0, 1, $control->element . '/'. $control->ref . '/photos/', $control, 'photo', 0, 0,0, 1);
        $out['control'][$control->id]['title']        = $langs->transnoentities(dol_ucfirst($control->element));
        $out['control'][$control->id]['ref']          = $control->getNomUrl(1, !$permissionToReadControl ? 'nolink' : 'blank', 1);
        $out['control'][$control->id]['control_date'] = '<i class="objet-icon far fa-calendar"></i>' . dol_print_date($control->control_date, 'day');

        $sheet = new Sheet($db);

        $sheet->fetch($control->fk_sheet);

        $out['control'][$control->id]['sheet_title'] = $langs->transnoentities('BasedOnModel');
        $out['control'][$control->id]['sheet_ref']   = $sheet->getNomUrl(1, !$permissionToReadSheet ? 'nolink' : 'blank', 1);

        if ($permissionToReadControl) {
            $out['control'][$control->id]['view_button'] = '<a class="wpeo-button button-square-60 button-radius-1 button-flex" href="' . dol_buildpath('custom/digiquali/view/control/control_card.php', 1) . '?id=' . $control->id . '" target="_blank"><i class="button-icon fas fa-eye"></i></a>';
        }
        $verdictControlColor                     = $control->verdict == 1 ? 'green' : 'red';
        $pictoControlColor                       = $control->verdict == 1 ? 'check' : 'exclamation';
        $out['control'][$control->id]['verdict'] = '<div class="wpeo-button button-square-60 button-radius-1 button-' . $verdictControlColor . ' button-disable-hover button-flex"><i class="button-icon fas fa-' . $pictoControlColor . '"></i></div>';

        if (getDolGlobalInt('DIGIQUALI_SHOW_LAST_CONTROL_FIRST_ON_PUBLIC_HISTORY')) {
            break;
        }
    }

    if (!empty($lastControl)) {
        $out['nextControl']['title'] = $langs->transnoentities('NoPeriodicityControl');
        if (getDolGlobalInt('DIGIQUALI_SHOW_ADD_CONTROL_BUTTON_ON_PUBLIC_INTERFACE') && $permissionToWriteControl) {
            $arraySelected = '';
            if (isModEnabled('categorie')) {
                require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
                $category   = new Categorie($db);
                $categories = $category->containing($lastControl->id, $lastControl->element);
                if (is_array($categories) && !empty($categories)) {
                    $arraySelected = '&categories=' . implode(',', array_column($categories, 'id'));
                }
            }

            $moreParams = '&fromtype=' . $linkedObject->element . '&fromid=' . $linkedObject->id . '&fk_sheet=' . $lastControl->fk_sheet . (!empty($lastControl->projectid) ? '&projectid=' . $lastControl->projectid : '') . $arraySelected;
            $out['nextControl']['create_button'] = '<a class="wpeo-button button-square-60 button-radius-1 button-primary button-flex" href="' . dol_buildpath('custom/digiquali/view/control/control_card.php?action=create' . $moreParams, 1) . '" target="_blank"><i class="button-icon fas fa-plus"></i></a>';
        }
        $verdictControlColor           = $lastControl->verdict == 1 ? 'green' : 'red';
        $pictoControlColor             = $lastControl->verdict == 1 ? 'check' : 'exclamation';
        $out['nextControl']['verdict'] = '<div class="wpeo-button button-square-60 button-radius-1 button-' . $verdictControlColor . ' button-disable-hover button-flex"><i class="button-icon fas fa-' . $pictoControlColor . '"></i></div>';
        if (!empty($lastControl->next_control_date)) {
            $nextControl                                   = (int) round(($lastControl->next_control_date - dol_now('tzuser'))/(3600 * 24));
            $out['nextControl']['title']                   = $langs->transnoentities('NextControl');
            $out['nextControl']['next_control_date']       = '<i class="objet-icon far fa-calendar"></i>' . dol_print_date($lastControl->next_control_date, 'day');
            $out['nextControl']['next_control_date_color'] = $lastControl->getNextControlDateColor();
            $out['nextControl']['next_control']            = '<i class="objet-icon far fa-clock"></i>' . $nextControl . ' ' . $langs->transnoentities('Days');
        }
    }

    return $out;
}

/**
 * Get parent linked object qc frequency
 *
 * @param  CommonObject $linkedObject    Linked object (product, productlot, project, etc.)
 * @param  array        $objectsMetadata Array of objects with metadata  (product, productlot, project, etc.)
 * @return int          $qcFrequency     QC frequency
 * @throws Exception
 */
function get_parent_linked_object_qc_frequency(CommonObject $linkedObject, array $objectsMetadata): int
{
    // Load Objects metadata
    if (empty($objectsMetadata)) {
        $objectsMetadata = saturne_get_objects_metadata();
    }

    $qcFrequency = 0;

    // $linkedObject->element holds the link name, the metadata is keyed by object type : they differ
    // for thirdparty/societe, contact/socpeople and task/project_task, hence this lookup.
    $objectMetadata = [];
    foreach ($objectsMetadata as $objectsMetadataEntry) {
        if (($objectsMetadataEntry['link_name'] ?? '') === $linkedObject->element) {
            $objectMetadata = $objectsMetadataEntry;
            break;
        }
    }

    if (isset($objectMetadata['fk_parent'])) {
        $parentLinkedObject = null;
        foreach ($objectsMetadata as $objectMetadata) {
            if (isset($objectMetadata['post_name']) && $objectMetadata['post_name'] === $objectMetadata['fk_parent']) {
                $parentLinkedObject = $objectMetadata['object'];
                break;
            }
        }

        if ($parentLinkedObject != null) {
            $parentLinkedObject->fetch($linkedObject->{$objectMetadata['fk_parent']});
            if (empty($linkedObject->array_options['options_qc_frequency']) && !empty($parentLinkedObject->array_options['options_qc_frequency'])) {
                $qcFrequency = $parentLinkedObject->array_options['options_qc_frequency'];
                //. ' ' . $langs->transnoentities('Days') . ' (' . $langs->transnoentities('Inherited') . ')';
            }
        }
    }

    return $qcFrequency;
}

/**
 * Get task infos
 *
 * @param  Task  $task Task object
 * @return array $out  Array of task infos to display
 * @throws Exception
 */
function get_task_infos(Task $task): array
{
    global $conf, $db, $langs;

    $out = [];

    $out['task']['ref']   = $task->getNomUrl(1, 'withproject');
    $out['task']['label'] = $task->label;

    $userTmp = new User($db);
    $userTmp->fetch($task->fk_user_creat);
    $out['task']['author'] = $userTmp->getNomUrl(1);

    $out['task']['assigned']         = [];
    $out['task']['assigned_user_id'] = 0;
    $assignedContacts = $task->liste_contact(-1, 'internal', 0, 'TASKEXECUTIVE');
    if (is_array($assignedContacts)) {
        foreach ($assignedContacts as $assignedContact) {
            $userTmp->fetch($assignedContact['id']);
            $out['task']['assigned'][] = $userTmp->getNomUrl(1);
            if (empty($out['task']['assigned_user_id'])) {
                $out['task']['assigned_user_id'] = $assignedContact['id'];
            }
        }
    }

    if (empty($task->date_start) && empty($task->date_end)) {
        $out['task']['date'] = dol_print_date($task->date_c, 'dayhour');
    } else {
        $out['task']['date']  = !empty($task->date_start) ? dol_print_date($task->date_start, 'dayhour') : '?';
        $out['task']['date'] .= ' - ' . (!empty($task->date_end) ? dol_print_date($task->date_end, 'dayhour') : '?');
    }

    $out['task']['time'] = 'N/A';
    $task->getSummaryOfTimeSpent();
    if ($task->timespent_total_duration > 0 && $task->planned_workload > 0) {
        $out['task']['time'] = convertSecondToTime($task->timespent_total_duration) . ' / ' . convertSecondToTime($task->planned_workload);
    }

    // Always defined, so that consumers can iterate without checking the key on a task with no time spent
    $out['task']['timespent'] = [];

    $task->fetchTimeSpentOnTask();
    if (is_array($task->lines) && !empty($task->lines)) {
        foreach ($task->lines as $timespent) {
            $out['task']['timespent'][$timespent->timespent_line_id]['id'] = $timespent->timespent_line_id;

            $userTmp->fetch($timespent->timespent_line_fk_user);
            $out['task']['timespent'][$timespent->timespent_line_id]['author'] = $userTmp->getNomUrl(1);

            if (!empty($timespent->timespent_line_datehour)) {
                $out['task']['timespent'][$timespent->timespent_line_id]['date'] = dol_print_date($timespent->timespent_line_datehour, 'dayhour');
            }
            if (!empty($timespent->timespent_line_note)) {
                $out['task']['timespent'][$timespent->timespent_line_id]['comment'] = $timespent->timespent_line_note;
            }
            if (!empty($timespent->timespent_line_duration)) {
                $out['task']['timespent'][$timespent->timespent_line_id]['duration'] = convertSecondToTime($timespent->timespent_line_duration);
            }
        }
    }

    $out['task']['timespentSingle']['id']       = $task->timespent_id;
    $out['task']['timespentSingle']['date']     = dol_print_date($task->timespent_datehour, '%Y-%m-%dT%H:%M');
    $out['task']['timespentSingle']['comment']  = $task->timespent_note;
    $out['task']['timespentSingle']['duration'] = $task->timespent_duration / 60;

    $out['task']['progress'] = $task->progress;
    $out['task']['budget']   = price($task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);

    return $out;
}

/**
 * Prepare array of tabs for ControlLine
 *
 * @param	ControlLine	$object					ControlLine
 * @return 	array<array{string,string,string}>	Array of tabs
 */
function controldetPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("digiquali@digiquali");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/digiquali/view/controldet/controldet_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Controldet");
	$head[$h][2] = 'card';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'test@test');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'test@test', 'remove');

	return $head;
}

/**
 * Get the lots/serials of a product along with the stock holding them.
 *
 * The controls tab of a product lists the controls linked to the product itself. Lots and serials are
 * distinct objects, so the controls really carried out on the units held in stock never show up there.
 * The second list of that tab is restricted to those lots and shows in which warehouse each of them
 * stands : both needs are served by this map, which the page caches for the list hooks to reuse.
 *
 * @param  int   $productId Id of the parent product
 * @return array            Lot id => ['lot' => ProductLot, 'warehouses' => Entrepot[], 'qty' => float], ordered by batch
 * @throws Exception
 */
function digiquali_get_product_lot_stock(int $productId): array
{
    global $db;

    if ($productId <= 0 || !isModEnabled('productbatch')) {
        return [];
    }

    // Load Dolibarr libraries
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

    // Lots/serials of the product
    $lots = [];
    $sql  = 'SELECT pl.* FROM ' . $db->prefix() . 'product_lot AS pl';
    $sql .= ' WHERE pl.fk_product = ' . $productId;
    $sql .= ' AND pl.entity IN (' . getEntity('productlot') . ')';
    $sql .= ' ORDER BY pl.batch';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog('digiquali_get_product_lot_stock ' . $db->lasterror(), LOG_ERR);
        return [];
    }
    while ($obj = $db->fetch_object($resql)) {
        $productLot = new ProductLot($db);
        $productLot->setVarsFromFetchObj($obj);
        $lots[(int) $obj->rowid] = ['lot' => $productLot, 'warehouses' => [], 'qty' => 0];
    }
    $db->free($resql);

    if (empty($lots)) {
        return [];
    }

    $lotIdsByBatch = [];
    foreach ($lots as $lotId => $lotData) {
        $lotIdsByBatch[(string) $lotData['lot']->batch] = $lotId;
    }

    // Warehouses holding each lot. The stock rows carry the batch value, not the lot id, hence the
    // lookup on the batch built above
    $sql  = 'SELECT ps.fk_entrepot AS warehouse_id, pb.batch, SUM(pb.qty) AS qty';
    $sql .= ' FROM ' . $db->prefix() . 'product_batch AS pb';
    $sql .= ' INNER JOIN ' . $db->prefix() . 'product_stock AS ps ON (ps.rowid = pb.fk_product_stock)';
    $sql .= ' INNER JOIN ' . $db->prefix() . 'entrepot AS e ON (e.rowid = ps.fk_entrepot)';
    $sql .= ' WHERE ps.fk_product = ' . $productId;
    $sql .= ' AND e.entity IN (' . getEntity('stock') . ')';
    $sql .= ' GROUP BY ps.fk_entrepot, pb.batch, e.ref';
    $sql .= ' HAVING SUM(pb.qty) <> 0';
    $sql .= ' ORDER BY e.ref, pb.batch';

    $resql = $db->query($sql);
    if ($resql) {
        $warehouses = [];
        while ($obj = $db->fetch_object($resql)) {
            $lotId = $lotIdsByBatch[(string) $obj->batch] ?? 0;
            if (empty($lotId)) {
                // A batch held in stock without its lot record is not an object a control can point at
                continue;
            }

            $warehouseId = (int) $obj->warehouse_id;
            if (!isset($warehouses[$warehouseId])) {
                $warehouse                = new Entrepot($db);
                $warehouses[$warehouseId] = ($warehouse->fetch($warehouseId) > 0 ? $warehouse : null);
            }
            if (!is_object($warehouses[$warehouseId])) {
                continue;
            }

            $lots[$lotId]['warehouses'][$warehouseId] = $warehouses[$warehouseId];
            $lots[$lotId]['qty']                     += (float) $obj->qty;
        }
        $db->free($resql);
    } else {
        dol_syslog('digiquali_get_product_lot_stock ' . $db->lasterror(), LOG_ERR);
    }

    return $lots;
}
