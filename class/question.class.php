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
 * \file    class/question.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for Question (Create/Read/Update/Delete)
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Question.
 */
class Question extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object
     */
    public $element = 'question';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'digiquali_question';

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
    public string $picto = 'fontawesome_fa-question_fas_#d35968';

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
        'rowid'                  => ['type' => 'integer',      'label' => 'TechnicalID',          'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'                    => ['type' => 'varchar(128)', 'label' => 'Ref',                  'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'                => ['type' => 'varchar(128)', 'label' => 'RefExt',               'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'                 => ['type' => 'integer',      'label' => 'Entity',               'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'          => ['type' => 'datetime',     'label' => 'DateCreation',         'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'                    => ['type' => 'timestamp',    'label' => 'DateModification',     'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'import_key'             => ['type' => 'varchar(14)',  'label' => 'ImportId',             'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'                 => ['type' => 'smallint',     'label' => 'Status',               'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 5, 'index' => 1, 'searchmulti' => 1, 'default' => 0, 'arrayofkeyval' => [1 => 'InProgress', 2 => 'Locked', 3 => 'Archived'], 'css' => 'minwidth200'],
		'type'                   => ['type' => 'varchar(128)', 'label' => 'Type',                 'enabled' => 1, 'position' => 80,  'notnull' => 1, 'visible' => 1],
		'label'                  => ['type' => 'varchar(255)', 'label' => 'Label',                'enabled' => 1, 'position' => 11,  'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'showoncombobox' => 1],
		'description'            => ['type' => 'html',         'label' => 'Description',          'enabled' => 1, 'position' => 12, 'notnull' => 0,  'visible' => 1, 'csslist' => 'tdoverflowmax300'],
		'points'            	 => ['type' => 'real',	       'label' => 'NumberOfPoints',       'enabled' => 1, 'position' => 13,  'notnull' => 0, 'visible' => 1, 'default' => 1, 'bounds' => ['min' => 0], 'validate' => 1],
		'show_photo'             => ['type' => 'boolean',      'label' => 'ShowPhoto',            'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0],
		'authorize_answer_photo' => ['type' => 'boolean',      'label' => 'AuthorizeAnswerPhoto', 'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0],
		'enter_comment'          => ['type' => 'boolean',      'label' => 'EnterComment',         'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 0],
        'photo_ok'               => ['type' => 'text',         'label' => 'PhotoOK',              'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => 3],
		'photo_ko'               => ['type' => 'text',         'label' => 'PhotoKO',              'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => 3],
        'json'                   => ['type' => 'text',         'label' => 'JSON',                 'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 0],
		'fk_user_creat'          => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 170, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
		'fk_user_modif'          => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 180, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
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
     * @var int|string Creation date
     */
    public $date_creation;

    /**
     * @var int|string Timestamp
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
     * @var string Type
     */
    public string $type = '';

	public const TYPE_UNIQUE_CHOICE = 'UniqueChoice';
	public const TYPE_MULTIPLE_CHOICES = 'MultipleChoices';
	public const TYPE_TEXT = 'Text';
	public const TYPE_PERCENTAGE = 'Percentage';
	public const TYPE_RANGE = 'Range';
	public const TYPE_OK_KO = 'OkKo';
	public const TYPE_OK_KO_TOFIX_NA = 'OkKoToFixNonApplicable';

    public const QUESTION_TYPES = [
		self::TYPE_UNIQUE_CHOICE => [
			'default_points' => 1,
			'correctable' => true,
			'only_one_correct_answer' => true,
			'answers_enable_actions' => true,
		],
		self::TYPE_MULTIPLE_CHOICES => [
			'default_points' => 1,
			'correctable' => true,
			'answers_enable_actions' => true,
		],
		self::TYPE_TEXT => [
			'default_points' => 0,
			'correctable' => false,
		],
		self::TYPE_PERCENTAGE => [
			'default_points' => 0,
			'correctable' => true,
			'bounds' => true,
		],
		self::TYPE_RANGE => [
			'default_points' => 0,
			'correctable' => true,
			'bounds' => true,
		],
		self::TYPE_OK_KO => [
			'default_points' => 1,
			'correctable' => true,
			'only_one_correct_answer' => true,
		],
		self::TYPE_OK_KO_TOFIX_NA => [
			'default_points' => 1,
			'correctable' => true,
			'only_one_correct_answer' => true,
		],
	];

    /**
     * @var string Label
     */
    public string $label;

    /**
     * @var string|null Description
     */
    public ?string $description;

    /**
     * @var float Points
     */
    public float $points;

    /**
     * @var bool|null Show photo
     */
    public ?bool $show_photo = null;

    /**
     * @var bool|null Authorize answer photo
     */
    public ?bool $authorize_answer_photo = null;

    /**
     * @var bool|null Comment
     */
    public ?bool $enter_comment = null;

    /**
     * @var string|null Photo OK
     */
    public ?string $photo_ok = '';

    /**
     * @var string|null Photo KO
     */
    public ?string $photo_ko = '';

    /**
     * @var string|null JSON
     */
    public ?string $json = '';

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
            if (GETPOST('question_group_id') > 0) {
                $questionGroup = new QuestionGroup($this->db);
                $questionGroup->fetch(GETPOST('question_group_id'));
                $questionGroup->addQuestion($this->id);
            } else if (GETPOST('sheet_id') > 0) {
               $sheet = new Sheet($this->db);
               $sheet->fetch(GETPOST('sheet_id'));
               $this->add_object_linked('digiquali_' . $sheet->element, GETPOST('sheet_id'));

               $sheet->updateQuestionsAndGroupsPosition([], [], true);

               $sheet->call_trigger('SHEET_ADDQUESTION', $user);
           }
        }

        return $result;
    }

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a question can be deleted
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	public function is_erasable() {
		return $this->isLinkedToOtherObjects();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a question is linked to another object
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
	 * @param  array     $options Options array
	 * @return int                New object created, < 0 if KO
	 * @throws Exception
	 */
	public function createFromClone(User $user, int $fromid, array $options): int
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $conf;
		$error = 0;

		$object = new self($this->db);
        $answer = new Answer($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		$oldRef = $object->ref;

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
				$categories = $cat->containing($fromid, 'question');
				if (is_array($categories) && !empty($categories)) {
					foreach ($categories as $cat) {
						$categoryIds[] = $cat->id;
					}
					$object->fetch($result);
					$object->setCategories($categoryIds);
				}
			}
			if (!empty($options['photos'])) {
				$dirFiles = $conf->digiquali->multidir_output[$object->entity ?? 1] . '/question/';
				$oldDirFiles = $dirFiles . $oldRef;
				$newDirFiles = $dirFiles . $object->ref;

				$photoOkList = dol_dir_list($oldDirFiles . '/photo_ok', 'files');
				$photoKoList = dol_dir_list($oldDirFiles . '/photo_ko', 'files');

				$photoOkThumbsList = dol_dir_list($oldDirFiles . '/photo_ok/thumbs', 'files');
				$photoKoThumbsList = dol_dir_list($oldDirFiles . '/photo_ko/thumbs', 'files');

				$photoOkPath = $newDirFiles . '/photo_ok';
				dol_mkdir($photoOkPath);
				if (is_array($photoOkList) && !empty($photoOkList)) {
					foreach ($photoOkList as $photoOk) {
						copy($photoOk['fullname'], $photoOkPath . '/' . $photoOk['name']);
					}
				}

				$photoKoPath = $newDirFiles . '/photo_ko';
				dol_mkdir($photoKoPath);
				if (is_array($photoKoList) && !empty($photoKoList)) {
					foreach ($photoKoList as $photoKo) {
						copy($photoKo['fullname'], $photoKoPath . '/' . $photoKo['name']);
					}
				}

				$photoOkThumbsPath = $newDirFiles . '/photo_ok/thumbs';
				dol_mkdir($photoOkThumbsPath);
				if (is_array($photoOkThumbsList) && !empty($photoOkThumbsList)) {
					foreach ($photoOkThumbsList as $photoOkThumbs) {
						copy($photoOkThumbs['fullname'], $photoOkThumbsPath . '/' . $photoOkThumbs['name']);
					}
				}

				$photoKoThumbsPath = $newDirFiles . '/photo_ok/thumbs';
				dol_mkdir($photoKoThumbsPath);
				if (is_array($photoKoThumbsList) && !empty($photoKoThumbsList)) {
					foreach ($photoKoThumbsList as $photoKoThumbs) {
						copy($photoKoThumbs['fullname'], $photoKoThumbsPath . '/' . $photoKoThumbs['name']);
					}
				}
			}

            $answersToClone = $answer->fetchAll('', '', 0 , 0, ['customsql' => 'fk_question = ' . $fromid]);
            if (is_array($answersToClone) && !empty($answersToClone)) {
                foreach ($answersToClone as $answerToClone) {
                    $answerToClone->fk_question = $result;
                    $answerToClone->create($user);
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
	public function selectQuestionList($selected = '', $htmlname = 'socid', $filter = '', $showempty = '1', $showtype = 0, $forcecombo = 0, $events = array(), $filterkey = '', $outputmode = 0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $multiple = false, $alreadyAdded = array())
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
		$sql .= " FROM " . MAIN_DB_PREFIX . "digiquali_question as s";

		$sql              .= " WHERE s.entity IN (" . getEntity($this->table_element) . ")";
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

		$this->result = array('nbofquestions' => $num);

		if ($outputmode) return $outarray;
		return $out;
	}

	/**
	 *	Update questions position in sheet
	 *
	 *	@param	array	$idsArray			Array containing position and ids of questions in sheet
	 */
	public function updateAnswersPosition($idsArray)
	{
		$this->db->begin();

		foreach ($idsArray as $position => $answerId) {
			$sql = 'UPDATE '. MAIN_DB_PREFIX . 'digiquali_answer';
			$sql .= ' SET position =' . $position;
			$sql .= ' WHERE fk_question = ' . $this->id;
			$sql .= ' AND rowid =' . $answerId;
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
	 * Write information of trigger description
	 *
	 * @param  Object $object Object calling the trigger
	 * @return string         Description to display in actioncomm->note_private
	 */
	public function getTriggerDescription(SaturneObject $object): string
	{
		global $langs;

		$ret   = parent::getTriggerDescription($object);
		$ret  .= $langs->transnoentities('ShowPhoto') . ' : ' . ($object->show_photo ? $langs->transnoentities('Yes') : $langs->transnoentities('No')) . '</br>';
		$ret  .= $langs->transnoentities('AuthorizeAnswerPhoto') . ' : ' . ($object->authorize_answer_photo ? $langs->transnoentities('Yes') : $langs->transnoentities('No')) . '</br>';
		$ret  .= $langs->transnoentities('EnterComment') . ' : ' . ($object->enter_comment ? $langs->transnoentities('Yes') : $langs->transnoentities('No')) . '</br>';
		$ret  .= (dol_strlen($object->photo_ok) > 0 ? $langs->transnoentities('PhotoOK') . ' : ' . $object->photo_ok . '</br>' : '');
		$ret  .= (dol_strlen($object->photo_ko) > 0 ? $langs->transnoentities('PhotoKO') . ' : ' . $object->photo_ko . '</br>' : '');

		return $ret;
	}

	/**
	 * Return the list of positions of all correct answers for this question
	 *
	 * @return array
	 */
	public function getAllCorrectAnswers(): array
	{
		// Depending question type :
		// - For UniqueChoice/MultipleChoices/OkKo/OkKoToFixNonApplicable : search correct answers for this question in llx_answer
		$answer = new Answer($this->db);
		$correctAnswers = $answer->fetchAll('', '', 0, 0, ['fk_question' => $this->id, 'correct' => 1, 'status' => 1]);
		
		if (is_array($correctAnswers)) {
			$retAnswersPositions = [];
			foreach ($correctAnswers as $correctAnswer) {
				$retAnswersPositions[] = $correctAnswer->position;
			}
			return $retAnswersPositions;
		}
	
		return [];
	}

	/**
	 * To know if all the answers given for the question are correct or not
	 * 
	 * @param mixed $answerValue
	 *
	 * @return int Return -1 if at least one answer is false, 0 for question of type text, 1 if all answers are correct
	 */
	public function checkAnswerIsCorrect(mixed $answerValue): int
	{
		$retValue = 1;
		if (in_array($this->type, [self::TYPE_PERCENTAGE, self::TYPE_RANGE])) {
			$retValue = $this->isAnswerInQuestionRange($answerValue) ? 1 : -1;
		} else if (in_array($this->type, [self::TYPE_OK_KO, self::TYPE_OK_KO_TOFIX_NA, self::TYPE_UNIQUE_CHOICE, self::TYPE_MULTIPLE_CHOICES])) {
			$correctAnswers = $this->getAllCorrectAnswers();
	
			if (is_array($correctAnswers)) {
				$listOfAnswersPositions = explode(',', $answerValue);
				foreach ($listOfAnswersPositions as $answerItemPosition) {
					if (!in_array($answerItemPosition, $correctAnswers)) {
						$retValue = -1;
						break;
					}
				}
			}
		} else {
			$retValue = 0;
		}
			
		return $retValue;
	}

	/**
	 * To know if the answer value is included in the min/max range defined for the question
	 *
	 * @return bool
	 */
	public function isAnswerInQuestionRange(mixed $answerValue): bool
	{
		$questionJson = json_decode($this->json, true);
		$questionConfig = $questionJson['config'] ?? [];

		$minAnswerValue = $questionConfig[$this->type]['answer-min-value'] ?? null;
		$maxAnswerValue = $questionConfig[$this->type]['answer-max-value'] ?? null;
		$hasMinAnswerValue = isset($minAnswerValue);
		$hasMaxAnswerValue = isset($maxAnswerValue);
		$hasMinAndMaxValues = $hasMinAnswerValue && $hasMaxAnswerValue;

		if ($hasMinAndMaxValues) {
			return $answerValue >= $minAnswerValue && $answerValue <= $maxAnswerValue;
		} else if ($hasMaxAnswerValue) {
			return $answerValue <= $maxAnswerValue;
		} else if ($hasMinAnswerValue) {
			return $answerValue >= $minAnswerValue;
		}

		return false;
	}

	/**
	 * To know if the question can have bounds or not, depending its type
	 *
	 * @return bool
	 */
	public function canHaveBounds(): bool
	{
		return self::QUESTION_TYPES[$this->type]['bounds'] ?? false;
	}

	/**
	 * To know if the answers of the question can be modified (add, update, delete)
	 *
	 * @return bool
	 */
	public function isAnswersActionsEnabled(): bool
	{
		return self::QUESTION_TYPES[$this->type]['answers_enable_actions'] ?? false;
	}

	/**
	 * To know if the question has a correction or not, depending its type
	 *
	 * @return bool
	 */
	public function isCorrectable(): bool
	{
		return self::QUESTION_TYPES[$this->type]['correctable'] ?? false;
	}

	/**
	 * To know if the question has at least one answer which is set as a correct answer
	 *
	 * @return bool
	 */
	public function hasAtLeastOneCorrectAnswer(): bool
	{
		if ($this->mustHaveOnlyOneCorrectAnswer()) {
			$answer = new Answer($this->db);
			$answerList = $answer->fetchAll('ASC', 'position', 0, 0, ['fk_question' => $this->id, 'status' => 1, 'correct' => 1]);
	
			return (is_array($answerList) && count($answerList) > 0);
		}
		return false;
	}

	/**
	 * To know if the question must have only one correct answer or not
	 *
	 * @return bool
	 */
	public function mustHaveOnlyOneCorrectAnswer(): bool
	{
		return self::QUESTION_TYPES[$this->type]['only_one_correct_answer'] ?? false;
	}

	/**
	 * To get the default number of points of the current question, depending its type
	 *
	 * @return integer Number of points
	 */
	public function getDefaultPoints(): int
	{
		return self::QUESTION_TYPES[$this->type]['default_points'] ?? 0;
	}

	/**
	 * List number of points associated to each question type
	 *
	 * @return array ['UniqueChoice' => 2, etc...]
	 */
	public static function getAllDefaultPoints(): array
	{
		return array_map(function($questionTypeConfig) {
			return ($questionTypeConfig['default_points'] ?? 0);
		}, self::QUESTION_TYPES);
	}

	/**
	 * List of question types which can have bounds
	 *
	 * @return array ['Percentage', etc...]
	 */
	public static function getQuestionTypesWithBounds(): array
	{
		$questionTypesWithBounds = [];
		foreach (self::QUESTION_TYPES as $questionTypeKey => $questionTypeValue) {
			if (isset($questionTypeValue['bounds'])) {
				$questionTypesWithBounds[] = $questionTypeKey;
			}
		}
		return $questionTypesWithBounds;
	}

	/**
	 * Return a formatted string to print question score (in points)
	 * like : 0 / 3 points
	 * 
	 * @param int $questionWithCorrectAnswer (values are those returned by Question::checkAnswerIsCorrect())
	 * 
	 * @return string
	 */
	public function formatSingleQuestionScore(int $questionWithCorrectAnswer): string
	{
		global $langs;

		return (($questionWithCorrectAnswer >= 0) ? $this->points : 0) . ' / ' . $this->points . ' ' . strtolower(($this->points > 1 ? $langs->trans('Points') : $langs->trans('Point')));
	}
}
