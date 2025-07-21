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
 * \file    class/sheet.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for Sheet (Create/Read/Update/Delete).
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Sheet.
 */
class Sheet extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object.
     */
    public $element = 'sheet';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiquali_sheet';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for sheet. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'sheet@digiquali' if picto is file 'img/object_sheet.png'.
     */
    public string $picto = 'fontawesome_fa-list_fas_#d35968';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     * 'label' the translation key.
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     * 'position' is the sort order of field.
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     * 'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     * 'index' if we want an index in database.
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     * 'comment' is not used. You can store here any text of your choice. It is not used by application.
     * 'validate' is 1 if you need to validate with $this->validateField()
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = [
        'rowid'               => ['type' => 'integer',      'label' => 'TechnicalID',        'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => -2, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'                 => ['type' => 'varchar(128)', 'label' => 'Ref',                'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'             => ['type' => 'varchar(128)', 'label' => 'RefExt',             'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => -2],
        'entity'              => ['type' => 'integer',      'label' => 'Entity',             'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => -2, 'index' => 1],
        'date_creation'       => ['type' => 'datetime',     'label' => 'DateCreation',       'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'                 => ['type' => 'timestamp',    'label' => 'DateModification',   'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => -2],
        'import_key'          => ['type' => 'varchar(14)',  'label' => 'ImportId',           'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => -2, 'index' => 0],
        'status'              => ['type' => 'smallint',     'label' => 'Status',             'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchmulti' => 1, 'default' => 1, 'arrayofkeyval' => [1 => 'InProgress', 2 => 'Locked', 3 => 'Archived'], 'css' => 'minwidth200'],
        'type'                => ['type' => 'select',       'label' => 'Type',               'enabled' => 1, 'position' => 65,  'notnull' => 1, 'visible' => 1, 'arrayofkeyval' => ['control' => 'Control', 'survey' => 'Survey']],
        'label'               => ['type' => 'varchar(255)', 'label' => 'Label',              'enabled' => 1, 'position' => 11,  'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200'],
        'description'         => ['type' => 'html',         'label' => 'Description',        'enabled' => 1, 'position' => 15,  'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200'],
        'element_linked'      => ['type' => 'text',         'label' => 'ElementLinked',      'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => -2],
        'photo'               => ['type' => 'text',         'label' => 'Photo',              'enabled' => 1, 'position' => 95,  'notnull' => 0, 'visible' => -2, 'disablesearch' => 1, 'disablesort' => 1],
        'success_rate'        => ['type' => 'real',         'label' => 'SuccessScore',       'enabled' => 1, 'position' => 35,  'notnull' => 0, 'visible' => 2, 'help' => 'PercentageValue'],
        'mandatory_questions' => ['type' => 'text',         'label' => 'MandatoryQuestions', 'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 0],
        'fk_user_creat'       => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'       => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => -2, 'foreignkey' => 'user.rowid']
    ];

    /**
     * @var int ID.
     */
    public int $rowid;

    /**
     * @var string Ref.
     */
    public $ref;

    /**
     * @var string Ref ext.
     */
    public $ref_ext;

    /**
     * @var int Entity.
     */
    public $entity;

    /**
     * @var int|string Creation date.
     */
    public $date_creation;

    /**
     * @var int|string Timestamp.
     */
    public $tms;

    /**
     * @var string Import key.
     */
    public $import_key;

    /**
     * @var int Status.
     */
    public $status;

    /**
     * @var string|null Type
     */
    public ?string $type = 'control';

    /**
     * @var string Label.
     */
    public string $label;

    /**
     * @var string|null Description.
     */
    public ?string $description;

    /**
     * @var string Element linked json.
     */
    public string $element_linked = '';

    /**
     * @var string|null Photo path
     */
    public ?string $photo = '';

    /**
     * @var float|null Success rate
     */
    public ?float $success_rate;

    /**
     * @var string Mandatory questions.
     */
    public ?string $mandatory_questions = '{}';

    /**
     * @var int User ID.
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID.
     */
    public $fk_user_modif;

    /**
     * Constructor.
     *
     * @param DoliDb $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Create object into database.
     *
     * @param  User $user      User that creates.
     * @param  bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, ID of created object if OK.
     */
    public function create(User $user, bool $notrigger = false): int
    {
        $this->ref                 = $this->getNextNumRef();
        $this->status              = $this->status ?: 1;
		$this->mandatory_questions = isset($this->mandatory_questions) ? $this->mandatory_questions : '{}';

        return parent::create($user, $notrigger);
    }

    /**
     * Return the status.
     *
     * @param  int    $status ID status.
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto.
     * @return string         Label of status.
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            $this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
            $this->labelStatus[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');

            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
            $this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_LOCKED) {
            $statusType = 'status4';
        }
        if ($status == self::STATUS_ARCHIVED) {
            $statusType = 'status8';
        }
        if ($status == self::STATUS_DELETED) {
            $statusType = 'status9';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * Clone an object into another one
     *
     * @param   User      $user   User that creates
     * @param   int       $fromID ID of object to clone
     * @return  mixed             New object created, <0 if KO
     * @throws  Exception
     */
    public function createFromClone(User $user, int $fromID): int
    {
        global $user;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $error = 0;

        $object = new self($this->db);
        $this->db->begin();

        // Load source object
        $object->fetchCommon($fromID);

        // Reset some properties
        unset($object->fk_user_creat);
        unset($object->import_key);

        // Clear fields
        if (property_exists($object, 'ref')) {
            $object->ref = '';
        }
        if (property_exists($object, 'date_creation')) {
            $object->date_creation = dol_now();
        }
        if (property_exists($object, 'status')) {
            $object->status = self::STATUS_VALIDATED;
        }

        $object->context = 'createfromclone';

        $questionAndGroups = $object->fetchQuestionsAndGroups();

        $sheetID = $object->create($user);
        if ($sheetID > 0) {
            // Categories
            $categoryIds = [];
            $category    = new Categorie($this->db);
            $categories  = $category->containing($fromID, 'sheet');
            if (is_array($categories) && !empty($categories)) {
                foreach($categories as $category) {
                    $categoryIds[] = $category->id;
                }
                $object->setCategories($categoryIds);
            }

            $questionIds = [];
            $questionGroupIds = [];

            if (is_array($questionAndGroups) && !empty($questionAndGroups)) {
                foreach ($questionAndGroups as $position => $questionOrGroup) {
                    // Clone questions/questiongroups because one element is linked to only one parent
                    if ($questionOrGroup instanceof Question) {
                        $clonedQuestion = new Question($this->db);
                        $clonedQuestion->id = $clonedQuestion->createFromClone($user, $questionOrGroup->id, []);
                        $clonedQuestion->add_object_linked('digiquali_' . $object->element, $sheetID);
                        $questionIds[$position+1] = $clonedQuestion->id;
                    } else {
                        $clonedQuestionGroup = new QuestionGroup($this->db);
                        $clonedQuestionGroup->id = $clonedQuestionGroup->createFromClone($user, $questionOrGroup->id, []);
                        $clonedQuestionGroup->add_object_linked('digiquali_' . $object->element, $sheetID);
                        $questionGroupIds[$position+1] = $clonedQuestionGroup->id;
                    }
                    // $object->updateQuestionsAndGroupsPosition(null, null, true);
                }
                $object->updateQuestionsAndGroupsPosition($questionIds, $questionGroupIds);
                
            }
        } else {
            $error++;
            $this->error  = $object->error;
            $this->errors = $object->errors;
        }

        // End
        if (!$error) {
            $this->db->commit();
            return $sheetID;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a sheet can be deleted
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function is_erasable() {
		require_once __DIR__ .'/control.class.php';

		$control = new Control($this->db);

		$controls = $control->fetchAll( '', '', 0, 0, ['customsql' => 't.fk_sheet= ' . $this->id . ' AND t.status >= 0']);
		if (is_array($controls) && !empty($controls)) {
			$result = -1;
		} else {
			$result = 1;
		}

		return $result;
	}

	/**
	 *  Output html form to select a third party.
	 *  Note, you must use the select_company to get the component to select a third party. This function must only be called by select_company.
	 *
	 * @param string $selected   Preselected type
	 * @param string $htmlname   Name of field in form
	 * @param string $filter     Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
	 * @param string $showempty  Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
	 * @param int    $showtype   Show third party type in combolist (customer, prospect or supplier)
	 * @param int    $forcecombo Force to use standard HTML select component without beautification
	 * @param array  $events     Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 * @param string $filterkey  Filter on key value
	 * @param int    $outputmode 0=HTML select string, 1=Array
	 * @param int    $limit      Limit number of answers
	 * @param string $morecss    Add more css styles to the SELECT component
	 * @param string $moreparam  Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 * @param bool   $multiple   add [] in the name of element and add 'multiple' attribut
	 * @return       string      HTML string with
	 * @throws Exception
	 */
	public function selectSheetList($selected = '', $htmlname = 'fk_sheet', $filter = '', $showempty = '1', $showtype = 0, $forcecombo = 0, $events = array(), $filterkey = '', $outputmode = 0, $limit = 0, $morecss = 'maxwidth500 widthcentpercentminusxx', $moreparam = '', $multiple = false)
	{
		// phpcs:enable
		global $conf, $user, $langs;

		$out      = '';
		$num      = 0;
		$outarray = array();

		if ($selected === '') $selected           = array();
		elseif ( ! is_array($selected)) $selected = array($selected);

		// On recherche les societes
		$sql  = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "digiquali_sheet as s";

		$sql              .= " WHERE s.entity IN (" . getEntity($this->table_element) . ")";
		if ($filter) $sql .= " AND (" . $filter . ")";
		if ($moreparam > 0 ) {
			$children = $this->fetchDigiriskElementFlat($moreparam);
			if ( ! empty($children) && $children > 0) {
				foreach ($children as $key => $value) {
					$sql .= " AND NOT s.rowid =" . $key;
				}
			}
			$sql .= " AND NOT s.rowid =" . $moreparam;
		}

		$sql .= $this->db->order("rowid", "ASC");
		$sql .= $this->db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this) . "::selectSheetList", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ( ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, 0);
			}

			// Construct $out and $outarray
			$out .= '<select id="' . $htmlname . '" class="minwidth200 flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . '>' . "\n";

			if ($showempty) {
				$out .= '<option value="-1">&nbsp;</option>';
			}

			$num                  = $this->db->num_rows($resql);
			$i                    = 0;

			if ($num) {
				while ($i < $num) {
					$obj   = $this->db->fetch_object($resql);
					$label = $obj->ref . ' - ' . $obj->label;


					if (empty($outputmode)) {
						if (in_array($obj->rowid, $selected)) {
							$out .= '<option value="' . $obj->rowid . '" selected>' . $label . '</option>';
						} else {
							$out .= '<option value="' . $obj->rowid . '"'.(($obj->status == 2) ? '' : 'disabled').'>' . $label . '</option>';
						}
					} else {
						array_push($outarray, array('key' => $obj->rowid, 'value' => $label, 'label' => $label));
					}

					$i++;
					if (($i % 10) == 0) $out .= "\n";
				}
			}
			$out .= '</select>' . "\n";
		} else {
			dol_print_error($this->db);
		}

		$this->result = array('nbofsheets' => $num);

		if ($outputmode) return $outarray;
		return $out;
	}

    /**
     * Update questions position in sheet
     *
     * @param array $questionIds Array containing position and ids of questions in sheet
     */
    public function updateQuestionsPosition(array $questionIds)
    {
        $this->db->begin();

        $questionIds = array_values($questionIds);
        for ($position = 0; $position < count($questionIds); $position++) {
            $sql  = 'UPDATE '. MAIN_DB_PREFIX . 'element_element';
            $sql .= ' SET position = ' . $position;
            $sql .= ' WHERE fk_source = ' . $this->id;
            $sql .= ' AND sourcetype = \'digiquali_sheet\'';
            $sql .= ' AND fk_target = ' . $questionIds[$position];
            $sql .= ' AND targettype = \'digiquali_question\'';
            $res  = $this->db->query($sql);

            if (!$res) {
                $error++;
            }
        }
        if ($error) {
            $this->db->rollback();
        } else {
            $this->db->commit();
        }
    }

    /**
     * Update questions position in sheet
     *
     * @param array $questionIds Array containing position and ids of questions and group questions in sheet
     */
    public function updateQuestionsAndGroupsPosition(?array $questionIds, ?array $questionGroupIds, $reindexLast = false, ?int $fk_source = null, string $sourcetype = 'digiquali_sheet')
    {
        $this->db->begin();

        if (is_null($fk_source)) {
            $fk_source = $this->id;
        }

        if ($reindexLast) {
            $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'element_element';
            $sql .= ' SET position = ( SELECT (COALESCE(MAX(position), 0) + 1) FROM llx_element_element WHERE fk_source = '. (int) $fk_source .' AND sourcetype = \'' . $this->db->escape($sourcetype) . '\' )';
            $sql .= ' WHERE fk_source = ' . $fk_source;
            $sql .= ' AND sourcetype = \'' . $this->db->escape($sourcetype) . '\'';
            $sql .= ' AND (targettype = "digiquali_question" OR targettype = "digiquali_questiongroup")';
            $sql .= ' AND position IS NULL';

            $res = $this->db->query($sql);

            if (!$res) {
                $error++;
            }
        }

        if (!empty($questionIds)) {
            foreach($questionIds as $position => $questionId) {
                $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'element_element';
                $sql .= ' SET position = ' . $position;
                $sql .= ' WHERE fk_source = ' . $this->id;
                $sql .= ' AND sourcetype = "digiquali_sheet"';
                $sql .= ' AND fk_target = ' . $questionId;
                $sql .= ' AND targettype = "digiquali_question"';

                $res = $this->db->query($sql);

                if (!$res) {
                    $error++;
                }
            }
        }

        if (!empty($questionGroupIds)) {
            foreach($questionGroupIds as $position => $questionGroupId) {
                $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'element_element';
                $sql .= ' SET position = ' . $position;
                $sql .= ' WHERE fk_source = ' . $this->id;
                $sql .= ' AND sourcetype = "digiquali_sheet"';
                $sql .= ' AND fk_target = ' . $questionGroupId;
                $sql .= ' AND targettype = "digiquali_questiongroup"';

                $res = $this->db->query($sql);

                if (!$res) {
                    $error++;
                }
            }
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }

    }



    /**
     * Return all questions of sheet with their groups
     *
     * @param array $question Array containing questions
     */
    public function fetchAllQuestions() {
        $questionAndGroups = $this->fetchQuestionsAndGroups($this->id, 'digiquali_sheet', true);
        $questions = [];
        if (is_array($questionAndGroups) && !empty($questionAndGroups)) {
            foreach($questionAndGroups as $questionOrGroup) {
                if ($questionOrGroup->element == 'question') {
                    $questions[] = $questionOrGroup;
                }
            }
        }

        return $questions;
    }

    /**
     * Fetch all questions and groups of sheet
     *
     * @return array Array containing questions and groups
     */
    public function fetchQuestionsAndGroups(?int $sourceId = null, string $sourceType = 'digiquali_sheet', bool $recursive = false) {

        require_once __DIR__ . '/question.class.php';
        require_once __DIR__ . '/questiongroup.class.php';

        $sql = 'SELECT ee.fk_target, ee.targettype, ee.position';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'element_element as ee';
        $sql .= ' WHERE ee.fk_source = ' . ($sourceId ?? $this->id);
        $sql .= ' AND ee.sourcetype = "' . $this->db->escape($sourceType) . '"';
        $sql .= ' AND (ee.targettype = "digiquali_question" OR ee.targettype = "digiquali_questiongroup")';
        $sql .= ' ORDER BY ee.position ASC';

        $res = $this->db->query($sql);
        $questionAndGroups = [];
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $question = new Question($this->db);
                $questionGroup = new QuestionGroup($this->db);

                if ($obj->targettype == 'digiquali_question') {
                    $question->fetch($obj->fk_target);
                    $questionAndGroups[] = $question;
                } else {
                    $questionGroup->fetch($obj->fk_target);
                    $questionAndGroups[] = $questionGroup;
                    if ($recursive) {
                        $questionAndGroups = array_merge($questionAndGroups, $this->fetchQuestionsAndGroups($questionGroup->id, 'digiquali_questiongroup', true));
                    }
                }
            }
        }
        return $questionAndGroups;

    }
	/**
	 * Write information of trigger description
	 *
	 * @param  Object $object Object calling the trigger
	 * @return string         Description to display in actioncomm->note_private
	 */
	public function getTriggerDescription(SaturneObject $object): string
	{
		global $langs;

		$linkedElement = json_decode($object->element_linked, true);

		$ret  = parent::getTriggerDescription($object);
		$ret .= $langs->transnoentities('ElementLinked') . ' : ';

		if (is_array($linkedElement) && !empty($linkedElement)) {
			foreach ($linkedElement as $objectType => $active) {
				$objectTypeUppercase = ucfirst($objectType);

				$ret .= $langs->transnoentities($objectTypeUppercase) . ' ';
			}
		} else {
			$ret .= $langs->transnoentities('NoData');
		}
		$ret .= '</br>';

		return $ret;
	}

    public function getQuestionAndGroupsTree($typeSelected = 'sheet', $idSelected = 0, $parentGroupId = 0)
    {
        global $langs, $moduleNameLowerCase, $conf;


        require_once __DIR__ . '/question.class.php';
        require_once __DIR__ . '/questiongroup.class.php';

        $numberingModules = [
            'question' => $conf->global->DIGIQUALI_QUESTION_ADDON,
            'questiongroup' => $conf->global->DIGIQUALI_QUESTIONGROUP_ADDON,
        ];

        list($modQuestion, $modQuestionGroup) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

        $questionAndGroups = $this->fetchQuestionsAndGroups();
        $questionGroupCardUrl = dol_buildpath('/custom/digiquali/view/questiongroup/questiongroup_card.php', 1);
        $questionCardUrl = dol_buildpath('/custom/digiquali/view/question/question_card.php', 1);
        $sheetCardUrl = dol_buildpath('/custom/digiquali/view/sheet/sheet_card.php', 1);

        $out = '<div id="id-container" class="id-container question-and-group-tree">';
        $out .= '<input type="hidden" name="token" value="'. newToken() . '"/>';
        $out .= '<input type="hidden" id="questionGroupCardUrl" value="'. $questionGroupCardUrl . '" />';
        $out .= '<input type="hidden" id="questionCardUrl" value="'. $questionCardUrl . '" />';
        $out .= '<input type="hidden" id="sheetCardUrl" value="'. $sheetCardUrl . '" />';

        $out .= '<div class="side-nav">';
        $out .= '  <div id="id-left">';
        $out .= '    <div class="nav-wrapper wpeo-wrap">';
        $out .= '      <div class="navigation-container">';
        $out .= '      <a href="'. $sheetCardUrl . '?id=' . $this->id . '" class="sheet-item-link">';
        $out .= '        <div class="sheet-header '. ($typeSelected == 'sheet' ? 'selected' : '') .'" data-id="'. $this->id .'">';
        $out .= '            <span class="icon fas fa-list fa-fw"></span>';
        $out .= '            <div class="title">&nbsp;' . $this->label .'</div>';
        $out .= '            <div class="add-object-container">';
        $out .= '              <a id="newGroup" href="' . $questionGroupCardUrl . '?action=create&sheet_id='. $this->id . '">';
        $out .= '                <div class="wpeo-button button-square-40 button-secondary wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="' . $langs->trans('NewQuestionGroup') . '">';
        $out .= '                  <strong>'. $modQuestionGroup->prefix .'</strong><span class="button-add animated fas fa-plus-circle"></span>';
        $out .= '                </div>';
        $out .= '              </a>';
        $out .= '              <a id="newQuestion" href="' . $questionCardUrl . '?action=create&sheet_id='. $this->id . '">';
        $out .= '                <div class="wpeo-button button-square-40 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="' . $langs->trans('NewQuestion') . '">';
        $out .= '                  <strong>'. $modQuestion->prefix .'</strong><span class="button-add animated fas fa-plus-circle"></span>';
        $out .= '                </div>';
        $out .= '              </a>';
        $out .= '            </div>';
        $out .= '        </div>';


        $out .= '      </a>';

        if (!empty($questionAndGroups)) {

            $out .= '        <ul class="question-list">';
            foreach ($questionAndGroups as $questionOrGroup) {
                if ($questionOrGroup->element == 'questiongroup') {
                    $questionsInGroup = $questionOrGroup->fetchQuestionsOrderedByPosition();
                    $groupHasQuestions = !empty($questionsInGroup);
                    $out .= '  <li class="group-item ' . ($typeSelected == 'questiongroup' && $idSelected == $questionOrGroup->id ? 'selected' : '') . '" data-id="' . $questionOrGroup->id . '">';
                    $out .= '    <span class="icon fas ' . ($groupHasQuestions ? 'fa-chevron-right' : '') . ' fa-fw toggle-group-in-tree" style="margin-right: 10px;"></span>';
                    $out .= '    <span class="icon fas fa-folder fa-2x"></span>';
                    $out .= '    <a href="' . $questionGroupCardUrl . '?id=' . $questionOrGroup->id . '&sheet_id=' . $this->id . '" class="group-item-link">';
                    $out .= '      <div class="title-container">';
                    $out .= '        <span class="ref">' . $questionOrGroup->ref . '</span>';
                    $out .= '        <span class="label">' . $questionOrGroup->label . '</span>';
                    $out .= '      </div>';
                    $out .= '      <div class="add-object-container">';
                    $out .= '        <a id="newQuestion" href="' . $questionCardUrl . '?action=create&sheet_id=' . $this->id . '">';
                    $out .= '          <div class="wpeo-button button-square-30 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="' . $langs->trans('NewQuestion') . '">';
                    $out .= '            <strong>' . $modQuestion->prefix . '</strong><span class="button-add animated fas fa-plus-circle"></span>';
                    $out .= '          </div>';
                    $out .= '        </a>';
                    $out .= '      </div>';
                    $out .= '    </a>';
                    $out .= '  </li>';

                    $out .= '  </li>';

                    if ($groupHasQuestions) {
                        $out .= '  <ul class="sub-questions collapsed">';
                        foreach ($questionsInGroup as $q) {
                            $out .= '    <li class="question-item '. ($typeSelected == 'question' && $idSelected == $q->id && $parentGroupId == $questionOrGroup->id ? 'selected' : '') .'" data-id="'. $q->id .'" data-parent-id="'. $q->getParentGroupId().'">';
                            $out .= '      <span class="icon fas fa-question fa-2x"></span>';
                            $out .= '      <a href="'. $questionCardUrl . '?id=' . $q->id . '&sheet_id='. $this->id .'&question_group_id='. $questionOrGroup->id .'" class="question-item-link">';
                            $out .= '        <div class="title-container">';
                            $out .= '            <span class="ref">' . $q->ref . '</span>';
                            $out .= '            <span class="label">' . $q->label . '</span>';
                            $out .= '        </div>';
                            $out .= '      </a>';
                            $out .= '    </li>';
                        }
                        $out .= '  </ul>';
                    }
                } else {
                    $out .= '  <li class="question-item '. ($typeSelected == 'question' && $idSelected == $questionOrGroup->id ? 'selected' : '') .'" data-id="'. $questionOrGroup->id .'" data-parent-id="0">';
                    $out .= '    <span class="icon fas fa-question fa-2x" ></span>';
                    $out .= '    <a href="'. $questionCardUrl . '?id=' . $questionOrGroup->id . '&sheet_id='. $this->id .'" class="question-item-link">';
                    $out .= '      <div class="title-container">';
                    $out .= '          <span class="ref">' . $questionOrGroup->ref . '</span>';
                    $out .= '          <span class="label">' . $questionOrGroup->label . '</span>';
                    $out .= '      </div>';
                    $out .= '    </a>';
                    $out .= '  </li>';
                }
            }
            $out .= '        </ul>';

            $out .= '<script>
                        $(document).ready(function() {
                            if (localStorage.maximized == "false") {
                                $("#id-left").attr("style", "display:none !important");
                            }

                            var container = document.querySelector(".navigation-container");
                            if (container) {
                                var selectedEl = container.querySelector(".group-item.selected, .question-item.selected");
                                if (selectedEl) {
                                    selectedEl.scrollIntoView({ behavior: "smooth", block: "center" });
                                }
                            }
                        });
                    </script>';
        }

        $out .= '      </div>';
        $out .= '    </div>';
        $out .= '  </div>';
        $out .= '</div>';

        $out .= '</div>';
        return $out;
    }

    public function setLocked(User $user, int $notrigger = 0): int
    {
        $questionsAndGroups = $this->fetchQuestionsAndGroups();

        if (is_array($questionsAndGroups) && !empty($questionsAndGroups)) {
            foreach($questionsAndGroups as $questionOrGroup) {
                if ($questionOrGroup->status != $questionOrGroup::STATUS_LOCKED) {
                    $questionOrGroup->setLocked($user, $notrigger);
                }
            }
        }

        return parent::setLocked($user, $notrigger);
    }


    /**
     * Show answer repartition
     *
     * @param Question $question
     * @param array $answers Array of answers
     * @param array $questionAnswerStats Array of answer stats
     *
     * @return void
     */
    public function showAnswerRepartition(Question $question, array $answers, array $questionAnswerStats): void
    {
        global $langs;

        require_once __DIR__ . '/../../saturne/class/saturnedashboard.class.php';

        switch ($question->type) {
            case 'UniqueChoice':
            case 'OkKo':
            case 'OkKoToFixNonApplicable':
            case 'MultipleChoices':
                if (!empty($answers)) {
                    $data = [];
                    $labels = [];

                    foreach ($answers as $a) {
                        $count = $questionAnswerStats[$question->id][$a->position]['nb_answers'] ?? 0;
                        if ($count > 0) {
                            $data[] = [$a->value, $count];
                            $labels[] = ['label' => $a->value, 'color' => $a->color];
                        }
                    }

                    if (!empty($data)) {
                        $uniqueKey = 'q_' . $question->id . '_pie';
                        $fileName = $uniqueKey . '.png';
                        $fileUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=digiquali&file=' . $fileName;

                        $graph = new DolGraph();
                        $graph->SetData($data);
                        $graph->SetDataColor(array_column($labels, 'color'));
                        $graph->SetType(['pie']);
                        $graph->SetWidth(120);
                        $graph->SetHeight(120);
                        $graph->setShowLegend(0);
                        $graph->draw($fileName, $fileUrl);
                        print $graph->show();
                    } else {
                        print '<span class="opacitymedium">' . $langs->trans('NoData') . '</span>';
                    }
                }
                break;
            case 'Percentage':
                $averagePercent = 0;

                if (!empty($questionAnswerStats[$question->id])) {
                    $answerCounter = 0;
                    $answerSum = 0;

                    foreach ($questionAnswerStats[$question->id] as $answerPercent) {
                        $answerCounter++;
                        $answerSum += $answerPercent['percentage'];
                    }
                    $averagePercent = $answerCounter > 0 ? $answerSum / $answerCounter : 0;
                }

                print '<div class="percentage-bar-container">';
                print '<div class="percentage-bar-background">';
                print '<div class="percentage-bar-fill" style="width: ' . $averagePercent . '%;"></div>';
                print '</div>';
                print '<div class="percentage-bar-label">' . $langs->trans("Average") . ' : ' . round($averagePercent) . '%</div>';
                print '</div>';
                break;

            case 'Text':
                break;

            case 'Range':
                $average = 0;
                $count = 0;
                foreach ($questionAnswerStats[$question->id] as $questionAnswer) {
                    if (isset($questionAnswer['range'])) {
                        $average += $questionAnswer['range'];
                        $count++;
                    }
                }

                $average = $count > 0 ? $average / $count : 0;

                print '<div class="range-bar-container">';
                print '<div class="range-average-indicator">';
                print '<div class="range-average-value">';
                print '<i class="fa fa-bullseye" aria-hidden="true"></i> ' . round($average, 1);
                print '</div>';
                print '<div class="range-average-label">' . $langs->trans("AverageValue") . '</div>';
                print '</div>';
                print '</div>';
                break;

            default:
                print '<span class="opacitymedium">' . $langs->trans('UnsupportedQuestionType') . '</span>';
                break;
        }
    }


    /**
     * Get average verdict
     *
     * @param array $controls Array of controls
     *
     * @return float
     */
    public function getAverageVerdict(array $controls): float
    {

        $controlsNumber = count($controls);

        if ($controlsNumber > 0) {
            $verdictSum = 0;

            foreach ($controls as $control) {
                if (!empty($control->verdict)) {
                    $verdictSum += $control->verdict;
                }
            }

            return $verdictSum / $controlsNumber;
        }
        return 0;
    }

    /**
     * Display question groups and questions in sheet card
     *
     * @return void
     */
    public function displayGroupsAndQuestions($questionsAndGroups)
    {
        global $langs;

        if (is_array($questionsAndGroups) && !empty($questionsAndGroups)) {
            $positionPath = 1;
            foreach ($questionsAndGroups as $questionOrGroup) {
                $object = $this;
                include DOL_DOCUMENT_ROOT . '/custom/digiquali/view/sheet/sheet_questiongroup.tpl.php';
                $positionPath++;
            }
        }
    }

    /**
     * Indicates if we want to display tree (with questions and groups/subgroups) on the sheet card
     */
    public function displayTree(): bool
    {
        return (getDolGlobalBool('DIGIQUALI_DISABLE_TREE_DISPLAY') === false);
    }
}

