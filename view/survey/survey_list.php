<?php
/* Copyright (C) 2024-2025 EVARISK <technique@evarisk.com>
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
 * \file    view/survey/survey_list.php
 * \ingroup digiquali
 * \brief   List page for survey
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
require_once __DIR__ . '/../../class/survey.class.php';

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
$object      = new Survey($db);
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
$nbLinkableElements = 0;
$objectPosition     = 21;
$excludeFields      = [];
$objectsMetadata    = saturne_get_objects_metadata();
foreach($objectsMetadata as $objectMetadata) {
    if ($objectMetadata['conf'] == 0) {
        continue;
    }

    if (empty($fromType) || $fromType == $objectMetadata['link_name']) {
        $object->fields[$objectMetadata['post_name']] = [
            'type'        => 'integer:' . $objectMetadata['class_name'] . ':' . $objectMetadata['class_path'],
            'label'       => $langs->trans($objectMetadata['langs']),
            'enabled'     => 1,
            'position'    => $objectPosition,
            'visible'     => 2,
            'disablesort' => 1
        ];
        //@TODO minwidth100 maxwidth125 widthcentpercentminusxx css

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
        $object->fields[$signatoryInDictionary->ref] = ['label' => $signatoryInDictionary->ref, 'enabled' => 1, 'position' => $customFieldsPosition++, 'visible' => 2, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx right'];
        $excludeFields[]                             = $signatoryInDictionary->ref;
    }
}

$object->fields['question_answered']           = ['label' => 'QuestionAnswered',           'enabled' => 1, 'position' => 66,  'visible' => 2, 'css' => 'center minwidth200 maxwidth250 widthcentpercentminusxx'];
$object->fields['last_status_date']            = ['label' => 'LastStatusDate',             'enabled' => 1, 'position' => 67,  'visible' => 2, 'css' => 'center minwidth200 maxwidth300 widthcentpercentminusxx'];
$object->fields['society_attendants']          = ['label' => 'SocietyAttendants',          'enabled' => 1, 'position' => 115, 'visible' => 2, 'css' => 'minwidth300 maxwidth500 widthcentpercentminusxx'];
$object->fields['average_percentage_qestions'] = ['label' => 'AveragePercentageQuestions', 'enabled' => 1, 'position' => 220, 'visible' => 2, 'css' => 'center minwidth200 maxwidth250 widthcentpercentminusxx'];
$object->fields['verdict_object']              = ['label' => 'VerdictObject',              'enabled' => 1, 'position' => 200, 'visible' => 2, 'css' => 'center minwidth200 maxwidth250 widthcentpercentminusxx'];

$excludeFields = array_merge($excludeFields, ['question_answered', 'last_status_date', 'society_attendants', 'average_percentage_qestions', 'verdict_object']);

// Initialize array of search criterias
$searchAll = trim(GETPOST('search_all'));
$search    = [];
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
    $search[$objectsMetadata[$fromType]['post_name']] = $fromId;
    if ($fromType == 'fk_sheet') {
        $search['fk_sheet'] = $fromId;
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
            'checked'  => (($visible < 0) ? 0 : 1),
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
                $search[$key.'_dtstart'] = '';
                $search[$key.'_dtend']   = '';
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
    $objectclass = 'Survey';
    $objectlabel = 'Survey';
    $uploaddir   = $conf->digiquali->dir_output;

    require_once DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

    // Mass actions archive
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/list_massactions.tpl.php';
}

/*
 * View
 */

$title = $langs->trans(ucfirst($object->element) . 'List');
saturne_header(0,'', $title, $helpUrl ?? '', '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist');

if (!empty($fromType) && !empty($fromId)) {
    $objectsMetadata[$fromType]['object']->fetch($fromId);
    saturne_get_fiche_head($objectsMetadata[$fromType]['object'], $object->element, $langs->trans(ucfirst($object->element)));
    $linkBack = '<a href="' . dol_buildpath($fromType . '/list.php?restore_lastsearch_values=1', 1) . '">' . $langs->trans('BackToList') . '</a>';
    saturne_banner_tab($objectsMetadata[$fromType]['object'], 'fromtype=' . $fromType . '&fromid', $linkBack, 1, 'rowid', ($fromType == 'productlot' ? 'batch' : 'ref'));

    $moreUrlParameters = '&fromtype=' . $fromType . '&fromid=' . $fromId;
    $formMoreParams    = ['fromtype' => $fromType, 'fromid' => $fromId];
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

// End of page
llxFooter();
$db->close();
