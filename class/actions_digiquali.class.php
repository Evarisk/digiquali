<?php
/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_digiquali.class.php
 * \ingroup digiquali
 * \brief   DigiQuali hook overload
 */

/**
 * Class ActionsDigiquali
 */
class ActionsDigiquali
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var array Errors
     */
    public array $errors = [];

    /**
     * @var string[] Warning codes (or messages)
     */
    public array $warnings = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public array $results = [];

    /**
     * @var string|null String displayed by executeHook() immediately after return
     */
    public ?string $resprints;

    /**
     * Constructor
     *
     *  @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the constructCategory function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function constructCategory(array $parameters): int
    {
        if (strpos($parameters['context'], 'category') !== false) {
            $tags = [
                'question' => [
                    'id'        => 436301001,
                    'code'      => 'question',
                    'obj_class' => 'Question',
                    'obj_table' => 'digiquali_question',
                    'label'     => 'Question'
                ],
                'sheet' => [
                    'id'        => 436301002,
                    'code'      => 'sheet',
                    'obj_class' => 'Sheet',
                    'obj_table' => 'digiquali_sheet',
                    'label'     => 'Sheet'
                ],
                'control' => [
                    'id'        => 436301003,
                    'code'      => 'control',
                    'obj_class' => 'Control',
                    'obj_table' => 'digiquali_control',
                    'label'     => 'Control'
                ],
                'survey' => [
                    'id'        => 436301004,
                    'code'      => 'survey',
                    'obj_class' => 'Survey',
                    'obj_table' => 'digiquali_survey',
                    'label'     => 'Survey'
                ],
                'questiongroup' => [
                    'id'        => 436301005,
                    'code'      => 'questiongroup',
                    'obj_class' => 'QuestionGroup',
                    'obj_table' => 'digiquali_questiongroup',
                    'label'     => 'QuestionGroup'
                ]
            ];
            $this->results = $tags;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the getElementProperties function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function getElementProperties(array $parameters): int
    {
        if (preg_match('/elementproperties|category/', $parameters['context'])) {
            $objectElements = ['question', 'questiongroup', 'sheet', 'control', 'survey'];
            if (in_array($parameters['elementType'], $objectElements)) {
                $out = [
                    'module'        => 'digiquali',
                    'table_element' => 'digiquali_' . $parameters['elementType'],
                    'classpath'     => 'custom/digiquali/class',
                ];
                $this->results = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (strpos($parameters['context'], 'categoryindex') !== false) {
            $resourcesRequired = [
                'js'  => '/custom/digiquali/js/digiquali.js'
            ];

            $out  = '<!-- Includes JS added by module digiquali -->';
            $out .= '<script src="' . dol_buildpath($resourcesRequired['js'], 1) . '"></script>';

            $this->resprints = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the hookSetManifest function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function hookSetManifest(array $parameters): int
    {
        if (strpos($_SERVER['PHP_SELF'], 'digiquali') !== false) {
            $this->resprints = dol_buildpath('custom/digiquali/manifest.json.php', 1);
            return 1; // or return 1 to replace standard code
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadata (context, etc...)
     * @param  object $object     The object to process
     * @param  string $action     Current action (if set). Generally create or edit or null
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function doActions(array $parameters, $object, string $action): int
    {
        global $conf;

        if (strpos($parameters['context'], 'categorycard') !== false) {
            require_once __DIR__ . '/../class/question.class.php';
            require_once __DIR__ . '/../class/sheet.class.php';
            require_once __DIR__ . '/../class/control.class.php';
            require_once __DIR__ . '/../class/survey.class.php';
        }

        if (strpos($parameters['context'], 'productlotcard') !== false) {
            if (isModEnabled('easyurl') && $action == 'set_easy_url_link') {
                //set_easy_url_link($object);

                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                exit;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printMainArea function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function printMainArea(array $parameters): int
    {
        global $langs;

        if (preg_match('/digiqualiview|digiqualistandardagenda|digiqualielementdocument|digiqualielementagenda/', $parameters['context'])) {
            require_once __DIR__ . '/../class/digiqualielement.class.php';

            $digiQualiElement = new DigiQualiElement($this->db);

            ob_start();
            $moreParams = [
                'moduleNameLowerCase'             => $digiQualiElement->module,
                'objectClassName'                 => 'DigiQualiElement',
                'objectElement'                   => $digiQualiElement->element,
                'objectFields'                    => $digiQualiElement->fields,
                'sideBarSecondaryNavigationTitle' => $langs->trans('DigiQualiElementNavigationTitle'),
                'sideBarSecondaryTitle'           => $langs->trans('Mapping')
            ];
            saturne_more_left_menu($moreParams);
            $this->resprints = ob_get_clean();
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneCustomHeaderFunction function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneCustomHeaderFunction(array $parameters): int
    {
        if (preg_match('/digiqualielementdocument|digiqualistandardagenda|digiqualielementagenda/', $parameters['context'])) {
            $this->results = ['loadMediaGallery' => 1, 'moreCSSOnBody' => 'sidebar-secondary-opened'];
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param  array       $parameters Hook metadata (context, etc...)
     * @param  object|null $object     Current object
     * @return int                     0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function formObjectOptions(array $parameters, ?object $object): int
    {
        global $conf, $extrafields, $langs, $user;

        if (!isset($conf->cache['objectsMetadata']) || empty($conf->cache['objectsMetadata'])) {
            require_once __DIR__ . '/../../saturne/lib/object.lib.php';
            $objectsMetadata                = saturne_get_objects_metadata();
            $conf->cache['objectsMetadata'] = $objectsMetadata;
        } else {
            $objectsMetadata = $conf->cache['objectsMetadata'];
        }

        foreach($objectsMetadata as $objectMetadata) {
            if ($objectMetadata['tab_type'] == $object->element) {
                if (strpos($parameters['context'], $objectMetadata['hook_name_card']) !== false) {
                    $picto            = img_picto('', 'fontawesome_fa-clipboard-check_fas_#d35968', 'class="pictofixedwidth"');
                    $extraFieldsNames = ['qc_frequency', 'control_history_link'];
                    foreach ($extraFieldsNames as $extraFieldsName) {
                        if (isset($extrafields->attributes[$object->table_element]['label'][$extraFieldsName])) {
                            $extrafields->attributes[$object->table_element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->table_element]['label'][$extraFieldsName]);
                        }
                    }
                }
            }
        }

        if (strpos($parameters['context'], 'productlotcard') !== false) {
            $objectB64                 = base64_encode(json_encode(['type' => $object->element, 'id' => (int) $object->id]));
            $publicControlInterfaceUrl = dol_buildpath('custom/digiquali/public/control/public_control_history.php?track_id=' . $objectB64 . '&entity=' . $conf->entity, 3);
            $setEasyUrlLinkButton      =  '';
            $assignEasyUrlButton       = '';
            if (isModEnabled('easyurl')) {
                require_once __DIR__ . '/../../easyurl/class/shortener.class.php';
                $shortener = new Shortener($this->db);
                $result    = $shortener->fetch(0, '', ' AND t.original_url = \'' . $publicControlInterfaceUrl . '\'');
                if ($result > 0) {
                    $publicControlInterfaceUrl = $shortener->short_url;
                } else {
                    if ($user->hasRight('easyurl', 'shortener', 'write')) {
                        $setEasyUrlLinkButton .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_easy_url_link&token=' . newToken() . '">';
                        $setEasyUrlLinkButton .= img_picto($langs->trans('SetEasyURLLink'), 'fontawesome_fa-redo_fas_#444', 'class="paddingright pictofixedwidth valignmiddle"') . '</a>';
                        $setEasyUrlLinkButton .= '<span>' . img_picto($langs->trans('GetEasyURLErrors'), 'fontawesome_fa-exclamation-triangle_fas_#bc9526') . '</span>';
                    }
                    if ($user->hasRight('easyurl', 'shortener', 'assign')) {
                        //@Todo assign button
                        //$assignEasyUrlButton .= dolButtonToOpenUrlInDialogPopup('assignShortener', $langs->transnoentities('AssignShortener'), '<span class="fas fa-link" title="' . $langs->trans('Assign') . '"></span>', '/custom/easyurl/view/shortener/shortener_card.php?element_type=' . $object->element . '&fk_element=' . $object->id . '&from_element=1&original_url=' . $publicControlInterfaceUrl . '&action=edit_assign', '', 'btnTitle', 'window.saturne.toolbox.checkIframeCreation();') . '</td>';
                    }
                }
            }

            $out  = '<a href="' . $publicControlInterfaceUrl . '" target="_blank" title="URL : ' . $publicControlInterfaceUrl . '"><i class="fas fa-external-link-alt paddingrightonly"></i>' . dol_trunc($publicControlInterfaceUrl) . '</a>';
            $out .= showValueWithClipboardCPButton($publicControlInterfaceUrl, 0, 'none');
//            $out .= $setEasyUrlLinkButton;
//            $out .= $assignEasyUrlButton;

            $object->array_options['options_control_history_link'] = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturnePrintFieldListLoopObject function : replacing the parent's function with the one below
     *
     * @param  array       $parameters Hook metadata (context, etc...)
     * @param  object|null $object     Current object
     * @return int                     0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListSelect(array $parameters, ?object $object = null): int
    {
        global $sortfield, $sortorder;

        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            $sql = ',COUNT(ee.fk_target) AS nb_question';
            $this->resprints = $sql;
        }

        if (preg_match('/\bcontrollist\b/', $parameters['context'])) {
            $sql = ', DATEDIFF(t.next_control_date, CURDATE()) AS days_remaining_before_next_control';
            $this->resprints = $sql;
        }

        if (preg_match('/surveylist|controllist/', $parameters['context']) && $object instanceof CommonObject) {
            $this->resprints .= $this->getLinkedElementSortSelect($object, (string) $sortfield, (string) $sortorder);
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Build the SELECT expressions that let a control / survey list be sorted on a linked element column.
     *
     * Those columns (contract, project, product, etc.) hold values coming from llx_element_element, not
     * from the listed object's own table, so ordering on them requires a correlated subquery returning
     * the linked object's name. Two aliases are produced per sorted column:
     *  - sortvalue_<post_name> the linked object name, the actual sort criteria
     *  - sortempty_<post_name> a flag keeping the rows without any linked element at the bottom
     * They are deliberately prefixed: an alias named after the field itself would land in $object-><key>
     * through setVarsFromFetchObj and feed a ref string to a column declared as integer:<Class>.
     * The flag polarity follows the requested direction, because the generic list applies one and the
     * same direction to every sort token: sorting "is empty" ascending, or "is not empty" descending,
     * both push the empty rows last.
     *
     * The expressions are emitted only for the column currently sorted on, so a list displayed without
     * sorting on a linked element keeps exactly the query it had before.
     *
     * @param  CommonObject $object    Listed object (control or survey)
     * @param  string       $sortfield Requested sort field(s), comma separated
     * @param  string       $sortorder Requested sort order(s), comma separated
     * @return string                  SQL to append to the SELECT, empty when no linked element is sorted on
     * @throws Exception
     */
    protected function getLinkedElementSortSelect(CommonObject $object, string $sortfield, string $sortorder): string
    {
        global $conf;

        if (empty($sortfield)) {
            return '';
        }

        if (!isset($conf->cache['objectsMetadata']) || empty($conf->cache['objectsMetadata'])) {
            require_once __DIR__ . '/../../saturne/lib/object.lib.php';
            $conf->cache['objectsMetadata'] = saturne_get_objects_metadata();
        }

        $sortedFields = array_map('trim', explode(',', $sortfield));
        // The direction is repeated for every token by saturne_get_title_field_of_list, so the first one
        // is the direction the empty flag will be sorted with
        $isDescending = (bool) preg_match('/^desc/i', trim((string) (explode(',', $sortorder)[0] ?? '')));

        $out      = '';
        $rendered = [];
        foreach ($conf->cache['objectsMetadata'] as $objectMetadata) {
            if (empty($objectMetadata['conf']) || !in_array('sortvalue_' . $objectMetadata['post_name'], $sortedFields, true)) {
                continue;
            }
            if (empty($objectMetadata['table_element']) || empty($objectMetadata['name_field'])) {
                continue;
            }
            // 'contrat' is registered as an alias of 'contract' and carries the same post_name
            if (isset($rendered[$objectMetadata['post_name']])) {
                continue;
            }
            $rendered[$objectMetadata['post_name']] = 1;

            $nameFields = array_map('trim', explode(',', $objectMetadata['name_field']));
            $nameSelect = count($nameFields) > 1
                ? 'CONCAT_WS(\' \', lnk.' . implode(', lnk.', $nameFields) . ')'
                : 'lnk.' . $nameFields[0];

            $subQuery  = '(SELECT ' . $nameSelect . ' FROM ' . $this->db->prefix() . $objectMetadata['table_element'] . ' AS lnk';
            $subQuery .= ' INNER JOIN ' . $this->db->prefix() . 'element_element AS eesort ON (eesort.fk_source = lnk.rowid)';
            $subQuery .= ' WHERE eesort.fk_target = t.rowid';
            $subQuery .= ' AND eesort.targettype = \'' . $this->db->escape($object->module . '_' . $object->element) . '\'';
            $subQuery .= ' AND eesort.sourcetype = \'' . $this->db->escape($objectMetadata['link_name']) . '\'';
            $subQuery .= ' ORDER BY ' . $nameSelect . ' LIMIT 1)';

            $out .= ', ' . $subQuery . ' AS sortvalue_' . $objectMetadata['post_name'];
            $out .= ', (' . $subQuery . ($isDescending ? ' IS NOT NULL' : ' IS NULL') . ') AS sortempty_' . $objectMetadata['post_name'];
        }

        return $out;
    }

    /**
     * Return the ids of the objects the generic list is about to display.
     *
     * $sqlForList is the snapshot the saturne list exposes for aggregate hooks: the whole filtered query,
     * before sorting and pagination. Wrapping it as a subquery, with the very order and limit the list
     * applies, yields exactly the rows of the current page - including when the sort references an alias
     * that only exists inside that query.
     *
     * @return int[] Ids of the page, empty when the list variables are out of reach
     */
    protected function getListPageObjectIds(): array
    {
        global $limit, $offset, $sortfield, $sortorder, $sqlForList;

        if (empty($sqlForList)) {
            return [];
        }

        $sql  = 'SELECT listpage.rowid FROM (' . $sqlForList;
        $sql .= $this->db->order($sortfield, $sortorder);
        $sql .= (!empty($limit) ? $this->db->plimit($limit + 1, $offset) : '');
        $sql .= ') AS listpage';

        $resql = $this->db->query($sql);
        if (!$resql) {
            return [];
        }

        $ids = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $ids[] = (int) $obj->rowid;
        }
        $this->db->free($resql);

        return $ids;
    }

    /**
     * Load, for a whole page of listed objects, the date of the last agenda event of each status change.
     *
     * The last_status_date column used to call ActionComm::getActions() once per status and per row, so
     * one hundred queries for a page of twenty-five. Each of them orders by datep and takes the first
     * row, and MySQL resolves part of them with an index_merge intersection on idx_actioncomm_entity -
     * an index every row matches - which costs 30ms instead of 0.3ms. One query scoped to the ids of the
     * page replaces them all and always uses idx_actioncomm_fk_element.
     *
     * Rows are read in ascending datep order and each one overwrites the previous entry of its key, so
     * the value kept is the one of the greatest datep: the same record getActions() returned.
     *
     * @param  CommonObject $object Listed object (control or survey), giving the agenda element type
     * @param  int[]        $ids    Ids to load
     * @return array<int, array<string, int>> Timestamps indexed by object id, then by agenda code
     */
    protected function loadLastStatusDates(CommonObject $object, array $ids): array
    {
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return [];
        }

        $codes = [];
        foreach (['VALIDATE', 'UNVALIDATE', 'LOCK', 'ARCHIVE'] as $code) {
            $codes[] = '\'AC_' . dol_strtoupper($object->element) . '_' . $code . '\'';
        }

        $sql  = 'SELECT fk_element, code, datec FROM ' . $this->db->prefix() . 'actioncomm';
        $sql .= ' WHERE entity IN (' . getEntity('agenda') . ')';
        $sql .= ' AND elementtype = \'' . $this->db->escape($object->element . '@' . $object->module) . '\'';
        $sql .= ' AND code IN (' . implode(', ', $codes) . ')';
        $sql .= ' AND fk_element IN (' . implode(', ', $ids) . ')';
        $sql .= ' ORDER BY datep ASC';

        $resql = $this->db->query($sql);
        if (!$resql) {
            return [];
        }

        $lastStatusDates = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $lastStatusDates[(int) $obj->fk_element][$obj->code] = $this->db->jdate($obj->datec);
        }
        $this->db->free($resql);

        // Ids without any agenda event must still count as loaded, otherwise the caller reloads them one by one
        foreach ($ids as $id) {
            if (!isset($lastStatusDates[$id])) {
                $lastStatusDates[$id] = [];
            }
        }

        return $lastStatusDates;
    }

    /**
     * Overloading the printFieldListFrom function : replacing the parent's function with the one below
     *
     * @param  array       $parameters Hook metadata (context, etc...)
     * @param  object|null $object     Current object
     * @return int                     0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListFrom(array $parameters, ?object $object): int
    {
        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            $sql = ' LEFT JOIN ' . $this->db->prefix() . 'element_element AS ee ON (t.rowid = ee.fk_source AND ee.sourcetype = "' .  $object->module . '_' . $object->element . '" AND ee.targettype = "digiquali_question")';
            $this->resprints = $sql;
        }

        if (preg_match('/surveylist|controllist/', $parameters['context'])) {
            $sql = ' LEFT JOIN ' . $this->db->prefix() . 'element_element AS ee ON (t.rowid = ee.fk_target AND ee.targettype = "' . $object->module . '_' . $object->element . '")';
            $this->resprints = $sql;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListSearch function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadata (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListSearch(array $parameters, $object = null): int
    {
        global $conf;

        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            if ($parameters['key'] == 'nb_questions') {
                return 1; // or return 1 to replace standard code
            }
        }

        if (preg_match('/surveylist|controllist/', $parameters['context'])) {
            if (!isset($conf->cache['objectsMetadata']) || empty($conf->cache['objectsMetadata'])) {
                require_once __DIR__ . '/../../saturne/lib/object.lib.php';
                $conf->cache['objectsMetadata'] = saturne_get_objects_metadata();
            }
            $objectsMetadata = $conf->cache['objectsMetadata'];
            foreach ($objectsMetadata as $objectMetadata) {
                if (empty($objectMetadata['conf'])) {
                    continue;
                }
                if ($objectMetadata['post_name'] == $parameters['key'] && (int) $parameters['val'] > 0) {
                    $sql = ' AND (ee.fk_source IN (' . $parameters['val'] . ') AND ee.sourcetype = "' . $objectMetadata['link_name'] . '")';
                    $this->resprints = $sql;
                    $conf->global->MAIN_DISABLE_FULL_SCANLIST = 1;
                    return 1; // or return 1 to replace standard code
                }
                if ($parameters['val'] === '-1') {
                    return 1; // or return 1 to replace standard code
                }
            }

            // Signatory role columns (e.g. Controller) have no real t.<role> column: filter by
            // the signatory's name (linked user or contact) through the signature table.
            $signatoriesInDictionary = $conf->cache['signatoriesInDictionary'] ?? [];
            if (is_object($object) && is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
                foreach ($signatoriesInDictionary as $signatoryInDictionary) {
                    if ($parameters['key'] === $signatoryInDictionary->ref && trim((string) $parameters['val']) !== '') {
                        $nameFilter = natural_search(['su.firstname', 'su.lastname', 'sc.firstname', 'sc.lastname'], $parameters['val'], 0, 1);
                        $sql  = ' AND EXISTS (SELECT 1 FROM ' . $this->db->prefix() . 'saturne_object_signature AS sgn';
                        $sql .= ' LEFT JOIN ' . $this->db->prefix() . 'user AS su ON (sgn.element_type = \'user\' AND sgn.element_id = su.rowid)';
                        $sql .= ' LEFT JOIN ' . $this->db->prefix() . 'socpeople AS sc ON (sgn.element_type = \'socpeople\' AND sgn.element_id = sc.rowid)';
                        $sql .= ' WHERE sgn.fk_object = t.rowid';
                        $sql .= ' AND sgn.object_type = \'' . $this->db->escape($object->element) . '\'';
                        $sql .= ' AND sgn.role = \'' . $this->db->escape($signatoryInDictionary->ref) . '\'';
                        $sql .= ' AND ' . $nameFilter . ')';
                        $this->resprints = $sql;
                        return 1; // or return 1 to replace standard code
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListWhere function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListWhere(array $parameters): int
    {
        if (strpos($parameters['context'], 'controllist') !== false) {
            if (isset($parameters['search']['verdict']) && $parameters['search']['verdict'] != '' && $parameters['search']['verdict'] == 0) {
                $sql = ' OR (t.verdict IS NULL)';
                $this->resprints = $sql;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListGroupBy function : replacing the parent's function with the one below
     *
     * @param  array      $parameters Hook metadata (context, etc...)
     * @return int                    0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListGroupBy(array $parameters): int
    {
        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            $sql = ' GROUP BY t.rowid';
            $this->resprints = $sql;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturnePrintFieldListLoopObject function : replacing the parent's function with the one below
     *
     * @param array      $parameters Hook metadata (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListHaving(array $parameters): int
    {
        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            if (!empty($parameters['search']['nb_questions']) && (int) $parameters['search']['nb_questions'] != 0) {
                $sql = ' HAVING nb_question = ' . (int) $parameters['search']['nb_questions'];
                $this->resprints = $sql;
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param  array    $parameters Hook metadata (context, etc...)
     * @return int                  0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListOption(array $parameters): int
    {
        global $conf, $extrafields, $langs, $object;

        if (!isset($conf->cache['objectsMetadata']) || empty($conf->cache['objectsMetadata'])) {
            require_once __DIR__ . '/../../saturne/lib/object.lib.php';
            $objectsMetadata = saturne_get_objects_metadata();
            $conf->cache['objectsMetadata'] = $objectsMetadata;
        } else {
            $objectsMetadata = $conf->cache['objectsMetadata'];
        }

        foreach($objectsMetadata as $objectMetadata) {
            if ($objectMetadata['tab_type'] != $object->element) {
                continue;
            }
            if (preg_match('/' . $objectMetadata['hook_name_list'] . '|projecttaskscard/', $parameters['context'])) {
                $picto            = img_picto('', 'fontawesome_fa-clipboard-check_fas_#d35968', 'class="pictofixedwidth"');
                $extraFieldsNames = ['qc_frequency', 'control_history_link'];
                foreach ($extraFieldsNames as $extraFieldsName) {
                    if (isset($extrafields->attributes[$object->table_element]['label'][$extraFieldsName])) {
                        $extrafields->attributes[$object->table_element]['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes[$object->table_element]['label'][$extraFieldsName]);
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListValue function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadata (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListValue(array $parameters): int
    {
        global $conf, $langs, $user;

        if (strpos($parameters['context'], 'product_lotlist') !== false) {
            $trackId                   = base64_encode(json_encode(['type' => $parameters['object']->element, 'id' => (int) $parameters['object']->id]));
            $publicControlInterfaceUrl = dol_buildpath('custom/digiquali/public/control/public_control_history.php?track_id=' . $trackId . '&entity=' . $conf->entity, 3);
            $setEasyUrlLinkButton      =  '';
            $assignEasyUrlButton       = '';
            if (isModEnabled('easyurl')) {
                require_once __DIR__ . '/../../easyurl/class/shortener.class.php';
                $shortener = new Shortener($this->db);
                $result    = $shortener->fetch(0, '', ' AND t.original_url = \'' . $publicControlInterfaceUrl . '\'');
                if ($result > 0) {
                    $publicControlInterfaceUrl = $shortener->short_url;
                } else {
                    if ($user->hasRight('easyurl', 'shortener', 'write')) {
                        $setEasyUrlLinkButton .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $parameters['object']->id . '&action=set_easy_url_link&token=' . newToken() . '">';
                        $setEasyUrlLinkButton .= img_picto($langs->trans('SetEasyURLLink'), 'fontawesome_fa-redo_fas_#444', 'class="paddingright pictofixedwidth valignmiddle"') . '</a>';
                        $setEasyUrlLinkButton .= '<span>' . img_picto($langs->trans('GetEasyURLErrors'), 'fontawesome_fa-exclamation-triangle_fas_#bc9526') . '</span>';
                    }
                    if ($user->hasRight('easyurl', 'shortener', 'assign')) {
                        //@Todo assign button
                        //$assignEasyUrlButton .= dolButtonToOpenUrlInDialogPopup('assignShortener', $langs->transnoentities('AssignShortener'), '<span class="fas fa-link" title="' . $langs->trans('Assign') . '"></span>', '/custom/easyurl/view/shortener/shortener_card.php?element_type=' . $parameters['object']->element . '&fk_element=' . $parameters['object']->id . '&from_element=1&original_url=' . $publicControlInterfaceUrl . '&action=edit_assign', '', 'btnTitle', 'window.saturne.toolbox.checkIframeCreation();') . '</td>';
                    }
                }
            }

            $out  = '<a href="' . $publicControlInterfaceUrl . '" target="_blank" title="URL : ' . $publicControlInterfaceUrl . '"><i class="fas fa-external-link-alt paddingrightonly"></i>' . dol_trunc($publicControlInterfaceUrl) . '</a>';
            $out .= showValueWithClipboardCPButton($publicControlInterfaceUrl, 0, 'none');
//            $out .= $setEasyUrlLinkButton;
//            $out .= $assignEasyUrlButton; ?>
            <script>
                var outJS             = <?php echo json_encode($out); ?>;
                var publicControlCell = $('.liste > tbody > tr.oddeven').find('td[data-key="product_lot.control_history_link"]').last();
                publicControlCell.html(outJS);
            </script>
            <?php
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the redirectAfterConnection function : replacing the parent's function with the one below
     *
     * @param array $parameters Hook metadata (context, etc...)
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
    */
    public function redirectAfterConnection(array $parameters): int
    {
        if (strpos($parameters['context'], 'mainloginpage') !== false) {
            if (getDolGlobalInt('DIGIQUALI_REDIRECT_AFTER_CONNECTION')) {
                $this->resprints = dol_buildpath('/custom/digiquali/digiqualiindex.php?mainmenu=digiquali', 1);
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the completeTabsHead function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    function completeTabsHead(array $parameters, $object) : int
    {
        global $conf, $langs;

        if ($object === null || !is_object($object)) {
            return 0;
        }

        if (strpos($parameters['context'], 'main') !== false) {
            if (!empty($parameters['head'])) {
                foreach ($parameters['head'] as $headKey => $headTab) {
                    if (is_array($headTab) && count($headTab) > 0) {
                        if (isset($headTab[2]) && $headTab[2] === 'control' && is_string($headTab[1]) && strpos($headTab[1], $langs->trans('Controls')) !== false && strpos($headTab[1], 'badge') === false) {
                            $object->fetchObjectLinked($object->id, $object->element, null, 'digiquali_control', 'OR', 1, 'sourcetype', 0);
                            if (isset($object->linkedObjectsIds['digiquali_control']) && !empty($object->linkedObjectsIds['digiquali_control'])) {
                                $NbControls = count($object->linkedObjectsIds['digiquali_control']);
                                $parameters['head'][$headKey][1] .= '<span class="badge marginleftonlyshort">' . $NbControls . '</span>';
                            }
                        }
                        if (isset($headTab[2]) && $headTab[2] === 'medias' && is_string($headTab[1]) && strpos($headTab[1], $langs->trans('Medias')) !== false && strpos($headTab[1], 'badge') === false) {
                            $object = $parameters['object'];

                            $object->fetchObjectLinked($object->fk_sheet, 'digiquali_sheet');
                            $questionsLinked = $object->linkedObjects;
                            $linkedMedias    = 0;
                            $confName        = 'DIGIQUALI_' . dol_strtoupper($object->element) . '_USE_LARGE_MEDIA_IN_GALLERY';

                            if (!empty($questionsLinked['digiquali_question']) && is_array($questionsLinked['digiquali_question'])) {
                                foreach ($questionsLinked['digiquali_question'] as $questionLinked) {
                                    if ($questionLinked->authorize_answer_photo > 0) {
                                        saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $questionLinked->ref, (getDolGlobalInt($confName) ? 'large' : 'medium'), '', 0, 0, 0, 200, 200, 0, 0, 0, $object->element . '/' . $object->ref . '/answer_photo/' . $questionLinked->ref, $object, '', 0, 0);
                                        $linkedMedias += $object->nbphoto;
                                    }
                                }
                            }

                            $parameters['head'][$headKey][1] .= '<span class="badge marginleftonlyshort">' . $linkedMedias . '</span>';
                        }
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneBannerTab function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadata (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneBannerTab(array $parameters, CommonObject $object): int
    {
        global $conf, $langs;

        if (preg_match('/controlcard|surveycard/', $parameters['context'])) {
            if ($conf->browser->layout == 'phone') {
                $this->resprints = '<br><div>' . img_picto('', 'fontawesome_fa-caret-square-down_far_#966EA2F2_fa-2em', 'class="toggle-object-infos pictofixedwidth valignmiddle" style="width: 35px;"') . $langs->trans('DisplayMoreInfo') . '</div>';
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneAdminDocumentData function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneAdminDocumentData(array $parameters): int
    {
        if (strpos($parameters['context'], 'digiqualiadmindocuments') !== false) {
            $types = [
                'ControlDocument' => [
                    'documentType' => 'controldocument',
                    'picto'        => 'fontawesome_fa-tasks_fas_#d35968'
                ],
                'SurveyDocument' => [
                    'documentType' => 'surveydocument',
                    'picto'        => 'fontawesome_fa-marker_fas_#d35968'
                ]
            ];
            $this->results = $types;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneAdminObjectConst function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneAdminObjectConst(array $parameters): int
    {
        if (strpos($parameters['context'], 'surveyadmin') !== false) {
            $constArray['digiquali'] = [
                'DisplayMedias' => [
                    'name'        => 'DisplayMediasSample',
                    'description' => 'DisplaySurveyMediasSampleDescription',
                    'code'        => 'DIGIQUALI_SURVEY_DISPLAY_MEDIAS',
                ],
                'UseLargeSizeMedia' => [
                    'name'        => 'UseLargeSizeMedia',
                    'description' => 'UseLargeSizeMediaDescription',
                    'code'        => 'DIGIQUALI_SURVEY_USE_LARGE_MEDIA_IN_GALLERY',
                ],
                'AutoSaveActionQuestionAnswer' => [
                    'name'        => 'AutoSaveActionQuestionAnswer',
                    'description' => 'AutoSaveActionQuestionAnswerDescription',
                    'code'        => 'DIGIQUALI_SURVEYDET_AUTO_SAVE_ACTION',
                ]
            ];
            $this->results = $constArray;

            return 1; // or return 1 to replace standard code
        }

        if (strpos($parameters['context'], 'controladmin') !== false) {
            $constArray['digiquali'] = [
                'DisplayMedias' => [
                    'name'        => 'DisplayMediasSample',
                    'description' => 'DisplayControlMediasSampleDescription',
                    'code'        => 'DIGIQUALI_CONTROL_DISPLAY_MEDIAS',
                ],
                'UseLargeSizeMedia' => [
                    'name'        => 'UseLargeSizeMedia',
                    'description' => 'UseLargeSizeMediaDescription',
                    'code'        => 'DIGIQUALI_CONTROL_USE_LARGE_MEDIA_IN_GALLERY',
                ],
                'LockControlOutdatedEquipment' => [
                    'name'        => 'LockControlOutdatedEquipment',
                    'description' => 'LockControlOutdatedEquipmentDescription',
                    'code'        => 'DIGIQUALI_LOCK_CONTROL_OUTDATED_EQUIPMENT',
                ],
                'AutoSaveActionQuestionAnswer' => [
                    'name'        => 'AutoSaveActionQuestionAnswer',
                    'description' => 'AutoSaveActionQuestionAnswerDescription',
                    'code'        => 'DIGIQUALI_CONTROLDET_AUTO_SAVE_ACTION',
                ],
                'EnablePublicControlHistory' => [
                    'name'        => 'EnablePublicControlHistory',
                    'description' => 'EnablePublicControlHistoryDescription',
                    'code'        => 'DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY',
                ],
                'ShowQcFrequencyPublicInterface' => [
                    'name'        => 'ShowQcFrequencyPublicInterface',
                    'description' => 'ShowQcFrequencyPublicInterfaceDescription',
                    'code'        => 'DIGIQUALI_SHOW_QC_FREQUENCY_PUBLIC_INTERFACE',
                ],
                'ShowLastControlFirstOnPublicHistory' => [
                    'name'        => 'ShowLastControlFirstOnPublicHistory',
                    'description' => 'ShowLastControlFirstOnPublicHistoryDescription',
                    'code'        => 'DIGIQUALI_SHOW_LAST_CONTROL_FIRST_ON_PUBLIC_HISTORY',
                ],
                'ShowAddControlButtonOnPublicInterface' => [
                    'name'        => 'ShowAddControlButtonOnPublicInterface',
                    'description' => 'ShowAddControlButtonOnPublicInterfaceDescription',
                    'code'        => 'DIGIQUALI_SHOW_ADD_CONTROL_BUTTON_ON_PUBLIC_INTERFACE',
                ],
                'ShowParentLinkedObjectOnPublicInterface' => [
                    'name'        => 'ShowParentLinkedObjectOnPublicInterface',
                    'description' => 'ShowParentLinkedObjectOnPublicInterfaceDescription',
                    'code'        => 'DIGIQUALI_SHOW_PARENT_LINKED_OBJECT_ON_PUBLIC_INTERFACE',
                ]
            ];
            $this->results = $constArray;

            return 1; // or return 1 to replace standard code
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturnePrintFieldListSearch function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object $object    Current object
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function saturnePrintFieldListSearch(array $parameters, object $object): int
    {
        global $conf;

        if (preg_match('/surveylist|controllist/', $parameters['context'])) {
            $out = [];

            if ($parameters['key'] == 'fk_sheet') {
                require_once __DIR__ . '/sheet.class.php';
                $sheet = new Sheet($this->db);

                $out[$parameters['key']] = $sheet->selectSheetList(GETPOST('fromtype') == 'fk_sheet' ? GETPOST('fromid') : $parameters['search'][$parameters['key']] ?? 0,'search_fk_sheet','s.type = "' . $object->element . '"');
            }

            $this->results = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the addMoreMassActions function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadata (context, etc...)
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     */
    public function addMoreMassActions(array $parameters): int
    {
        global $langs;

        if (strpos($parameters['context'], 'questionlist') !== false) {
            $arrayOfMassactions['prelock']           = '<span class="fas fa-lock paddingrightonly"></span>' . $langs->trans('Lock');
            $arrayOfMassactions['pre_add_questions'] = '<span class="fas fa-plus-circle paddingrightonly"></span>' . $langs->transnoentities('AddToSheet');

            $out  = '<option value="prelock" data-html="' . dol_escape_htmltag($arrayOfMassactions['prelock']) . '">' . $arrayOfMassactions['prelock'] . '</option>';
            $out .= '<option value="pre_add_questions" data-html="' . dol_escape_htmltag($arrayOfMassactions['pre_add_questions']) . '">' . $arrayOfMassactions['pre_add_questions'] . '</option>';
            $this->resprints = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the doPreMassActions function : replacing the parent's function with the one below
     *
     * @param  array    $parameters Hook metadata (context, etc...)
     * @return int                  0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function doPreMassActions(array $parameters): int
    {
        global $form, $langs;

        if (strpos($parameters['context'], 'questionlist') !== false) {
            if ($parameters['massaction'] == 'prelock') {
                $this->resprints = $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassLock'), $langs->trans('ConfirmMassLockingQuestion', count($parameters['toselect'])), 'lock', null, '', 0, 200, 500, 1);
            }

            if ($parameters['massaction'] == 'pre_add_questions') {
                require_once __DIR__ . '/sheet.class.php';
                $sheet  = new Sheet($this->db);
                $sheets = $sheet->fetchAll('', '', 0, 0, ['customsql' => 't.status = ' . Sheet::STATUS_VALIDATED]);
                if (is_array($sheets) && !empty($sheets)) {
                    $sheetArray = array_reduce($sheets, function ($carry, $sheet) {
                        $carry[$sheet->id] = $sheet->ref . ' - ' . $sheet->label;
                        return $carry;
                    }, []);
                    $formQuestion = [
                        ['type' => 'select', 'name' => 'sheet', 'label' => $langs->trans('Sheet'), 'values' => $sheetArray, 'morecss' => 'maxwidth300 maxwidth200onsmartphone']
                    ];
                    $this->resprints = $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('ConfirmMassAddQuestion'), $langs->trans('ConfirmMassAddingQuestion', count($parameters['toselect'])), 'add_questions', $formQuestion, '', 0, 200, 500, 1);
                } else {
                    setEventMessages('<a href="' . dol_buildpath('custom/digiquali/view/sheet/sheet_list.php', 1) . '">' . $langs->transnoentities('ObjectNotFound', img_picto('', $sheet->picto, 'class="paddingrightonly"') . $langs->transnoentities(ucfirst($sheet->element))) . '</a>', [], 'warnings');
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneSetVarsFromFetchObj function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object $object    Current object
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneSetVarsFromFetchObj(array $parameters, object $object): int
    {
        global $conf;

        if (preg_match('/surveylist|controllist/', $parameters['context'])) {
            // Load Saturne libraries
            require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

            // Load DigiQuali libraries
            require_once __DIR__ . '/sheet.class.php';

            $signatory = new SaturneSignature($this->db, 'digiquali', $object->element);
            $sheet     = new Sheet($this->db);

            $object->fetchLines();
            // This hook serves both the control and the survey list, so the target type must follow the
            // current object: a hard-coded 'digiquali_control' finds no link at all for a survey
            $object->fetchObjectLinked('', '', $object->id, 'digiquali_' . $object->element);

            // A failed fetch() leaves the object as it was, so an unfetched sheet must not reach the cache
            if ($sheet->fetch($object->fk_sheet) > 0) {
                $sheet->fetchObjectLinked($object->fk_sheet, 'digiquali_' . $sheet->element, null, '', 'OR', 1, 'position');
                $conf->cache['sheet'] = $sheet;
            } else {
                $conf->cache['sheet'] = null;
            }

            $filter      = ['customsql' => 'fk_object = ' . $object->id . ' AND status > 0 AND object_type = "' . $object->element . '"'];
            $signatories = $signatory->fetchAll('', 'role', 0, 0, $filter);

            $conf->cache['signatories'] = $signatories;
            $conf->cache['contact']     = [];
            $conf->cache['user']        = [];
            $conf->cache['thirdparty']  = [];
            if (is_array($signatories) && !empty($signatories)) {
                foreach ($signatories as $signatory) {
                    $contact = null; // An unresolved contact must not leak from the previous signatory
                    // fetch user or contact depending on the element type of the signatory
                    if ($signatory->element_type == 'user') {
                        $userTmp = new User($this->db);
                        $userTmp->fetch($signatory->element_id);
                        // fetch contact if user has one linked
                        if ($userTmp->contact_id > 0) {
                            $contact = new Contact($this->db);
                            $contact->fetch($userTmp->contact_id);
                        }
                        $conf->cache['user'][$signatory->role][$signatory->id] = $userTmp;
                    } elseif ($signatory->element_type == 'socpeople') {
                        $contact = new Contact($this->db);
                        $contact->fetch($signatory->element_id);
                    }
                    if (!empty($contact->fk_soc)) {
                        $thirdparty = new Societe($this->db);
                        $thirdparty->fetch($contact->fk_soc);
                        $conf->cache['contact'][$signatory->role][$signatory->id] = $contact;
                        $conf->cache['thirdparty'][$signatory->id]                = $thirdparty;
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturnePrintFieldListLoopObject function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @param  object $object    Current object
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function saturnePrintFieldListLoopObject(array $parameters, object $object): int
    {
        global $conf, $langs, $db;

        if (strpos($parameters['context'], 'questionlist') !== false) {
            $out = [];

            if ($parameters['key'] == 'type') {
                $out[$parameters['key']] = $object->showOutputField($parameters['val'], $parameters['key'], $langs->trans($object->{$parameters['key']}), '');
            }

            $this->results = $out;
        }

        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            $out = [];

            if ($parameters['key'] == 'nb_questions') {
                $allQuestions               = $object->fetchAllQuestions();
                $out[$parameters['key']]    = is_array($allQuestions) ? count($allQuestions) : 0;
            }

            if ($parameters['key'] == 'photo') {
                $out[$parameters['key']] = saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$object->entity] . '/sheet/' . $object->ref . '/photos/', 'small', 0, 0, 0, 0, 50, 50, 0, 0, 0, 'sheet/' . $object->ref . '/photos/', $object, 'photo', 0, 0);
            }

            $this->results = $out;
        }

        if (preg_match('/surveylist|controllist/', $parameters['context'])) {
            $out = [];

            if ($parameters['key'] == 'fk_sheet') {
                $out[$parameters['key']] = isset($conf->cache['sheet']) ? $conf->cache['sheet']->getNomUrl(1, '', 0, 'maxwidth200onsmartphone maxwidth300', -1, 1) : '';
            }

            // Linked element columns (contract, project, product, etc.) are fed by llx_element_element.
            // Every linked type must be scanned: an object linked to both a contract and a project has
            // to fill both columns, not only the one of the first type returned by fetchObjectLinked()
            $objectsMetadata = $conf->cache['objectsMetadata'] ?? [];
            if (!empty($object->linkedObjects) && is_array($objectsMetadata)) {
                foreach ($objectsMetadata as $objectMetadata) {
                    if (empty($objectMetadata['conf']) || $parameters['key'] != $objectMetadata['post_name']) {
                        continue;
                    }
                    $linkedObjects = $object->linkedObjects[$objectMetadata['link_name']] ?? [];
                    if (empty($linkedObjects)) {
                        continue;
                    }
                    $links = [];
                    foreach ($linkedObjects as $linkedObject) {
                        $links[] = $linkedObject->getNomUrl(1);
                    }
                    $out[$parameters['key']] = implode('<br>', $links);
                }
            }

            if ($parameters['key'] == 'days_remaining_before_next_control') {
                if (dol_strlen($object->next_control_date) > 0) {
                    $nextControl          = (int) round(($object->next_control_date - dol_now('tzuser'))/(3600 * 24));
                    $nextControlDateColor = $object->getNextControlDateColor();
                    $out[$parameters['key']] = '<div class="wpeo-button" style="background-color: ' . $nextControlDateColor .'; border-color: ' . $nextControlDateColor . ' ">' . $nextControl . '</div>';
                }
            }

            if ($parameters['key'] == 'verdict') {
                $verdictColor            = $object->{$parameters['key']} == 1 ? 'green' : ($object->{$parameters['key']} == 2 ? 'red' : 'grey');
                $out[$parameters['key']] = '<div class="wpeo-button button-' . $verdictColor . '">' . $object->fields['verdict']['arrayofkeyval'][(!empty($object->{$parameters['key']})) ? $object->{$parameters['key']} : 0] . '</div>';
            }

            if ($parameters['key'] == 'question_answered') {
                $NbQuestion  = 0;
                $questionIds = isset($conf->cache['sheet']) ? ($conf->cache['sheet']->linkedObjectsIds['digiquali_question'] ?? []) : [];
                if (is_array($questionIds) && !empty($questionIds)) {
                    $NbQuestion = count($questionIds);
                    $NbAnswer   = 0;
                    if (is_array($object->lines) && !empty($object->lines)) {
                        foreach ($object->lines as $objectLine) {
                            if (dol_strlen($objectLine->answer) > 0) {
                                $NbAnswer++;
                            }
                        }
                    }
                    $out[$parameters['key']]  = $NbAnswer . '/' . $NbQuestion;
                    $out[$parameters['key']] .= ($NbQuestion == $NbAnswer && $object->status == $object::STATUS_DRAFT ? img_picto($langs->transnoentities('ObjectReadyToValidate', dol_strtolower($langs->transnoentities(ucfirst($object->element)))), 'check') : '');
                }
            }

            if ($parameters['key'] == 'last_status_date') {
                if (!isset($conf->cache['lastStatusDatesIds'])) {
                    $conf->cache['lastStatusDatesIds'] = $this->getListPageObjectIds();
                    $conf->cache['lastStatusDates']    = $this->loadLastStatusDates($object, $conf->cache['lastStatusDatesIds']);
                }
                if (!in_array($object->id, $conf->cache['lastStatusDatesIds'], true)) {
                    // The page ids could not be determined, so load this row alone rather than show nothing
                    $conf->cache['lastStatusDatesIds'][] = $object->id;
                    $conf->cache['lastStatusDates']     += $this->loadLastStatusDates($object, [$object->id]);
                }

                $out[$parameters['key']] = '';
                $actionCommCode          = ['VALIDATE' => 'ValidationDate', 'UNVALIDATE' => 'ReopeningDate', 'LOCK' => 'LockingDate', 'ARCHIVE' => 'ArchivingDate'];
                foreach ($actionCommCode as $code => $date) {
                    $lastActionDate = $conf->cache['lastStatusDates'][$object->id]['AC_' . dol_strtoupper($object->element) . '_' . $code] ?? 0;
                    if ($lastActionDate > 0) {
                        $out[$parameters['key']] .= $langs->trans($date) . '<br>' . dol_print_date($lastActionDate, 'dayhour') . '<br>';
                    }
                }
            }

            if ($parameters['key'] == 'average_percentage_questions' || $parameters['key'] == 'verdict_object') {
                $questions = isset($conf->cache['sheet']) ? ($conf->cache['sheet']->linkedObjects['digiquali_question'] ?? []) : [];
                if (is_array($questions) && !empty($questions)) {
                    $questions = array_column($questions, null, 'id');
                }

                $answers = [];
                if (is_array($object->lines) && !empty($object->lines)) {
                    foreach ($object->lines as $objectLine) {
                        if (!isset($questions[$objectLine->fk_question]) || $questions[$objectLine->fk_question]->type !== 'Percentage') {
                            continue; // Skip questions the sheet no longer carries, and non-percentage ones
                        }
                        if (!is_numeric($objectLine->answer)) {
                            continue; // An unanswered question holds '', which array_sum() cannot add
                        }
                        $answers[] = (float) $objectLine->answer;
                    }
                }

                $mean = 0;
                if (!empty($answers)) {
                    $mean = array_sum($answers) / count($answers);
                }

                if ($parameters['key'] == 'average_percentage_questions') {
                    $out[$parameters['key']] = round($mean, 2) . ' %';
                } elseif ($parameters['key'] == 'verdict_object') {
                    $isCorrect = $object->isCorrect();
                    $out[$parameters['key']] = '<span class="wpeo-button button-' . ($isCorrect ? 'green' : 'red') . ' badge-status' . '">' . ($isCorrect ? $langs->transnoentities('OK') : $langs->transnoentities('KO')) . '</span>';
                }
            }

            $signatoriesInDictionary = $conf->cache['signatoriesInDictionary'];
            if (is_array($signatoriesInDictionary) && !empty($signatoriesInDictionary)) {
                $users       = $conf->cache['user'];
                $contacts    = $conf->cache['contact'];
                $signatories = $conf->cache['signatories'];
                foreach ($signatoriesInDictionary as $signatoryInDictionary) {
                    if ($parameters['key'] == $signatoryInDictionary->ref) {
                        if (is_array($signatories) && !empty($signatories)) {
                            $out[$parameters['key']] = '';
                            foreach ($signatories as $signatory) {
                                if ($signatory->role != $signatoryInDictionary->ref) {
                                    continue;
                                }
                                switch ($signatory->attendance) {
                                    case 1:
                                        $cssButton = '#0d8aff';
                                        $userIcon  = 'fa-user-clock';
                                        break;
                                    case 2:
                                        $cssButton = '#e05353';
                                        $userIcon  = 'fa-user-slash';
                                        break;
                                    default:
                                        $cssButton = '#47e58e';
                                        $userIcon  = 'fa-user';
                                        break;
                                }
                                // Only the roles holding a signatory are cached, so both lookups can miss
                                $roleUsers    = $users[$signatory->role] ?? [];
                                $roleContacts = $contacts[$signatory->role] ?? [];

                                $signatoryName = '';
                                if (!empty($roleUsers[$signatory->id])) {
                                    $signatoryName = $roleUsers[$signatory->id]->getNomUrl(1, '', 0, 0, 24, 1);
                                } elseif (!empty($roleContacts[$signatory->id])) {
                                    $signatoryName = $roleContacts[$signatory->id]->getNomUrl(1);
                                }
                                if (dol_strlen($signatoryName) == 0) {
                                    continue; // Neither the user nor the contact could be loaded
                                }

                                $out[$parameters['key']] .= $signatoryName . ' - ' . $signatory->getLibStatut(3);
                                $out[$parameters['key']] .= ' - <i class="fas ' . $userIcon . '" style="color: ' . $cssButton . '"></i><br>';
                            }
                        }
                    }
                }
            }

            if ($parameters['key'] == 'society_attendants') {
                $thirdparties = $conf->cache['thirdparty'];
                if (is_array($thirdparties) && !empty($thirdparties)) {
                    $out[$parameters['key']]  = '';
                    $alreadyAddedThirdParties = [];
                    foreach ($thirdparties as $thirdparty) {
                        if (!empty($thirdparty->id) && !in_array($thirdparty->id, $alreadyAddedThirdParties)) {
                            $out[$parameters['key']] .= $thirdparty->getNomUrl(1) . '<br>';
                        }
                        $alreadyAddedThirdParties[] = $thirdparty->id;
                    }
                }
            }

            $this->results = $out;
        } elseif (preg_match('/controldetlist/', $parameters['context'])) {
            $out = [];

            if ($parameters['key'] == 'tasks') {
                $sqlGetTasks = "SELECT DISTINCT pt.rowid, pt.ref
                        	FROM ".MAIN_DB_PREFIX."projet_task as pt
                        	INNER JOIN ".MAIN_DB_PREFIX."element_element as dt
                        		ON dt.fk_source = ".$object->id."
                        		AND dt.sourcetype = '".$object->element."'
                        		AND dt.targettype = 'project_task'
                        	WHERE dt.fk_target = pt.rowid";

                $resqlTasks = $db->query($sqlGetTasks);
                if ($resqlTasks) {
                    $task = new Task($db);
                    while ($objTask = $db->fetch_object($resqlTasks)) {
                        $task->fetch($objTask->rowid);
                        $out[$parameters['key']] .= $task->getNomUrl() . '<br>';
                    }
                }
            } elseif ($parameters['key'] == 'question_type') {
                $question = new Question($db);

                if ($object->fk_question) {
                    $question->fetch($object->fk_question);
                    $out[$parameters['key']] = $langs->trans($question->type);
                }
            } elseif ($parameters['key'] == 'answer') {
                if ($object->answer) {
                    $answer = new Answer($db);
                    $res = $answer->fetch(0, '', ' AND t.position ='.$object->answer.' AND t.fk_question = '.$object->fk_question);
                    $out[$parameters['key']] = ($res != -1 && $answer->value ? $answer->value : '').($object->answer ? ' <span class="opacitymedium" title="value">('.$object->answer.')</span>' : '');
                }
            }

            $this->results = $out;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the addSearchEntry function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addSearchEntry(array $parameters): int
    {
        global $langs, $user;

        if (strpos($parameters['context'], 'searchform') !== false) {
            $position      = 0;
            $moduleNum     = 436301;
            $searchEntries = [];
            $objects       = ['question' => 'question', 'sheet' => 'list', 'control' => 'tasks', 'survey' => 'marker'];
            foreach ($objects as $objectName => $picto) {
                if ($user->hasRight('digiquali', $objectName, 'read')) {
                    $position += 10;
                    $searchEntries['searchinto' . $objectName] = [
                        'position' => $moduleNum . sprintf('%02d', $position),
                        'img'      => 'fontawesome_fa-' . $picto . '_fas_#d35968',
                        'label'    => $langs->trans(ucfirst($objectName)),
                        'text'     => img_picto('', 'fontawesome_fa-' . $picto . '_fas_#d35968', 'class="pictofixedwidth"') . $langs->trans(ucfirst($objectName)),
                        'url'      => dol_buildpath('custom/digiquali/view/' . $objectName . '/' . $objectName . '_list.php?mainmenu=digiquali', 1) . (!empty($parameters['search_boxvalue']) ? '&search_all=' . urlencode($parameters['search_boxvalue']) : '')
                    ];
                }
            }

            $this->results = $searchEntries;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneExtendGetObjectsMetadata function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneExtendGetObjectsMetadata(array $parameters): int
    {

        //@todo faire les autres object

        /*
            sheet prob sur fk_skeet/fatal sur sheet
            control problème
        */
        $objects = ['question' => 'comments', 'answer' => 'arrow-right', /*'sheet' => 'list', */ 'control' => 'tasks', 'survey' => 'marker'];
        foreach ($objects as $objectName => $picto) {
            $objectsMetadata['digiquali_' . $objectName] = [
                'mainmenu'       => 'digiquali',
                'leftmenu'       => '',
                'langs'          => ucfirst($objectName),
                'langfile'       => 'digiquali@digiquali',
                'picto'          => 'fontawesome_fa-' . $picto . '_fas_#d35968',
                'color'          => '#d35968',
                'class_name'     => ucfirst($objectName),
                'post_name'      => 'fk_' . $objectName,
                'link_name'      => 'digiquali_' . $objectName,
                'tab_type'       => $objectName,
                'table_element'  => 'digiquali_' . $objectName,
                'name_field'     => 'ref, label',
                'label_field'    => 'label',
                'hook_name_card' => $objectName . 'list',
                'hook_name_list' => $objectName . 'card',
                'create_url'     => 'custom/digiquali/view/' . $objectName . '/' . $objectName . '_card.php?action=create',
                'class_path'     => 'custom/digiquali/class/' . $objectName . '.class.php',
                'lib_path'       => 'custom/digiquali/lib/digiquali_' . $objectName . '.lib.php'
            ];
        }

        // objects specificataions
        $objectsMetadata['digiquali_answer']['create_url'] = '';

        $this->results = $objectsMetadata;

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the saturneMoreObjectsMetadata function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneMoreObjectsMetadata(array $parameters): int
    {
        if (preg_match('/surveycard|surveylist|digiqualiindex|sheetadmin|controlcard|controllist|sheetcard/', $parameters['context'])) {
            $confCode = 'DIGIQUALI_SHEET_LINK_' . dol_strtoupper($parameters['objectType']);
            $moreObjectsMetadata = [
                'code'       => $confCode,
                'conf'       => getDolGlobalString($confCode),
                'name'       => 'Link' . ucfirst($parameters['objectType']),
                'description'=> 'Link' . ucfirst($parameters['objectType']) . 'Description',
            ];
            $this->results = $moreObjectsMetadata;
        }

        return 0; // or return 1 to replace standard code
    }
}
