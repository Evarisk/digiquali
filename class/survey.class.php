<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    class/survey.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for Survey (Create/Read/Update/Delete)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Survey
 */
class Survey extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object
     */
    public $element = 'survey';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'digiquali_survey';

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
     * @var string Name of icon for survey. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'survey@digiquali' if picto is file 'img/object_survey.png'
     */
    public string $picto = 'fontawesome_fa-marker_fas_#d35968';

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
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'           => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'       => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2, 'positioncard' => 10],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 5, 'index' => 1, 'default' => 0, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'Validated', 2 => 'Locked', 3 => 'Archived']],
        'note_public'   => ['type' => 'html',         'label' => 'NotePublic',       'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0],
        'note_private'  => ['type' => 'html',         'label' => 'NotePrivate',      'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'photo'         => ['type' => 'text',         'label' => 'Photo',            'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 0],
        'success_rate'  => ['type' => 'real',         'label' => 'SuccessScore',     'enabled' => 1, 'position' => 35,  'notnull' => 0, 'visible' => 2, 'help' => 'PercentageValue'],
        'track_id'      => ['type' => 'text',         'label' => 'TrackID',          'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 2],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php',           'label' => 'UserAuthor',  'picto' => 'user',                            'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php',           'label' => 'UserModif',   'picto' => 'user',                            'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_sheet'      => ['type' => 'integer:Sheet:digiquali/class/sheet.class.php',    'label' => 'Sheet',       'picto' => 'fontawesome_fa-list_fas_#d35968', 'enabled' => 1, 'position' => 11,  'notnull' => 1, 'visible' => 5, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'digiquali_sheet.rowid'],
        'projectid'     => ['type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project',     'picto' => 'project',                         'enabled' => 1, 'position' => 13,  'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'projet.rowid', 'positioncard' => 2]
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
     * @var string Public note
     */
    public $note_public;

    /**
     * @var string Private note
     */
    public $note_private;

    /**
     * @var string|null Photo path
     */
    public ?string $photo = '';

    /**
     * @var float|string|null Success rate
     */
    public $success_rate;

    /**
     * @var string|null TrackID
     */
    public ?string $track_id;

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    /**
     * @var int Sheet ID
     */
    public int $fk_sheet;

    /**
     * @var int|string|null Project ID
     */
    public $projectid;

    /**
     * @var string Name of subtable line
     */
    public $table_element_line = 'digiquali_surveydet';

    /**
     * @var SurveyLine[] Array of subtable lines
     */
    public $lines = [];

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);

        // Set default values
        $this->track_id = generate_random_id();
    }

    /**
     * Create object into database
     *
     * @param  User      $user      User that creates
     * @param  bool      $notrigger false = launch triggers after, true = disable triggers
     * @return int                  0 < if KO, ID of created object if OK
     * @throws Exception
     */
    public function create(User $user, bool $notrigger = false): int
    {
        global $conf;
        $result = parent::create($user, $notrigger);
        if ($result > 0) {
            // Load Digiquali libraries
            require_once __DIR__ . '/sheet.class.php';

            $sheet      = new Sheet($this->db);
            $surveyLine = new SurveyLine($this->db);

            $sheet->fetch($this->fk_sheet);

            if ($sheet->success_rate > 0) {
                $this->success_rate = $sheet->success_rate;
                $this->setValueFrom('success_rate', $this->success_rate, '', '', 'text', '', $user);
            }

            $questions = $sheet->fetchAllQuestions();

            if (!empty($questions)) {
                foreach ($questions as $question) {
                    $surveyLine->ref                     = $surveyLine->getNextNumRef();
                    $surveyLine->entity                  = $this->entity;
                    $surveyLine->status                  = 1;
                    $surveyLine->{'fk_'. $this->element} = $this->id;
                    $surveyLine->fk_question             = $question->id;

                    $surveyLine->create($user);
                }
            }

            if ($this->context['createfromclone'] != 'createfromclone') {
                $objectsMetadata = saturne_get_objects_metadata();
                foreach ($objectsMetadata as $objectMetadata) {
                    if (!empty(GETPOST($objectMetadata['post_name'])) && GETPOST($objectMetadata['post_name']) > 0) {
                        $this->add_object_linked($objectMetadata['link_name'], GETPOST($objectMetadata['post_name']));
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Set draft status
     *
     * @param  User $user      Object user that modify
     * @param  int  $notrigger 1 = Does not execute triggers, 0 = Execute triggers
     * @return int             0 < if KO, > 0 if OK
     * @throws Exception
     */
    public function setDraft(User $user, int $notrigger = 0): int
    {
        $signatory = new SaturneSignature($this->db, $this->module, $this->element);
        $signatory->deleteSignatoriesSignatures($this->id, $this->element);
        return parent::setDraft($user, $notrigger);
    }

    /**
     * Return if a survey can be deleted
     *
     * @return int 0 < if KO, > 0 if OK
     */
    public function is_erasable(): int
    {
        return $this->isLinkedToOtherObjects();
    }

    /**
     * Return if a survey is linked to another object
     *
     * @return int 0 < if KO, > 0 if OK
     */
    public function isLinkedToOtherObjects(): int
    {
        // Links between objects are stored in table element_element
        $sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
        $sql .= ' FROM '.MAIN_DB_PREFIX . 'element_element';
        $sql .= ' WHERE fk_target = ' . $this->id;
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
     * Clone an object into another one
     *
     * @param  User      $user    User that creates
     * @param  int       $fromID  ID of object to clone
     * @param  array     $options Options array
     * @return int                New object created, <0 if KO
     * @throws Exception
     */
    public function createFromClone(User $user, int $fromID, array $options): int
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $error = 0;

        $object = new self($this->db);
        $this->db->begin();

        // Load source object
        $result = $object->fetchCommon($fromID);
        if ($result > 0 && !empty($object->table_element_line)) {
            $object->fetchLines();
        }

        $objectRef = $object->ref;

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
            $object->status = 0;
        }
        if (empty($options['photos'])) {
            $object->photo = '';
        }
        if (property_exists($object, 'track_id')) {
            $object->track_id = generate_random_id();
        }

        $object->context['createfromclone']  = 'createfromclone';

        $object->fetchObjectLinked('','', $object->id, 'digiquali_' . $object->element,  'OR', 1, 'sourcetype', 0);

        $surveyID = $object->create($user);
        if ($surveyID > 0) {
            $objectFromClone = new self($this->db);
            $objectFromClone->fetch($surveyID);

            // Categories
            $cat = new Categorie($this->db);
            $categories = $cat->containing($fromID, 'survey');
            if (is_array($categories) && !empty($categories)) {
                foreach($categories as $cat) {
                    $categoryIds[] = $cat->id;
                }
                $object->setCategories($categoryIds);
            }

            // Add objects linked
            $objectsMetadata = saturne_get_objects_metadata();
            foreach ($objectsMetadata as $objectMetadata) {
                if (!empty($object->linkedObjectsIds[$objectMetadata['link_name']])) {
                    $object->add_object_linked($objectMetadata['link_name'], current($object->linkedObjectsIds[$objectMetadata['link_name']]));
                }
            }

            // Add Attendants
            $signatory = new SaturneSignature($this->db);
            if (!empty($options['attendants'])) {
                // Load signatory from source object
                $signatories = $signatory->fetchSignatory('', $fromID, $this->element);
                if (is_array($signatories) && !empty($signatories)) {
                    foreach ($signatories as $arrayRole) {
                        foreach ($arrayRole as $signatoryRole) {
                            $signatory->createFromClone($user, $signatoryRole->id, $surveyID);
                        }
                    }
                }
            }

            // Add Photos
            if (!empty($options['photos'])) {
                $dir  = $conf->digiquali->multidir_output[$conf->entity] . '/survey';
                $path = $dir . '/' . $objectRef . '/photos';
                dol_mkdir($dir . '/' . $objectFromClone->ref . '/photos');
                dolCopyDir($path,$dir . '/' . $objectFromClone->ref . '/photos', 0, 1);
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
            return $surveyID;
        } else {
            $this->db->rollback();
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

            $this->labelStatus[self::STATUS_DRAFT]          = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatus[self::STATUS_VALIDATED]      = $langs->transnoentitiesnoconv('Validated');
            $this->labelStatus[self::STATUS_LOCKED]         = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatus[self::STATUS_ARCHIVED]       = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatus[self::STATUS_DELETED]        = $langs->transnoentitiesnoconv('Deleted');

            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
            $this->labelStatusShort[self::STATUS_LOCKED]    = $langs->transnoentitiesnoconv('Locked');
            $this->labelStatusShort[self::STATUS_ARCHIVED]  = $langs->transnoentitiesnoconv('Archived');
            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status4';
        }
        if ($status == self::STATUS_LOCKED) {
            $statusType = 'status6';
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
     * Initialise object with example values
     * ID must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        $this->initAsSpecimenCommon();
    }

    /**
     * Return HTML string to put an input field into a page
     * Code very similar with showInputField of extra fields
     *
     * @param  string          $key         Key of attribute
     * @param  string|string[] $value       Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
     * @param  string          $moreparam   To add more parameters on html input tag
     * @param  string          $keysuffix   Suffix string to add into name and id of field (can be used to avoid duplicate names)
     * @param  string          $keyprefix   Prefix string to add into name and id of field (can be used to avoid duplicate names)
     * @param  string|int      $morecss     Value for css to define style/length of field. May also be a numeric
     * @param  int<0,1>        $nonewbutton Force to not show the new button on field that are links to object
     * @return string          $out         HTML string to put an input field into a page
     * @throws Exception
     */
    public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0): string
    {
        $objectsMetadata = saturne_get_objects_metadata();
        foreach($objectsMetadata as $objectMetadata) {
            if ($objectMetadata['conf'] > 0 && $key == $objectMetadata['post_name']) {
                $out          = '';
                $objectArrays = [];
                $objects      = saturne_fetch_all_object_type($objectMetadata['class_name']);
                if (is_array($objects) && !empty($objects)) {
                    $nameFields = explode(', ', $objectMetadata['name_field']);
                    foreach ($objects as $object) {
                        $objectArrays[$object->id] = array_reduce($nameFields, function($carry, $field) use ($object) {
                            return $carry . ' ' . $object->{$field};
                        });
                    }

                    $out = Form::selectarray($keyprefix . $key . $keysuffix, $objectArrays, $value, 1, 0, 0, '', 0, 0, 0, '', !empty($val['css']) ? $val['css'] : 'minwidth200 maxwidth300 widthcentpercentminusx');
                }

                return $out;
            }
        }

        return parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
    }


    /**
     * Load dashboard info
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        global $user, $langs;

        $confName        = dol_strtoupper($this->module) . '_DASHBOARD_CONFIG';
        $dashboardConfig = property_exists($user->conf, $confName) ? json_decode($user->conf->$confName) : null;
        $array = ['graphs' => [], 'disabledGraphs' => []];

        if (empty($dashboardConfig->graphs->SurveysByFiscalYear->hide)) {
            $array['graphs'][] = $this->getNbSurveysByMonth();
        } else {
            $array['disabledGraphs']['SurveysByFiscalYear'] = $langs->transnoentities('SurveysByFiscalYear');
        }

        return $array;
    }

    /**
     * Get surveys by month
     *
     * @return array     Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getNbSurveysByMonth(): array
    {
        global $conf, $langs;

        $startMonth  = $conf->global->SOCIETE_FISCAL_MONTH_START;
        $currentYear = date('Y', dol_now());
        $years       = [0 => $currentYear - 2, 1 => $currentYear - 1, 2 => $currentYear];

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('SurveysByFiscalYear');
        $array['name']  = 'SurveysByFiscalYear';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'bars';
        $array['showlegend'] = 1;
        $array['dataset']    = 3;

        $array['labels'] = [
            0 => [
                'label' => $years[0],
                'color' => '#9567AA'
            ],
            1 => [
                'label' => $years[1],
                'color' => '#4F9EBE'
            ],
            2 => [
                'label' => $years[2],
                'color' => '#FAC461'
            ]
        ];

        $arrayNbSurveys = array_fill(0, count($years), array_fill(1, 12, 0));
        for ($i = 1; $i <= 12; $i++) {
            foreach ($years as $key => $year) {
                $surveys = $this->fetchAll('', '', 0, 0, ['customsql' => 'MONTH (t.date_creation) = ' . $i . ' AND YEAR (t.date_creation) = ' . $year . ' AND t.status >= 0']);
                if (is_array($surveys) && !empty($surveys)) {
                    $arrayNbSurveys[$key][$i] = count($surveys);
                }
            }

            $month    = $langs->transnoentitiesnoconv('MonthShort' . sprintf('%02d', $i));
            $arrayKey = ($i - $startMonth + 12) % 12;
            $array['data'][$arrayKey] = [
                $month,
                $arrayNbSurveys[0][$i],
                $arrayNbSurveys[1][$i],
                $arrayNbSurveys[2][$i]
            ];
        }
        ksort($array['data']);

        return $array;
    }

    /**
     * Write information of trigger description
     *
     * @param  SaturneObject $object Object calling the trigger
     * @return string                Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $langs;

        // Load DigiQuali libraries
        require_once __DIR__ . '/../class/sheet.class.php';

        $sheet = new Sheet($this->db);
        $sheet->fetch($object->fk_sheet);

        $ret  = parent::getTriggerDescription($object);
        $ret .= $langs->transnoentities('Sheet') . ' : ' . $sheet->ref . ' - ' . $sheet->label . '</br>';
        if ($object->projectid > 0) {
            require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
            $project = new Project($this->db);
            $project->fetch($object->projectid);
            $ret .= $langs->transnoentities('Project') . ' : ' . $project->ref . ' ' . $project->title . '</br>';
        }
        $ret .= (!empty($object->photo) ? $langs->transnoentities('Photo') . ' : ' . $object->photo . '</br>' : '');

        return $ret;
    }

    /**
     * Calculate the total number of points for correct answers and the total possible number of points
     * (on the current survey)
     *
     * @return array
     */
    public function calculatePoints(): array
    {
        $numberOfAnsweredQuestions = 0;
        $numberOfQuestions = 0;
        $surveyTotalPoints = 0;
        $surveyCorrectAnswersTotalPoints = 0;
        $atLeastOneIncorrectSubGroup = false;

        // Fetch all questions or groups direct children of sheet
        $this->fetchObjectLinked($this->fk_sheet, 'digiquali_sheet');

        // Compute questions points
        if (isset($this->linkedObjectsIds['digiquali_question'])) {
            foreach ($this->linkedObjectsIds['digiquali_question'] as $questionId) {
                $question = new Question($this->db);
                $question->fetch($questionId);

                foreach ($this->lines as $questionAnswer) {
                    if ($questionId == $questionAnswer->fk_question) {
                        if ($question->checkAnswerIsCorrect($questionAnswer->answer)) {
                            $surveyCorrectAnswersTotalPoints += $question->points;
                        }
                        if ($questionAnswer->answer !== '') {
                            $numberOfAnsweredQuestions++;
                        }
                        $surveyTotalPoints += $question->points;
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

                [$subGroupNumberOfAnsweredQuestions, $subGroupNumberOfQuestions, $questionGroupCorrectAnswersTotalPoints, $questionGroupTotalPoints, $questionGroupWithAtLeastOneIncorrectSubGroup] = $questionGroup->calculatePoints($this);

                $numberOfAnsweredQuestions += $subGroupNumberOfAnsweredQuestions;
                $numberOfQuestions += $subGroupNumberOfQuestions;
                $surveyTotalPoints += $questionGroupTotalPoints;
                $surveyCorrectAnswersTotalPoints += $questionGroupCorrectAnswersTotalPoints;

                if (!$questionGroup->isCorrectFromPoints($questionGroupCorrectAnswersTotalPoints, $questionGroupTotalPoints)) {
                    $atLeastOneIncorrectSubGroup = true;
                }
                $atLeastOneIncorrectSubGroup = $atLeastOneIncorrectSubGroup || $questionGroupWithAtLeastOneIncorrectSubGroup;
            }
        }

        return [$numberOfAnsweredQuestions, $numberOfQuestions, $surveyCorrectAnswersTotalPoints, $surveyTotalPoints, $atLeastOneIncorrectSubGroup];
    }

    /**
	 * To know if the rate of correct answers is bigger than the attempted success rate of the current survey
     * and all the groups of the current survey are corrects.
	 *
	 * @return bool
	 */
	public function isCorrect(): bool
	{
        [$numberOfAnweredQuestions, $numberOfQuestions, $correctPoints, $totalPoints, $atLeastOneIncorrectSubGroup] = $this->calculatePoints();

        if ($atLeastOneIncorrectSubGroup) {
            return false;
        }

        return $this->isCorrectFromPoints($correctPoints, $totalPoints);
	}

    /**
	 * To know if the rate of correct answers is bigger than the attempted success rate of the current survey
     * and all the groups of the current survey are corrects.
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
     * Return a formatted string to print survey score (in points)
     * and success rate
     *
     * @return string
	 */
    public function getFormattedResults(): string
	{
        global $langs;

        [$numberOfAnsweredQuestions, $numberOfQuestions, $correctPoints, $totalPoints, $atLeastOneIncorrectSubGroup] = $this->calculatePoints();
        $successRate = 0;
        if ($totalPoints > 0) {
            $successRate = round($correctPoints / $totalPoints * 100, 2);
        }
        $pointsResult = $correctPoints . ' / ' . $totalPoints . ' ' . strtolower(($totalPoints > 1 ? $langs->trans('Points') : $langs->trans('Point')));
        $successRateResult = $successRate . ' %';
		return $successRateResult . ' (' . $pointsResult . ')';
	}

    /**
     * Display survey questions & answers
     */
    public function displayAnswers(SurveyLine $objectLine, array $questionsAndGroups, bool $isFrontend, int $level = 0)
    {
        global $langs;

        $object = $this;

        include DOL_DOCUMENT_ROOT . '/custom/digiquali/core/tpl/digiquali_answers.tpl.php';
    }
}

/**
 * Class for SurveyLine
 */
class SurveyLine extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object
     */
    public $element = 'surveydet';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'digiquali_surveydet';

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
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'             => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'               => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 1, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'           => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'            => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'     => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0],
        'tms'               => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'import_key'        => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0, 'index' => 0],
        'status'            => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 0, 'index' => 1, 'default' => 1],
        'type'              => ['type' => 'varchar(128)', 'label' => 'Type',             'enabled' => 0, 'position' => 80,  'notnull' => 0, 'visible' => 0],
        'answer'            => ['type' => 'text',         'label' => 'Answer',           'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'answer_photo'      => ['type' => 'text',         'label' => 'AnswerPhoto',      'enabled' => 0, 'position' => 100, 'notnull' => 0, 'visible' => 0],
        'comment'           => ['type' => 'text',         'label' => 'Comment',          'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0],
        'fk_user_creat'     => ['type' => 'integer:User:user/class/user.class.php',              'label' => 'UserAuthor', 'picto' => 'user',                                'enabled' => 1, 'position' => 120, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'     => ['type' => 'integer:User:user/class/user.class.php',              'label' => 'UserModif',  'picto' => 'user',                                'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_survey'         => ['type' => 'integer:Survey:digiquali/class/survey.class.php',     'label' => 'Survey',     'picto' => 'fontawesome_fa-marker_fas_#d35968',   'enabled' => 1, 'position' => 140,  'notnull' => 1, 'visible' => 0, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'digiquali_survey.rowid'],
        'fk_question'       => ['type' => 'integer:Question:digiquali/class/question.class.php', 'label' => 'Question',   'picto' => 'fontawesome_fa-question_fas_#d35968', 'enabled' => 1, 'position' => 150,  'notnull' => 1, 'visible' => 0, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'foreignkey' => 'digiquali_question.rowid'],
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
     * @var string|null Type
     */
    public ?string $type;

    /**
     * @var string|null Answer
     */
    public ?string $answer = '';

    /**
     * @var string|null Answer photo
     */
    public ?string $answer_photo;

    /**
     * @var string|null Comment
     */
    public ?string $comment = '';

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    /**
     * @var int Survey ID
     */
    public int $fk_survey;

    /**
     * @var ?int|null Question ID
     */
    public int $fk_question;

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
     * Load survey line from database form parent with question
     *
     * @param  int        $surveyID   Survey id
     * @param  int        $questionID Question id
     * @return array|int              Int <0 if KO, array of pages if OK
     * @throws Exception
     */
    public function fetchFromParentWithQuestion(int $surveyID, int $questionID)
    {
        return $this->fetchAll('', '', 1, 0, ['customsql' => 't.fk_survey = ' . $surveyID . ' AND t.fk_question = ' . $questionID . ' AND t.status > 0']);
    }
}
