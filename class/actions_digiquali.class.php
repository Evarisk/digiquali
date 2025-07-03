<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
     * @param  array        $parameters Hook metadata (context, etc...)
     * @param  CommonObject $object     The object to process
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function constructCategory(array $parameters, &$object): int
    {
        if (strpos($parameters['context'], 'category') !== false) {
            $tags = [
                'question' => [
                    'id'        => 436301001,
                    'code'      => 'question',
                    'obj_class' => 'Question',
                    'obj_table' => 'digiquali_question',
                ],
                'sheet' => [
                    'id'        => 436301002,
                    'code'      => 'sheet',
                    'obj_class' => 'Sheet',
                    'obj_table' => 'digiquali_sheet',
                ],
                'control' => [
                    'id'        => 436301003,
                    'code'      => 'control',
                    'obj_class' => 'Control',
                    'obj_table' => 'digiquali_control',
                ],
                'survey' => [
                    'id'        => 436301004,
                    'code'      => 'survey',
                    'obj_class' => 'Survey',
                    'obj_table' => 'digiquali_survey',
                ]
            ];

            $this->results = $tags;
        }

        return 0; // or return 1 to replace standard code
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
        if (preg_match('/digiqualiview|digiqualistandardagenda|digiqualielementdocument|digiqualielementagenda/', $parameters['context'])) {
            ob_start();
            saturne_more_left_menu('digiquali', 'DigiQualiElement');
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

        require_once __DIR__ . '/../../saturne/lib/object.lib.php';

        $objectsMetadata = saturne_get_objects_metadata();
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
                require_once DOL_DOCUMENT_ROOT . '/custom/easyurl/class/shortener.class.php';
                $shortener = new Shortener($this->db);
                $result    = $shortener->fetch('', '', ' AND t.original_url = \'' . $publicControlInterfaceUrl . '\'');
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
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printFieldListSelect(array $parameters): int
    {
        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            $sql = ',COUNT(ee.fk_target) AS nb_question';
            $this->resprints = $sql;
        }

        return 0; // or return 1 to replace standard code
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
    public function printFieldListSearch(array $parameters): int
    {
        global $conf;

        if (preg_match('/\bsheetlist\b/', $parameters['context'])) {
            if ($parameters['key'] == 'nb_questions') {
                return 1; // or return 1 to replace standard code
            }
        }

        if (preg_match('/surveylist|controllist/', $parameters['context'])) {
            require_once __DIR__ . '/../../saturne/lib/object.lib.php';

            $objectsMetadata = saturne_get_objects_metadata();
            foreach($objectsMetadata as $objectMetadata) {
                if ($objectMetadata['conf'] == 0) {
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
        global $extrafields, $langs, $object;

        require_once __DIR__ . '/../../saturne/lib/object.lib.php';

        $objectsMetadata = saturne_get_objects_metadata();
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
                require_once DOL_DOCUMENT_ROOT . '/custom/easyurl/class/shortener.class.php';
                $shortener = new Shortener($this->db);
                $result    = $shortener->fetch('', '', ' AND t.original_url = \'' . $publicControlInterfaceUrl . '\'');
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
        global $langs;

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

            $conf->cache['objectsMetadata'] = saturne_get_objects_metadata();

            $object->fetchLines();
            $object->fetchObjectLinked('', '', $object->id, 'digiquali_control');

            $sheet->fetch($object->fk_sheet);
            $sheet->fetchObjectLinked($object->fk_sheet, 'digiquali_' . $sheet->element, null, '', 'OR', 1, 'position');
            $conf->cache['sheet'] = $sheet;

            $filter      = ['customsql' => 'fk_object = ' . $object->id . ' AND status > 0 AND object_type = "' . $object->element . '"'];
            $signatories = $signatory->fetchAll('', 'role', 0, 0, $filter);

            $conf->cache['signatories'] = $signatories;
            $conf->cache['contact']     = [];
            $conf->cache['user']        = [];
            $conf->cache['thirdparty']  = [];
            if (is_array($signatories) && !empty($signatories)) {
                foreach ($signatories as $signatory) {
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
        global $conf, $langs;

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
                $out[$parameters['key']]  = 0;
                $object->fetchObjectLinked($object->id, 'digiquali_' . $object->element, null, '', 'OR', 1, 'position', 0);
                if (isset($object->linkedObjectsIds['digiquali_question']) && is_array($object->linkedObjectsIds['digiquali_question'])) {
                    $out[$parameters['key']] = count($object->linkedObjectsIds['digiquali_question']);
                }
            }

            if ($parameters['key'] == 'photo') {
                $out[$parameters['key']] = saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$object->entity] . '/sheet/' . $object->ref . '/photos/', 'small', 0, 0, 0, 0, 50, 50, 0, 0, 0, 'sheet/' . $object->ref . '/photos/', $object, 'photo', 0, 0);
            }

            $this->results = $out;
        }

        if (preg_match('/surveylist|controllist/', $parameters['context'])) {
            $out = [];

            if ($parameters['key'] == 'fk_sheet') {
                $out[$parameters['key']] = $conf->cache['sheet']->getNomUrl(1, '', 0, 'maxwidth200onsmartphone maxwidth300', -1, 1);
            }

            if (!empty($object->linkedObjects)) {
                $linkedObjectType = key($object->linkedObjects);
                $objectsMetadata  = $conf->cache['objectsMetadata'];
                foreach ($objectsMetadata as $objectMetadata) {
                    if ($objectMetadata['conf'] == 0 || $objectMetadata['link_name'] != $linkedObjectType) {
                        continue;
                    }
                    if ($parameters['key'] == $objectMetadata['post_name']) {
                        $out[$parameters['key']] = $object->linkedObjects[$objectMetadata['link_name']][key($object->linkedObjects[$objectMetadata['link_name']])]->getNomUrl(1);
                    }
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
                $questionIds = $conf->cache['sheet']->linkedObjectsIds['digiquali_question'] ?? [];
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
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

                $actionComm = new ActionComm($this->db);

                $out[$parameters['key']] = '';
                $actionCommCode          = ['VALIDATE' => 'ValidationDate', 'UNVALIDATE' => 'ReopeningDate', 'LOCK' => 'LockingDate', 'ARCHIVE' => 'ArchivingDate'];
                foreach ($actionCommCode as $code => $date) {
                    $lastAction     = $actionComm->getActions(0, $object->id, $object->element . '@' . $object->module, ' AND a.code = "AC_' . dol_strtoupper($object->element) . '_' . $code . '"', 'a.datep', 'DESC', 1);
                    $lastActionDate = (!empty($lastAction) && isset($lastAction[0]->datec)) ? $lastAction[0]->datec : 0;
                    if ($lastActionDate > 0) {
                        $out[$parameters['key']] .= $langs->trans($date) . '<br>' . dol_print_date($lastActionDate, 'dayhour') . '<br>';
                    }
                }
            }

            if ($parameters['key'] == 'average_percentage_questions' || $parameters['key'] == 'verdict_object') {
                $questions = $conf->cache['sheet']->linkedObjects['digiquali_question'];
                if (is_array($questions) && !empty($questions)) {
                    $questions = array_column($questions, null, 'id');
                }

                $answers = [];
                if (is_array($object->lines) && !empty($object->lines)) {
                    foreach ($object->lines as $objectLine) {
                        if ($questions[$objectLine->fk_question]->type !== 'Percentage') {
                            continue; // Skip non-percentage questions
                        }
                        $answers[] = $objectLine->answer;
                    }
                }

                $mean = 0;
                if (!empty($answers)) {
                    $mean = array_sum($answers) / count($answers);
                }

                if ($parameters['key'] == 'average_percentage_questions') {
                    $out[$parameters['key']] = round($mean, 2) . ' %';
                } elseif ($parameters['key'] == 'verdict_object') {
                    $out[$parameters['key']] = '<span class="wpeo-button button-' . ($mean > $object->success_rate ? 'green' : 'red') . ' badge-status' . '">' . ($mean > $object->success_rate ? $langs->transnoentities('OK') : $langs->transnoentities('KO')) . '</span>';
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
                                        break;
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
                                if (is_array($users[$signatory->role]) && !empty($users[$signatory->role])) {
                                    $out[$parameters['key']] .= $users[$signatory->role][$signatory->id]->getNomUrl(1, '', 0, 0, 24, 1);
                                } elseif (is_array($contacts[$signatory->role]) && !empty($contacts[$signatory->role])) {
                                    $out[$parameters['key']] .= $contacts[$signatory->role][$signatory->id]->getNomUrl(1);
                                }
                                if ((is_array($users[$signatory->role]) && !empty($users[$signatory->role])) || (is_array($contacts[$signatory->role]) && !empty($contacts[$signatory->role]))) {
                                    $out[$parameters['key']] .= ' - ' . $signatory->getLibStatut(3);
                                    $out[$parameters['key']] .= ' - <i class="fas ' . $userIcon . '" style="color: ' . $cssButton . '"></i><br>';
                                }
                            }
                        }
                    }
                }
            }

            if ($parameters['key'] == 'society_attendants') {
                $thirdparties = $conf->cache['thirdparty'];
                if (is_array($thirdparties) && !empty($thirdparties)) {
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
