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
 * \file    class/activity.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for Activity (Create/Read/Update/Delete).
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Activity
 */
class Activity extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object
     */
    public $element = 'activity';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'digiquali_activity';

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
     * @var int Does object support category module ? 0 = No, 1 = Yes
     */
    public int $isCategoryManaged = 1;

    /**
     * @var string Name of icon for activity. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'activity@digiquali' if picto is file 'img/object_activity.png'
     */
    public string $picto = 'fontawesome_fa-list_fas_#d35968';

    public const STATUS_DELETED   = -1;
    public const STATUS_VALIDATED = 1;
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
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => -2, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'           => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'       => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => -2],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => -2, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => -2],
        'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => -2, 'index' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchmulti' => 1, 'default' => 1, 'arrayofkeyval' => [1 => 'InProgress', 2 => 'Locked', 3 => 'Archived'], 'css' => 'minwidth200'],
        'label'         => ['type' => 'varchar(255)', 'label' => 'Label',            'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 1, 'searchall' => 1],
        'source'        => ['type' => 'html',         'label' => 'Source',           'enabled' => 1, 'position' => 15,  'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'viewmode' => 'badge', 'picto' => 'fas fa-user-tie'],
        'source_from'   => ['type' => 'html',         'label' => 'SourceFrom',       'enabled' => 1, 'position' => 15,  'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'viewmode' => 'badge', 'picto' => 'fas fa-user'],
        'input_data'    => ['type' => 'html',         'label' => 'InputData',        'enabled' => 1, 'position' => 15,  'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'viewmode' => 'badge', 'picto' => 'fas fa-file-import'],
        'output_data'   => ['type' => 'html',         'label' => 'OutputData',       'enabled' => 1, 'position' => 15,  'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth200', 'viewmode' => 'badge', 'picto' => 'fas fa-file-export'],
        'score'         => ['type' => 'real',         'label' => 'ActualScore',      'enabled' => 1, 'position' => 35,  'notnull' => 0, 'visible' => 2, 'viewmode' => 'badge', 'picto' => 'fas fa-star'],
        'target_score'  => ['type' => 'real',         'label' => 'TargetScore',      'enabled' => 1, 'position' => 35,  'notnull' => 0, 'visible' => 2, 'viewmode' => 'badge', 'picto' => 'fas fa-bullseye'],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => -2, 'foreignkey' => 'user.rowid'],
        'fk_element'    => ['type' => 'integer',                                'label' => 'ParentElement',                 'enabled' => 1, 'position' => 9,   'notnull' => 1, 'visible' => 1],
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
     * @var string|null Label
     */
    public ?string $label = null;

    /**
     * @var string|null Source
     */
    public ?string $source = null;

    /**
     * @var string|null Source from
     */
    public ?string $source_from = null;

    /**
     * @var string|null Input data
     */
    public ?string $input_data = null;

    /**
     * @var string|null Output data
     */
    public ?string $output_data = null;

    /**
     * @var float|null Score
     */
    public ?float $score;

    /**
     * @var float|null Target score
     */
    public ?float $target_score;

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    /**
     * @var int|string Element ID
     */
    public $fk_element;

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
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
            $this->labelStatus[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');

            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InProgress');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
        }

        $statusType = 'status' . $status;
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

        $object->context['createfromclone'] = 'createfromclone';

        $questionAndGroups = $object->fetchQuestionsAndGroups();

        $activityID = $object->create($user);
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
                    $questionOrGroup->add_object_linked('digiquali_' . $object->element, $sheetID);
                    if ($questionOrGroup instanceof Question) {
                        $questionIds[$position] = $questionOrGroup->id;
                    } else {
                        $questionGroupIds[$position] = $questionOrGroup->id;
                    }
                }
                $object->updateQuestionsAndGroupsPosition($questionIds, $questionGroupIds);
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
            return $sheetID;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public function initAsSpecimen()
    {
        global $langs;

        parent::initAsSpecimen();

        $this->ref          = 'A2024-0001';
        $this->label        = $langs->trans('ActivityLabelSpecimen');
        $this->source       = $langs->trans('ActivitySourceSpecimen');
        $this->source_from  = $langs->trans('ActivitySourceFromSpecimen');
        $this->input_data   = $langs->trans('ActivityInputDataSpecimen');
        $this->output_data  = $langs->trans('ActivityOutputDataSpecimen');
        $this->score        = 50;
        $this->target_score = 70;
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

    public static function getNbActivities($object): int
    {
        $nbActivities = 0;
        // Enable caching of object type count activity
        require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
        $cacheKey      = 'count_activities_' . $object->element . '_' . $object->id;
        $dataRetrieved = dol_getcache($cacheKey);
        if (!is_null($dataRetrieved)) {
            $nbActivities = $dataRetrieved;
        } else {
            $nbActivities = saturne_fetch_all_object_type('Activity', '', '', 0, 0, ['customsql' => 't.status = ' . Activity::STATUS_VALIDATED . ' AND t.fk_element = ' . $object->id], 'AND', false, true, false, '', ['count' => true]);
            dol_setcache($cacheKey, $nbActivities, 120); // If setting cache fails, this is not a problem, so we do not test result
        }

        return $nbActivities;
    }

    public function getActivityInfos() {
        $out = [];

        $out[$this->element]['id']      = $this->id;
        $out[$this->element]['ref']     = $this->getNomUrl(1, 'nolink', 1, '', -1, 1);
        $out[$this->element]['element'] = $this->element;

        return $out[$this->element];
    }
}

