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
 * \file    class/question_group.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for QuestionGroup (Create/Read/Update/Delete)
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for QuestionGroup.
 */
class QuestionGroup extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object
     */
    public $element = 'questiongroup';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'digiquali_questiongroup';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for control. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'control@digiquali' if picto is file 'img/object_control.png'
     */
    public string $picto = 'fontawesome_fa-folder_fas_#d35968';

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
     * 'label' the translation key
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     * 'position' is the sort order of field
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0
     * 'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created
     * 'index' if we want an index in database
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...)
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1
     * 'comment' is not used. You can store here any text of your choice. It is not used by application
     * 'validate' is 1 if you need to validate with $this->validateField()
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
	public $fields = [
        'rowid'                  => ['type' => 'integer',      'label' => 'TechnicalID',          'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1],
        'ref'                    => ['type' => 'varchar(128)', 'label' => 'Ref',                  'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1],
        'ref_ext'                => ['type' => 'varchar(128)', 'label' => 'RefExt',               'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0, 'noteditable' => 1, 'index' => 1],
        'entity'                 => ['type' => 'integer',      'label' => 'Entity',               'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'default' => 1, 'index' => 1],
        'date_creation'          => ['type' => 'datetime',     'label' => 'DateCreation',         'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'default' => 'CURRENT_TIMESTAMP', 'index' => 1],
        'tms'                    => ['type' => 'timestamp',    'label' => 'DateModification',     'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'default' => 'CURRENT_TIMESTAMP', 'index' => 1],
        'import_key'             => ['type' => 'varchar(14)',  'label' => 'ImportKey',            'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'noteditable' => 1, 'index' => 1],
        'status'                 => ['type' => 'integer',      'label' => 'Status',               'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'default' => 1, 'index' => 1],
        'label'                  => ['type' => 'varchar(255)', 'label' => 'Label',                'enabled' => 1, 'position' => 80,  'notnull' => 1, 'visible' => 1, 'noteditable' => 0, 'index' => 1],
        'description'            => ['type' => 'text',         'label' => 'Description',          'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 1, 'noteditable' => 0],
        'success_rate'           => ['type' => 'real',         'label' => 'SuccessScore',         'enabled' => 1, 'position' => 95,  'notnull' => 0, 'visible' => 1, 'help' => 'PercentageValue', 'default' => 0, 'validate' => 1, 'bounds' => ['min' => 0, 'max' => 100]],
        'fk_user_creat'          => ['type' => 'integer',      'label' => 'UserCreation',         'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1],
        'fk_user_modif'          => ['type' => 'integer',      'label' => 'UserModification',     'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0, 'noteditable' => 1, 'index' => 1],
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var string Ref ext
     */
    public $ref_ext;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var string Date creation
     */
    public $date_creation;

    /**
     * @var string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var string Label
     */
    public $label;

    /**
     * @var string Description
     */
    public $description;

    /**
     * @var float success_rate
     */
    public float $success_rate = 0;

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false = launch triggers after, true = disable triggers
     * @return int             0 < if KO, ID of created object if OK
     */
    public function create(User $user, bool $notrigger = false): int
    {
        $this->ref      = $this->getNextNumRef();
		$this->status   = $this->status ?: 1;

        $result = parent::create($user, $notrigger);

        if ($result > 0) {
            if (GETPOST('parent_group_id') > 0) {
                $this->add_object_linked('digiquali_questiongroup', GETPOST('parent_group_id'));
            } else if (GETPOST('sheet_id') > 0) {
                $sheet = new Sheet($this->db);
                $sheet->fetch(GETPOST('sheet_id'));

                $this->add_object_linked('digiquali_sheet', GETPOST('sheet_id'));

                $sheet->updateQuestionsAndGroupsPosition([], [], true);
                $sheet->call_trigger('SHEET_ADDQUESTIONGROUP', $user);

            }
        }
        return $result;

    }

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a question_group can be deleted
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function is_erasable() {
		return $this->isLinkedToOtherObjects();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a question_group is linked to another object
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function isLinkedToOtherObjects() {

		// Links between objects are stored in table element_element
		$sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql .= " WHERE fk_target = " . $this->id;
		$sql .= " AND targettype = '" . $this->table_element . "'";

		$resql = $this->db->query($sql);

		if ($resql) {
			$nbObjectsLinked = 0;
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$nbObjectsLinked++;
				$i++;
			}
			if ($nbObjectsLinked > 0) {
				return -1;
			} else {
				return 1;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

    /**
     * Return the status
     *
     * @param  int    $status ID status
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     * @return string         Label of status
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
	 * @param  User      $user    User that creates
	 * @param  int       $fromid  ID of object to clone
	 * @return int                New object created, < 0 if KO
	 * @throws Exception
	 */
	public function createFromClone(User $user, int $fromid): int
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$object = new self($this->db);

		$this->db->begin();

		$object->fetchCommon($fromid);
        $object->fetchObjectLinked('', '', $this->id, $this->table_element);

        $previousQuestions = $object->linkedObjects['digiquali_question'];

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);


		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = $this->getNextNumRef();
		}
		if (!empty($options['label'])) {
			if (property_exists($object, 'label')) {
				$object->label = $options['label'];
			}
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'status')) {
			$object->status = 1;
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result                             = $object->create($user);

		if ($result > 0) {
			if (!empty($options['categories'])) {
				$cat        = new Categorie($this->db);
				$categories = $cat->containing($fromid, 'question_group');
				if (is_array($categories) && !empty($categories)) {
					foreach ($categories as $cat) {
						$categoryIds[] = $cat->id;
					}
					$object->fetch($result);
					$object->setCategories($categoryIds);
				}
			}
            if (!empty($previousQuestions)) {
                foreach ($previousQuestions as $previousQuestion) {
                    $object->addQuestion($previousQuestion->id);
                }
            }
		} else {
			$error++;
			$this->error  = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $result;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 *  Output html form to select a third party
	 *  Note, you must use the select_company to get the component to select a third party. This function must only be called by select_company
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
	public function selectQuestionGroupList($selected = '', $htmlname = 'socid', $filter = '', $showempty = '1', $showtype = 0, $forcecombo = 0, $events = array(), $filterkey = '', $outputmode = 0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $multiple = false, $alreadyAdded = array())
	{
		$out      = '';
		$num      = 0;
		$outarray = array();

		if ($selected === '') $selected           = array();
		elseif ( ! is_array($selected)) $selected = array($selected);

		// Clean $filter that may contains sql conditions so sql code
		if (function_exists('testSqlAndScriptInject')) {
			if (testSqlAndScriptInject($filter, 3) > 0) {
				$filter = '';
			}
		}
		// On recherche les societes
		$sql  = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "digiquali_questiongroup as s";

		$sql              .= " WHERE s.entity IN (" . getEntity($this->table_element) . ")";
        $sql              .= " AND s.rowid NOT IN (";
		$sql              .= "	SELECT fk_target FROM llx_element_element WHERE targettype = 'digiquali_questiongroup'";
		$sql			  .= ")";
		if ($filter) $sql .= " AND (" . $filter . ")";

		$sql .= $this->db->order("rowid", "ASC");
		$sql .= $this->db->plimit($limit, 0);

		// Build output string
		dol_syslog(get_class($this) . "::selectQuestionList", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ( ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $events, 0);
			}

			// Construct $out and $outarray
			$out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . '>' . "\n";

			$num                  = $this->db->num_rows($resql);
			$i                    = 0;
            if ($showempty)
            {
                if ($showempty === '1') $out .= '<option value="0" selected>'. dol_escape_htmltag('&nbsp;') . '</option>';
                else $out .= '<option value="0"></option>';
            }
			if ($num) {
				while ($i < $num) {
					$obj   = $this->db->fetch_object($resql);
					$label = $obj->ref . ' - ' . dol_trunc($obj->label, 64);


					if (empty($outputmode)) {
						if (in_array($obj->rowid, $selected)) {
							$out .= '<option value="' . $obj->rowid . '" selected>' . $label . '</option>';
						} else {
							if (!empty($alreadyAdded)) {
								if (in_array($obj->rowid, $alreadyAdded)) {
									$out .= '<option disabled value="' . $obj->rowid . '">' . $label . '</option>';
								} else {
									$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
								}
							} else {
								$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
							}
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

		$this->result = array('nbofquestion_groups' => $num);

		if ($outputmode) return $outarray;
		return $out;
	}

    /**
     * Add question into question group
     *
     * @param  int $questionId ID of question
     */
    public function addQuestion($questionId) {
        global $user;

        $question = new Question($this->db);
        $question->fetch($questionId);
        $question->add_object_linked('digiquali_questiongroup', $this->id);
        $this->call_trigger('QUESTIONGROUP_ADDQUESTION', $user);
    }

    /**
     * Move questions
     */
    public function updateQuestionPosition($questionIds)
    {

        foreach ($questionIds as $position => $questionId) {
            $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'element_element';
            $sql .= ' SET position =' . $position;
            $sql .= ' WHERE fk_source = ' . $questionId;
            $sql .= ' AND sourcetype = \'digiquali_question\'';
            $sql .= ' AND fk_target = ' . $this->id;
            $sql .= ' AND targettype = \'digiquali_questiongroup\'';
            $res = $this->db->query($sql);

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
     * Get questions
     *
     * @return array
     */
    public function fetchQuestionsOrderedByPosition(?int $groupId = null)
    {
        $this->fetchObjectLinked($groupId ?? $this->id, $this->table_element, '', '', 'OR', '', 'position');

        if (!empty($this->linkedObjects['digiquali_question'])) {
            return $this->linkedObjects['digiquali_question'];
        } else {
            return [];
        }
    }

    /**
     * Get question groups
     *
     * @return array
     */
    public function fetchQuestionGroupsOrderedByPosition(?int $groupId = null)
    {
        $this->fetchObjectLinked($groupId ?? $this->id, $this->table_element, null, 'digiquali_questiongroup', 'OR', '', 'position');

        if (!empty($this->linkedObjects['digiquali_questiongroup'])) {
            return $this->linkedObjects['digiquali_questiongroup'];
        } else {
            return [];
        }
    }

    /**
     * Get id of the parent group
     *
     * @return int
     */
    public function getParentGroupId()
    {
        $this->fetchObjectLinked(null, 'digiquali_questiongroup', $this->id, 'digiquali_questiongroup', 'OR', '', 'position');

        if (isset($this->linkedObjectsIds['digiquali_questiongroup'])) {
            return intval(array_shift($this->linkedObjectsIds['digiquali_questiongroup']));
        }
        return 0;
    }

    /**
     * Return the number of questions in the group)
     * 
     * @param bool $includeSubGroups If you want to include questions of subgroups or not
     *
     * @return int
     */
    public function getNumberOfQuestions(bool $includeSubGroups = true): int
    {
        $groupQuestions = $this->fetchQuestionsOrderedByPosition();
        $numberOfQuestions = count($groupQuestions);

        if ($includeSubGroups) {
            $questionGroups = $this->fetchQuestionGroupsOrderedByPosition();

            foreach ($questionGroups as $singleQuestionGroup) {
                $numberOfQuestions += $singleQuestionGroup->getNumberOfQuestions();
            }
        }

        return $numberOfQuestions;
    }

    /**
     * Display question group in sheet card
     *
     * @return void
     */
    public function displayInSheetCard($sheetObject, $subLevel = 0)
    {
        global $langs, $db;

        $groupQuestions = $this->fetchQuestionsOrderedByPosition();
        $numberOfQuestions = $this->getNumberOfQuestions();

        $questionGroups = $this->fetchQuestionGroupsOrderedByPosition();
        

        print '<tr id="group-' . $this->id . '" class="line-row question-group" data-group-id="' . $this->getParentGroupId() . '">';
        print '<td colspan="8">';
        print '<div class="group-header" onclick="window.digiquali.sheet.toggleGroup(' . $this->id . ')" style="margin-left: calc(2rem * ' . $subLevel . ');">';
        print '<span class="group-title">' . $this->getNomUrl(1) . ' - ' . $this->label . ' (' . $numberOfQuestions . ' question' . ($numberOfQuestions > 1 ? 's' : '').')</span>';
        print '<span class="toggle-icon">+</span>';
        print '</div>';
        print '</td>';
        if ($sheetObject->status < $sheetObject::STATUS_LOCKED) {
            print '<td class="center">';
            print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $sheetObject->id . '&amp;action=unlinkQuestionGroup&questionGroupId=' . $this->id . '&token=' . newToken() . '">';
            print img_delete();
            print '</a>';
            print '</td>';
            // print '<td class="sheet-move-line ui-sortable-handle" onmousedown="window.digiquali.sheet.closeAllGroups()">';
            print '<td class="sheet-move-line ui-sortable-handle">';
            print '</td>';
        } else {
            print '<td>';
        }
        print '</tr>';

        $groupId = $this->id;
        $object = $sheetObject;
        $tdOffsetStyle = 'style="padding-left: calc(2rem + 2rem * ' . $subLevel . ' + 12px);"';
        include DOL_DOCUMENT_ROOT . '/custom/digiquali/view/sheet/sheet_addforms.tpl.php';

        if (is_array($groupQuestions) && !empty($groupQuestions)) {
            foreach ($groupQuestions as $question) {
                include DOL_DOCUMENT_ROOT . '/custom/digiquali/view/sheet/sheet_question.tpl.php';
            }            
        }

        if (is_array($questionGroups) && !empty($questionGroups)) {
            $subLevel++;
            foreach ($questionGroups as $singleGroup) {
                $singleGroup->displayInSheetCard($sheetObject, $subLevel);
            }
        }
    }

    /**
     * Calculate the total number of points for correct answers and the total possible number of points
     * (of the current group)
     *
     * @return array
     */
    public function calculatePoints(Survey $survey): array
    {
        $numberOfAnsweredQuestions = 0;
        $numberOfQuestions = 0;
        $questionGroupTotalPoints = 0;
        $questionGroupCorrectAnswersTotalPoints = 0;

        $this->fetchObjectLinked($this->id, 'digiquali_questiongroup');

        // Compute questions points
        if (isset($this->linkedObjectsIds['digiquali_question'])) {
            foreach ($this->linkedObjectsIds['digiquali_question'] as $questionId) {
                $question = new Question($this->db);
                $question->fetch($questionId);

                foreach ($survey->lines as $questionAnswer) {
                    if ($questionId == $questionAnswer->fk_question) {
                        if ($question->checkAnswerIsCorrect($questionAnswer->answer) >= 0) {
                            $questionGroupCorrectAnswersTotalPoints += $question->points;
                        }
                        if ($questionAnswer->answer !== '') {
                            $numberOfAnsweredQuestions++;
                        }
                        $questionGroupTotalPoints += $question->points;
                    }
                }
                $numberOfQuestions++;
            }
        }

        // Compute groups points
        if (isset($this->linkedObjectsIds['digiquali_questiongroup'])) {
            foreach ($this->linkedObjectsIds['digiquali_questiongroup'] as $groupId) {
                $questionGroup = new QuestionGroup($this->db);
                $questionGroup->fetch($groupId);

                [$subGroupNumberOfAnsweredQuestions, $subGroupNumberOfQuestion, $subGroupCorrectAnswersTotalPoints, $subGroupTotalPoints, $groupWithAtLeastOneIncorrectSubGroup] = $questionGroup->calculatePoints($survey);

                $numberOfAnsweredQuestions += $subGroupNumberOfAnsweredQuestions;
                $numberOfQuestions += $subGroupNumberOfQuestion;
                $questionGroupTotalPoints += $subGroupTotalPoints;
                $questionGroupCorrectAnswersTotalPoints += $subGroupCorrectAnswersTotalPoints;

                if (!$this->isCorrectFromPoints($subGroupCorrectAnswersTotalPoints, $subGroupTotalPoints)) {
                    $atLeastOneIncorrectSubGroup = true;
                }
                $atLeastOneIncorrectSubGroup = $atLeastOneIncorrectSubGroup || $groupWithAtLeastOneIncorrectSubGroup;
            }
        }

        return [$numberOfAnsweredQuestions, $numberOfQuestions, $questionGroupCorrectAnswersTotalPoints, $questionGroupTotalPoints, $atLeastOneIncorrectSubGroup];
    }

    /**
	 * To know if the rate of correct answers is bigger than the attempted success rate of the current group
	 *
	 * @return bool
	 */
	public function isCorrect(Survey $survey): bool
	{

        [$numberOfAnsweredQuestions, $numberOfQuestions, $correctPoints, $totalPoints, $atLeastOneIncorrectSubGroup] = $this->calculatePoints($survey);

        if ($atLeastOneIncorrectSubGroup) {
            return false;
        }

        return $this->isCorrectFromPoints($correctPoints, $totalPoints);
	}

    /**
	 * To know if the rate of correct answers is bigger than the attempted success rate of the current group
	 *
	 * @return bool
	 */
	public function isCorrectFromPoints($correctPoints, $totalPoints): bool
	{
        $correctAnswersRate = 0;
        if ($totalPoints > 0) {
            $correctAnswersRate = round($correctPoints / $totalPoints * 100, 2);
        }

        if ($correctAnswersRate >= $this->success_rate) {
            return true;
        }

		return false;
	}
    
    /**
     * Return a array of formatted string to print group score (in points)
     * and success rate
     * 
     * @param Survey $survey the survey on which check answers are correct or not
     * 
     * @return array
	 */
    public function getFormattedSuccessPointsAndRates(Survey $survey): array
	{
        global $langs;

        [$numberOfCorrectAnswers, $numberOfQuestions, $correctPoints, $totalPoints] = $this->calculatePoints($survey);
        $successRate = 0;
        if ($totalPoints > 0) {
            $successRate = round($correctPoints / $totalPoints * 100, 2);
        }
        $pointsResult = $correctPoints . ' / ' . $totalPoints . ' ' . strtolower(($totalPoints > 1 ? $langs->trans('Points') : $langs->trans('Point')));
        $successRateResult = $successRate . ' %' . ' (min ' . $this->success_rate . ' %)';
		return [$pointsResult, $successRateResult];
	}
}
