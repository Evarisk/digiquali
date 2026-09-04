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
 * \file    view/control/control_list.php
 * \ingroup digiquali
 * \brief   List page for control
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// load DigiQuali libraries
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../lib/digiquali_linked_object.lib.php';
require_once __DIR__ . '/../../core/boxes/digiqualiwidget1.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action     = GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');                                        // The bulk action (combo box choice into lists)
$fromType   = GETPOST('fromtype', 'alpha');                                          // Element type
$fromId     = GETPOSTINT('fromid');                                                        // Element id

// Get list parameters
$toselect                                   = [];
[$confirm, $contextpage, $optioncss, $mode] = ['', '', '', ''];
$listParameters                             = saturne_load_list_parameters(basename(dirname(__FILE__)));
foreach ($listParameters as $listParameterKey => $listParameter) {
    $$listParameterKey = $listParameter;
}

// Get pagination parameters
[$limit, $page, $offset] = [0, 0, 0];
[$sortfield, $sortorder] = ['', ''];
$paginationParameters    = saturne_load_pagination_parameters();
foreach ($paginationParameters as $paginationParameterKey => $paginationParameter) {
    $$paginationParameterKey = $paginationParameter;
}

// Initialize technical objects
$object      = new Control($db);
$box         = new digiqualiwidget1($db);
$extrafields = new ExtraFields($db);
if (isModEnabled('categorie')) {
    $categorie = new Categorie($db);
}

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$contextpage]); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (isModEnabled('categorie')) {
    $searchCategories = GETPOST('search_category_' . $object->element . '_list', 'array');
}

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
    reset($object->fields);   // Reset is required to avoid key() to return null
    $sortfield = 't.date_creation'; // Set here default search field. By default, date_creation
}
if (!$sortorder) {
    $sortorder = 'DESC';
}

// Definition of custom fields for columns
$nbLinkableElements             = 0;
$objectPosition                 = 21;
$excludeFields                  = [];
$objectsMetadata                = saturne_get_objects_metadata();
$conf->cache['objectsMetadata'] = $objectsMetadata;

// The tab is opened with the link name of the element (fromtype=commande), which is not always the key the
// metadata array is indexed with (order) : resolve the entry once instead of reading the array with a key
// that does not exist. Stays empty for the fromtype values that designate no object, ex. fk_sheet
$fromObjectMetadata = digiquali_get_object_metadata_from_link_name($objectsMetadata, $fromType);
foreach($objectsMetadata as $objectMetadata) {
    // conf holds the raw constant value : absent or empty means the link is off. A == 0 test would
    // let those through, since PHP 8 compares '' to 0 as strings
    if (empty($objectMetadata['conf'])) {
        continue;
    }

    // A tab shows the column of the element it filters on, plus, on a product, the lot/serial each
    // control concerns : that one belongs to another metadata than the one the tab was opened from
    if (empty($fromType) || $fromType == $objectMetadata['link_name'] || ($fromType == 'product' && $objectMetadata['link_name'] == 'productlot')) {
        $object->fields[$objectMetadata['post_name']] = [
            'type'       => 'integer:' . $objectMetadata['class_name'] . ':' . $objectMetadata['class_path'],
            'label'      => $langs->trans($objectMetadata['langs']),
            'enabled'    => 1,
            'position'   => $objectPosition,
            'visible'    => 2,
            'csslist'    => 'minwidth150 maxwidth200',
            // Sort on the aliases the printFieldListSelect hook builds from llx_element_element, not on a
            // t.<key> column that does not exist. The empty flag comes first so the controls without any
            // linked element stay at the bottom in both directions
            'otheralias' => 'sortempty_' . $objectMetadata['post_name'] . ',sortvalue_'
        ];

        $objectPosition++;
        $nbLinkableElements++;
        $excludeFields[] = $objectMetadata['post_name'];
    }
}

$signatoriesInDictionary                = saturne_fetch_dictionary('c_' . $object->element . '_attendants_role');
$conf->cache['signatoriesInDictionary'] = $signatoriesInDictionary;
if (is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
    $customFieldsPosition = 111;
    foreach ($signatoriesInDictionary as $signatoryInDictionary) {
        // Signatory role columns are computed from the signatures (no real t.<ref> column in
        // the control table), so they cannot be ordered as a base-table column (disablesort).
        // Search IS supported: printFieldListSearch() filters on the signatory's name.
        $object->fields[$signatoryInDictionary->ref] = ['label' => $signatoryInDictionary->ref, 'enabled' => 1, 'position' => $customFieldsPosition++, 'visible' => 2, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx right', 'disablesort' => 1];
        $excludeFields[]                             = $signatoryInDictionary->ref;
    }
}

// Computed columns are built by the saturnePrintFieldListLoopObject hook, not selected from the control
// table, so they must declare disablesort: the invalid-sortfield guard of
// objectfields_list_build_sql_select would silently discard the ORDER BY they advertise.
// days_remaining_before_next_control is the exception: printFieldListSelect adds it as a real SELECT
// alias, and its empty otheralias makes the title sort on that alias instead of t.<key>
$object->fields['days_remaining_before_next_control'] = ['label' => 'DaysBeforeNextControl',      'enabled' => 1, 'position' => 66,  'visible' => 2, 'csslist' => 'center', 'otheralias' => ''];
$object->fields['question_answered']                  = ['label' => 'QuestionAnswered',           'enabled' => 1, 'position' => 66,  'visible' => 2, 'css' => 'center minwidth200 maxwidth250 widthcentpercentminusxx', 'disablesort' => 1];
$object->fields['last_status_date']                   = ['label' => 'LastStatusDate',             'enabled' => 1, 'position' => 67,  'visible' => 2, 'css' => 'center minwidth200 maxwidth300 widthcentpercentminusxx', 'disablesort' => 1];
$object->fields['society_attendants']                 = ['label' => 'SocietyAttendants',          'enabled' => 1, 'position' => 115, 'visible' => 2, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx',        'disablesort' => 1];
$object->fields['average_percentage_questions']       = ['label' => 'AveragePercentageQuestions', 'enabled' => 1, 'position' => 220, 'visible' => 2, 'css' => 'center minwidth200 maxwidth250 widthcentpercentminusxx', 'disablesort' => 1];

$excludeFields = array_merge($excludeFields, ['days_remaining_before_next_control', 'question_answered', 'last_status_date', 'society_attendants', 'average_percentage_questions']);

// Initialize array of search criterias
$searchAll = trim(GETPOST('search_all'));
$search    = [];
$search['status'] = saturne_get_status_search_filter([Control::STATUS_DRAFT, Control::STATUS_VALIDATED, Control::STATUS_LOCKED]);
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha') !== '') {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
    if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
        $search[$key . '_dtend']   = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
    }
}

if (!empty($fromType)) {
    if (!empty($fromObjectMetadata)) {
        $search[$fromObjectMetadata['post_name']] = $fromId;
    }
    switch ($fromType) {
        case 'fk_sheet':
            $search['fk_sheet'] = $fromId;
            break;
        case 'user':
            $search['fk_user_controller'] = $fromId;
            break;
    }
}

// List of fields to search into when doing a "search in all"
$fieldsToSearchAll = [];
foreach ($object->fields as $key => $val) {
    if (!empty($val['searchall'])) {
        $fieldsToSearchAll['t.' . $key] = $val['label'];
    }
}

// Definition of array of fields for columns
foreach ($object->fields as $key => $val) {
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible']);
        $arrayfields['t.' . $key] = [
            'label'    => $val['label'],
            'checked'  => (($visible < 0 || (!isset($val['showinpwa']) && $mode == 'pwa')) ? 0 : 1),
            'enabled'  => ($visible != 3 && dol_eval($val['enabled'])),
            'position' => $val['position'],
            'help'     => $val['help'] ?? '',
        ];
    }
}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields    = dol_sort_array($arrayfields, 'position');

// Permissions
$permissiontoread   = $user->hasRight($object->module, $object->element, 'read');
$permissiontoadd    = $user->hasRight($object->module, $object->element, 'write');
$permissiontodelete = $user->hasRight($object->module, $object->element, 'delete');

// Security check
saturne_check_access($permissiontoread, $object);

// Enable the "Validate" mass action offered by the saturne list templates
$enableMassValidate = 1;

/*
 * Actions
 */

$parameters = ['arrayfields' => &$arrayfields];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Selection of new fields
    require_once DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        foreach ($object->fields as $key => $val) {
            $search[$key] = '';
            if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
                $search[$key . '_dtstart'] = '';
                $search[$key . '_dtend']   = '';
            }
        }
        $searchAll            = '';
        $toselect             = [];
        $search_array_options = [];
        $searchCategories     = [];
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
        $massaction = '';
    }

    // Mass actions
    $objectclass = 'Control';
    $objectlabel = 'Control';
    $uploaddir   = $conf->digiquali->dir_output;

    require_once DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

    // Mass actions archive
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/list_massactions.tpl.php';
}

/*
 * View
 */

if ($mode == 'pwa') {
    $conf->dol_hide_topmenu  = 1;
    $conf->dol_hide_leftmenu = 1;
}

$title = $langs->trans(ucfirst($object->element) . 'List');
saturne_header(0,'', $title, $helpUrl ?? '', '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist');

if (!empty($fromObjectMetadata)) {
    $fromObject = $fromObjectMetadata['object'];
    $fromObject->fetch($fromId);
    saturne_get_fiche_head($fromObject, $object->element, $langs->trans(ucfirst($object->element)));

    // The list of the element is reached through its own path : fromtype is a link name, not a directory
    $backUrl  = $fromObjectMetadata['list_url'] ?: $fromType . '/list.php';
    $linkBack = '<a href="' . dol_buildpath($backUrl . '?restore_lastsearch_values=1', 1) . '">' . $langs->trans('BackToList') . '</a>';
    saturne_banner_tab($fromObject, 'fromtype=' . $fromType . '&fromid', $linkBack, 1, 'rowid', ($fromType == 'productlot' ? 'batch' : 'ref'));

    $moreUrlParameters = '&fromtype=' . $fromType . '&fromid=' . $fromId . '&mode=' . $mode;

    // Sort links, pagination and the search form all post to this very page : without these, the tab
    // loses the element it is opened from as soon as one of them is used
    $formMoreParams = ['fromtype' => $fromType, 'fromid' => $fromId];

    // The product tab shows two lists : the controls of the product itself and, below, the ones carried
    // out on its lots/serials. Naming the first one keeps them apart
    if ($fromType == 'product' && isModEnabled('productbatch')) {
        $title = $langs->trans('ControlsOnParentProduct');
    }
}

if ($fromId) {
    print '<div class="underbanner clearboth"></div>';
    print '<div class="fichehalfleft">';

    $controls = $object->fetchAll();
    if (is_array($controls) && !empty($controls)) {
        foreach ($controls as $control) {
            $control->fetchObjectLinked('','', $control->id, 'digiquali_' . $control->element, 'OR', 1, 'sourcetype', 0);
            if (!empty($control->linkedObjectsIds)) {
                if (array_key_exists($fromType, $control->linkedObjectsIds)) {
                    $linkedObjectsIds = array_values($control->linkedObjectsIds[$fromType]);
                    if (in_array($fromId, $linkedObjectsIds)) {
                        $categories = $categorie->getListForItem($control->id, $control->element);
                        if (is_array($categories) && !empty($categories)) {
                            foreach ($categories as $category) {
                                $nbBox[$category['label']] = 1;
                            }
                        }
                    }
                }
            }
        }

        if (is_array($nbBox) || is_object($nbBox)) {
            $box->loadBox();
            for ($i = 0; $i < count($nbBox); $i++) {
                $box->showBox($i,$i);
            }
        }
    }
    print '</div>';
}

if ($nbLinkableElements == 0) {
    $noticeMessage = '<a href="' . dol_buildpath('custom/digiquali/admin/sheet.php', 1) . '">' . $langs->transnoentities('MissingConfigElementTypeMessage') . '</a>';
    print saturne_show_notice($langs->transnoentities('MissingConfigElementTypeTitle'), $noticeMessage, 'error', 'notice-infos', true);
} else {
    require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php';
    require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_header.tpl.php';
    require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_input.tpl.php';
    require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_title.tpl.php';
    require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_loop_object.tpl.php';
    require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_footer.tpl.php';
}

// Controls carried out on the lots/serials of the product, grouped by warehouse : they are linked to the
// lots, not to the product, so the list above never shows them
if ($fromType == 'product' && isModEnabled('productbatch')) {
    require_once __DIR__ . '/../../core/tpl/control/control_product_lot_list.tpl.php';
}

// End of page
llxFooter();
$db->close();
