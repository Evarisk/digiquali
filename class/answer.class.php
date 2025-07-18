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
 * \file    class/answer.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for Answer (Create/Read/Update/Delete).
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Answer.
 */
class Answer extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object.
     */
    public $element = 'answer';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiquali_answer';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string Name of icon for answer. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'answer@digiquali' if picto is file 'img/object_answer.png'.
     */
    public string $picto = 'fontawesome_fa-arrow-right_fas_#d35968';

	public const STATUS_DELETED   = -1;
	public const STATUS_VALIDATED = 1;

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
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'           => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'       => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 5],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => 1],
        'value'         => ['type' => 'text',         'label' => 'Value',            'enabled' => 1, 'position' => 80,  'notnull' => 1, 'visible' => 1],
        'position'      => ['type' => 'integer',      'label' => 'Position',         'enabled' => 1, 'position' => 90,  'notnull' => 1, 'visible' => 0],
        'correct'       => ['type' => 'boolean',      'label' => 'AnswerCorrectness','enabled' => 1, 'position' => 95,  'notnull' => 1, 'visible' => 0, 'default' => 0],
        'color'         => ['type' => 'varchar(255)', 'label' => 'Color',            'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 0],
        'pictogram'     => ['type' => 'varchar(255)', 'label' => 'Pictogram',        'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 1],
        'fk_question'   => ['type' => 'integer',      'label' => 'FkQuestion',       'enabled' => 1, 'position' => 120, 'notnull' => 1, 'visible' => 0, 'index' => 1, 'foreignkey' => 'digiquali_question.rowid'],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 130, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
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
     * @var string Value.
     */
    public string $value;

    /**
     * @var int Position.
     */
    public int $position;

    /**
     * @var bool Correct.
     */
    public bool $correct = false;

    /**
     * @var string Color.
     */
    public string $color;

    /**
     * @var string Pictogram.
     */
    public string $pictogram;

    /**
     * @var int Question ID.
     */
    public int $fk_question;

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
        $this->ref      = $this->getNextNumRef();
        $this->position = $this->getMaxPosition() + 1;
		$this->status   = $this->status ?: self::STATUS_VALIDATED;

        return parent::create($user, $notrigger);
    }

    /**
     * Returns max position of answers in question
     *
     * @return int|null answer position.
     */
    public function getMaxPosition(): ?int
    {
        $sql   = "SELECT rowid, position FROM ". MAIN_DB_PREFIX ."digiquali_answer WHERE fk_question = " . $this->fk_question . " ORDER BY position DESC LIMIT 1";
        $resql = $this->db->query($sql);

        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $positionField = 'position';
            return $obj->$positionField;
        } else {
            return 0;
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
		global $db, $langs;

		$ret   = parent::getTriggerDescription($object);
		$ret  .= $langs->transnoentities('Position') . ' : ' . $object->position . '</br>';
		$ret  .= $langs->transnoentities('Color') . ' : ' . $object->color . '</br>';
		$ret  .= (dol_strlen($object->pictogram) > 0 ? $langs->transnoentities('Pictogram') . ' : ' . $object->pictogram . '</br>' : '');
		if (!empty($object->fk_question)) {
			$question = new Question($db);
			$question->fetch($object->fk_question);

			$ret .= $langs->transnoentities('Question') . ' : ' . $question->ref . ' - ' . $question->label . '</br>';
		}
		return $ret;
	}
}
