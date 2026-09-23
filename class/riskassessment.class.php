<?php

/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
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
 * \file    class/riskassessment.class.php
 * \ingroup digiquali
 * \brief   This file is a CRUD class file for RiskAssessment (Create/Read/Update/Delete)
 */

namespace Digiquali;

use DoliDB;
use Task;
use User;
use SaturneObject;

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for RiskAssessment
 */
class RiskAssessment extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiquali';

    /**
     * @var string Element type of object
     */
    public $element = 'riskassessment';

    /**
     * @var string Name of table without prefix where object is stored
     *             This is also the key used for extrafields management
     */
    public $table_element = 'digiquali_riskassessment';

    /**
     * @var string Name of icon for riskassessment
     *             Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size')
     *             or 'riskassessment@digiquali' if picto is file 'img/object_riskassessment.png'
     */
    public string $picto = 'fontawesome_fa-exclamation-triangle_fas_#d35968';

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
     * @var array Array with all fields and their property.
     *            Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'                => ['type' => 'integer',      'label' => 'TechnicalID',       'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => -2, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'                  => ['type' => 'varchar(128)', 'label' => 'Ref',               'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'              => ['type' => 'varchar(128)', 'label' => 'RefExt',            'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => -2],
        'entity'               => ['type' => 'integer',      'label' => 'Entity',            'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => -2, 'index' => 1],
        'date_creation'        => ['type' => 'datetime',     'label' => 'DateCreation',      'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'                  => ['type' => 'timestamp',    'label' => 'DateModification',  'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => -2],
        'import_key'           => ['type' => 'varchar(14)',  'label' => 'ImportId',          'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => -2, 'index' => 0],
        'status'               => ['type' => 'smallint',     'label' => 'Status',            'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 1,  'index' => 1, 'searchmulti' => 1, 'default' => self::STATUS_VALIDATED, 'arrayofkeyval' => [1 => 'InProgress', 2 => 'Locked', 3 => 'Archived'], 'css' => 'minwidth200'],
        'photo'                => ['type' => 'varchar(255)', 'label' => 'Photo',             'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 1],
        'comment'              => ['type' => 'html',         'label' => 'Comment',           'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 1],
        'gravity_percentage'   => ['type' => 'real',         'label' => 'Gravity',           'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 1, 'default' => 0.00],
        'frequency_percentage' => ['type' => 'real',         'label' => 'Frequency',         'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => 1, 'default' => 0.00],
        'control_percentage'   => ['type' => 'real',         'label' => 'ControlPercentage', 'enabled' => 1, 'position' => 120, 'notnull' => 1, 'visible' => 1, 'default' => 0.00],
        'fk_user_creat'        => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 130, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'        => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => -2, 'foreignkey' => 'user.rowid'],
        'fk_activity'          => ['type' => 'integer',                                'label' => 'Activity',                      'enabled' => 1, 'position' => 150, 'notnull' => 1, 'visible' => 1],
        'fk_parent'            => ['type' => 'integer',                                'label' => 'Parent',                        'enabled' => 1, 'position' => 160, 'notnull' => 1, 'visible' => 0, 'default' => 0],
    ];

    /**
     * @var int Status
     */
    public $status = self::STATUS_VALIDATED;

    /**
     * @var string|null Photo
     */
    public ?string $photo = null;

    /**
     * @var string|null Comment
     */
    public ?string $comment = null;

    /**
     * @var float Gravity percentage
     */
    public float $gravity_percentage = 0.00;

    /**
     * @var float Frequency percentage
     */
    public float $frequency_percentage = 0.00;

    /**
     * @var float Control percentage
     */
    public float $control_percentage = 0.00;

    /**
     * @var int Activity ID
     */
    public int $fk_activity = 0;

    /**
     * @var int Parent risk assessment ID (root of the evaluation line; 0 if this is a line root)
     */
    public int $fk_parent = 0;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Create object into database
     *
     * @param  User        $user      User that creates
     * @param  int<0,1>    $noTrigger 0 = launch triggers after, 1 = disable triggers
     * @return int<-1,max>            Return integer 0 < if KO, ID of created object if OK
     */
    public function create(User $user, int $noTrigger = 0): int
    {
        $this->ref = $this->getNextNumRef();

        return parent::create($user, $noTrigger);
    }

    /**
     * Write information of trigger description
     *
     * @return string Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(): string
    {
        global $langs;

        $linkedElement = json_decode($this->element_linked, true);

        $ret  = parent::getTriggerDescription();
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

    /**
     * Initialise object with example values
     * ID must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen(): void
    {
        global $langs;

         parent::initAsSpecimen();

        $this->ref                  = 'RAXX';
        $this->date_creation        = dol_now();
        $this->comment              = $langs->trans('NoData');
        $this->gravity_percentage   = 0.00;
        $this->frequency_percentage = 0.00;
        $this->control_percentage   = 0.00;
    }

    public function getRiskAssessmentInfos(): array
    {
        $out = [];

        $out[$this->element]['id']      = $this->id;
        $out[$this->element]['ref']     = $this->getNomUrl(1, 'nolink', 1);
        $out[$this->element]['comment'] = $this->comment;

        // @todo pas de gestion de user pour le moment
        $userTmp = new User($this->db);
        $userTmp->fetch($this->fk_user_creat);
        $out[$this->element]['author'] = $userTmp->getNomUrl(1);

        $out[$this->element]['date'] = dol_print_date($this->date_creation, 'day');

        $out[$this->element]['control_percentage'] = $this->control_percentage . '%';

        $residualRiskPercentage = round(($this->gravity_percentage * $this->frequency_percentage * (100 - $this->control_percentage)) / 10000, 2) . '%';

        $out[$this->element]['risk']          = $this->getResidualRiskPercentageClass();
        $out[$this->element]['residual_risk'] = $residualRiskPercentage;

        $task = new Task($this->db);

        $out[$task->element]['ref']   = $task->getNomUrl(1, 'withproject');
        $out[$task->element]['label'] = $task->label;

        // @todo pas de gestion de user pour le moment
        $userTmp = new User($this->db);
        $userTmp->fetch($task->fk_user_creat);
        $out[$task->element]['author'] = $userTmp->getNomUrl(1);

        $out[$task->element]['date']  = !empty($task->date_start) ? dol_print_date($task->date_start, 'dayhour') : '?';
        $out[$task->element]['date'] .= ' - ' . (!empty($task->date_end) ? dol_print_date($task->date_end, 'dayhour') : '?');

        return $out;
    }

    public function getResidualRiskPercentageClass(): string
    {
        $residualRiskPercentage = round(($this->gravity_percentage * $this->frequency_percentage * (100 - $this->control_percentage)) / 10000, 2);
        if ($residualRiskPercentage >= 75) {
            $residualRiskPercentageClass = 'black';
        } else if ($residualRiskPercentage >= 50) {
            $residualRiskPercentageClass = 'red';
        } else if ($residualRiskPercentage >= 25) {
            $residualRiskPercentageClass = 'yellow';
        } else {
            $residualRiskPercentageClass = 'grey';
        }

        return $residualRiskPercentageClass;
    }

    /**
     * Get the root id of the evaluation line this risk assessment belongs to.
     *
     * @return int Root risk assessment id (itself when it is the line root)
     */
    public function getRootId(): int
    {
        return $this->fk_parent > 0 ? $this->fk_parent : $this->id;
    }

    /**
     * Fetch the current risk assessment of each evaluation line of an activity.
     * A line groups a chain of re-evaluations; only its latest (validated) one is current.
     *
     * @param  int    $activityId Activity id
     * @return self[]             Current risk assessments, one per evaluation line
     */
    public function fetchCurrentLines(int $activityId): array
    {
        $riskAssessments = $this->fetchAll('ASC', 'rowid', 0, 0, ['customsql' => 't.fk_activity = ' . $activityId . ' AND t.status = ' . self::STATUS_VALIDATED]);

        return is_array($riskAssessments) ? $riskAssessments : [];
    }

    /**
     * Fetch the whole history of an evaluation line (root + re-evaluations), newest first.
     *
     * @param  int    $rootId Line root risk assessment id
     * @return self[]         Risk assessments belonging to the line
     */
    public function fetchLineHistory(int $rootId): array
    {
        $riskAssessments = $this->fetchAll('DESC', 'rowid', 0, 0, ['customsql' => '(t.rowid = ' . $rootId . ' OR t.fk_parent = ' . $rootId . ')']);

        return is_array($riskAssessments) ? $riskAssessments : [];
    }

    /**
     * Display the current evaluation lines of an activity (one block per line).
     *
     * @param  array $activityInfos Activity infos (id, element, ...)
     * @return void
     */
    public function displayRiskAssessmentList(array $activityInfos): void
    {
        global $db, $langs, $user; // used in tpl

        $riskAssessment     = new self($db);
        $currentAssessments = $riskAssessment->fetchCurrentLines((int) $activityInfos['id']);

        require __DIR__ . '/../core/tpl/riskassessment/digiquali_riskassessment_lines_view.tpl.php';
    }

    /**
     * Display the read-only history of an evaluation line.
     *
     * @param  int  $rootId Line root risk assessment id
     * @return void
     */
    public function displayLineHistory(int $rootId): void
    {
        global $db, $langs; // used in tpl

        $riskAssessment = new self($db);
        $lineHistory    = $riskAssessment->fetchLineHistory($rootId);

        require __DIR__ . '/../core/tpl/riskassessment/digiquali_riskassessment_history_view.tpl.php';
    }

    public function displayRiskAssessmentView(array $riskAssessmentInfos): void
    {
        global $db, $langs;

        if (($riskAssessmentInfos['id'] ?? 0) > 0) {
            require __DIR__ . '/../core/tpl/riskassessment/digiquali_riskassessment_single_view.tpl.php';
        } else {
            require __DIR__ . '/../core/tpl/riskassessment/digiquali_riskassessment_add_view.tpl.php';
        }
    }

    public function displayRiskAssessmentTaskView(array $riskAssessmentInfos): void
    {
        global $db, $langs;

        if (($riskAssessmentInfos['id'] ?? 0) > 0) {
            require __DIR__ . '/../core/tpl/riskassessment/task/digiquali_riskassessment_task_single_view.tpl.php';
        } else {
            require __DIR__ . '/../core/tpl/riskassessment/task/digiquali_riskassessment_task_add_view.tpl.php';
        }
    }
}

