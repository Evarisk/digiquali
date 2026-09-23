<?php

/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    class/api_digiquali.class.php
 * \ingroup digiquali
 * \brief   REST API for the DigiQuali module — exposes all 8 entities
 *          (control, survey, sheet, question, answer, questiongroup,
 *          activity, riskassessment) under the /digiquali endpoint.
 */

require_once DOL_DOCUMENT_ROOT . '/custom/saturne/class/saturneapi.class.php';

require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/control.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/survey.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/sheet.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/question.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/answer.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/questiongroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/activity.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/digiquali/class/riskassessment.class.php';

use Luracast\Restler\RestException;
use Digiquali\RiskAssessment;

/**
 * REST API for DigiQuali.
 *
 * Dolibarr's REST router maps /digiquali to this class because the file
 * lives at /htdocs/custom/digiquali/class/api_digiquali.class.php and the
 * URL segment must match the module dir. All eight DigiQuali entities are
 * exposed under namespaced sub-routes (e.g. /digiquali/controls/{id}).
 *
 * The CRUD/lifecycle/line plumbing lives in {@see SaturneApi}; this class
 * just declares the per-entity configuration and the @url-annotated
 * methods that Restler turns into routes.
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Digiquali extends SaturneApi
{
    /**
     * Constructor: wire the per-entity configuration that drives the
     * generic dispatcher in SaturneApi.
     */
    public function __construct()
    {
        parent::__construct();

        $this->module = 'digiquali';

        $this->entities = [
            'controls' => [
                'class'          => Control::class,
                'fields'         => ['fk_sheet', 'fk_user_controller'],
                'permissions'    => [
                    'read'       => 'control->read',
                    'create'     => 'control->create',
                    'write'      => 'control->write',
                    'delete'     => 'control->delete',
                    'setverdict' => 'control->setverdict',
                ],
                'lineClass'      => ControlLine::class,
                'lineForeignKey' => 'fk_control',
                'lineFields'     => ['fk_question'],
            ],
            'surveys' => [
                'class'          => Survey::class,
                'fields'         => ['fk_sheet'],
                'permissions'    => [
                    'read'   => 'survey->read',
                    'write'  => 'survey->write',
                    'delete' => 'survey->delete',
                ],
                'lineClass'      => SurveyLine::class,
                'lineForeignKey' => 'fk_survey',
                'lineFields'     => ['fk_question'],
            ],
            'sheets' => [
                'class'       => Sheet::class,
                'fields'      => ['label', 'type'],
                'permissions' => [
                    'read'   => 'sheet->read',
                    'write'  => 'sheet->write',
                    'delete' => 'sheet->delete',
                ],
            ],
            'questions' => [
                'class'       => Question::class,
                'fields'      => ['label', 'type'],
                'permissions' => [
                    'read'   => 'question->read',
                    'write'  => 'question->write',
                    'delete' => 'question->delete',
                ],
            ],
            'answers' => [
                'class'       => Answer::class,
                'fields'      => ['value', 'fk_question'],
                'permissions' => [
                    'read'   => 'question->read',
                    'write'  => 'question->write',
                    'delete' => 'question->delete',
                ],
            ],
            'questiongroups' => [
                'class'       => QuestionGroup::class,
                'fields'      => ['label'],
                'permissions' => [
                    'read'   => 'questiongroup->read',
                    'write'  => 'questiongroup->write',
                    'delete' => 'questiongroup->delete',
                ],
            ],
            'activities' => [
                'class'       => Activity::class,
                'fields'      => ['fk_element'],
                'permissions' => [
                    'read'   => 'activity->read',
                    'write'  => 'activity->write',
                    'delete' => 'activity->delete',
                ],
            ],
            'riskassessments' => [
                'class'       => RiskAssessment::class,
                'fields'      => ['fk_activity'],
                'permissions' => [
                    'read'   => 'riskassessment->read',
                    'write'  => 'riskassessment->write',
                    'delete' => 'riskassessment->delete',
                ],
            ],
        ];
    }

    // =================================================================
    // controls
    // =================================================================

    /**
     * Get one control by id.
     *
     * @param int $id Control id
     *
     * @return object Cleaned control
     *
     * @url GET controls/{id}
     */
    public function getControl($id)
    {
        return $this->_get('controls', $id);
    }

    /**
     * List controls.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET controls
     */
    public function listControls($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('controls', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create a control.
     *
     * @param array<string, mixed>|null $request_data Control data
     *
     * @return int New control id
     *
     * @url POST controls
     */
    public function createControl($request_data = null)
    {
        return $this->_post('controls', $request_data);
    }

    /**
     * Update a control.
     *
     * @param int                       $id           Control id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned, freshly-fetched control
     *
     * @url PUT controls/{id}
     */
    public function updateControl($id, $request_data = null)
    {
        return $this->_put('controls', $id, $request_data);
    }

    /**
     * Delete a control.
     *
     * @param int $id Control id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE controls/{id}
     */
    public function deleteControl($id)
    {
        return $this->_delete('controls', $id);
    }

    /**
     * Validate a control (move from draft to validated).
     *
     * @param int $id Control id
     *
     * @return object Cleaned control
     *
     * @url POST controls/{id}/validate
     */
    public function validateControl($id)
    {
        return $this->_setStatus('controls', $id, 'validate');
    }

    /**
     * Move a control back to draft.
     *
     * @param int $id Control id
     *
     * @return object Cleaned control
     *
     * @url POST controls/{id}/setdraft
     */
    public function setDraftControl($id)
    {
        return $this->_setStatus('controls', $id, 'setDraft');
    }

    /**
     * Lock a control.
     *
     * @param int $id Control id
     *
     * @return object Cleaned control
     *
     * @url POST controls/{id}/setlocked
     */
    public function setLockedControl($id)
    {
        return $this->_setStatus('controls', $id, 'setLocked');
    }

    /**
     * Archive a control.
     *
     * @param int $id Control id
     *
     * @return object Cleaned control
     *
     * @url POST controls/{id}/setarchived
     */
    public function setArchivedControl($id)
    {
        return $this->_setStatus('controls', $id, 'setArchived');
    }

    /**
     * Set the verdict on a control. Verdict values: 0=N/A, 1=OK, 2=KO.
     *
     * @param int $id      Control id
     * @param int $verdict New verdict value
     *
     * @return object Cleaned control
     *
     * @url POST controls/{id}/setverdict
     */
    public function setControlVerdict($id, $verdict)
    {
        $this->_checkPermission('controls', 'setverdict');

        $verdict = (int) $verdict;
        if ($verdict < 0 || $verdict > 2) {
            throw new RestException(400, 'Verdict must be 0 (N/A), 1 (OK) or 2 (KO)');
        }

        $control = $this->_getObject('controls');
        if ($control->fetch((int) $id) <= 0) {
            throw new RestException(404, 'Control not found');
        }

        $control->verdict = $verdict;
        if ($control->update(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error setting verdict', $this->_collectErrors($control));
        }

        $control->fetch((int) $id);
        return $this->_cleanObjectDatas($control);
    }

    /**
     * Get the lines of a control.
     *
     * @param int $id Control id
     *
     * @return array<int, object>
     *
     * @url GET controls/{id}/lines
     */
    public function listControlLines($id)
    {
        return $this->_getLines('controls', $id);
    }

    /**
     * Add a line to a control.
     *
     * @param int                       $id           Control id
     * @param array<string, mixed>|null $request_data Line data
     *
     * @return int New line id
     *
     * @url POST controls/{id}/lines
     */
    public function createControlLine($id, $request_data = null)
    {
        return $this->_postLine('controls', $id, $request_data);
    }

    /**
     * Update a line of a control.
     *
     * @param int                       $id           Control id
     * @param int                       $lineid       Line id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned line
     *
     * @url PUT controls/{id}/lines/{lineid}
     */
    public function updateControlLine($id, $lineid, $request_data = null)
    {
        return $this->_putLine('controls', $id, $lineid, $request_data);
    }

    /**
     * Delete a line of a control.
     *
     * @param int $id     Control id
     * @param int $lineid Line id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE controls/{id}/lines/{lineid}
     */
    public function deleteControlLine($id, $lineid)
    {
        return $this->_deleteLine('controls', $id, $lineid);
    }

    // =================================================================
    // surveys
    // =================================================================

    /**
     * Get one survey by id.
     *
     * @param int $id Survey id
     *
     * @return object Cleaned survey
     *
     * @url GET surveys/{id}
     */
    public function getSurvey($id)
    {
        return $this->_get('surveys', $id);
    }

    /**
     * List surveys.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET surveys
     */
    public function listSurveys($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('surveys', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create a survey.
     *
     * @param array<string, mixed>|null $request_data Survey data
     *
     * @return int New survey id
     *
     * @url POST surveys
     */
    public function createSurvey($request_data = null)
    {
        return $this->_post('surveys', $request_data);
    }

    /**
     * Update a survey.
     *
     * @param int                       $id           Survey id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned survey
     *
     * @url PUT surveys/{id}
     */
    public function updateSurvey($id, $request_data = null)
    {
        return $this->_put('surveys', $id, $request_data);
    }

    /**
     * Delete a survey.
     *
     * @param int $id Survey id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE surveys/{id}
     */
    public function deleteSurvey($id)
    {
        return $this->_delete('surveys', $id);
    }

    /**
     * Validate a survey.
     *
     * @param int $id Survey id
     *
     * @return object Cleaned survey
     *
     * @url POST surveys/{id}/validate
     */
    public function validateSurvey($id)
    {
        return $this->_setStatus('surveys', $id, 'validate');
    }

    /**
     * Move a survey back to draft.
     *
     * @param int $id Survey id
     *
     * @return object Cleaned survey
     *
     * @url POST surveys/{id}/setdraft
     */
    public function setDraftSurvey($id)
    {
        return $this->_setStatus('surveys', $id, 'setDraft');
    }

    /**
     * Lock a survey.
     *
     * @param int $id Survey id
     *
     * @return object Cleaned survey
     *
     * @url POST surveys/{id}/setlocked
     */
    public function setLockedSurvey($id)
    {
        return $this->_setStatus('surveys', $id, 'setLocked');
    }

    /**
     * Archive a survey.
     *
     * @param int $id Survey id
     *
     * @return object Cleaned survey
     *
     * @url POST surveys/{id}/setarchived
     */
    public function setArchivedSurvey($id)
    {
        return $this->_setStatus('surveys', $id, 'setArchived');
    }

    /**
     * Get the lines of a survey.
     *
     * @param int $id Survey id
     *
     * @return array<int, object>
     *
     * @url GET surveys/{id}/lines
     */
    public function listSurveyLines($id)
    {
        return $this->_getLines('surveys', $id);
    }

    /**
     * Add a line to a survey.
     *
     * @param int                       $id           Survey id
     * @param array<string, mixed>|null $request_data Line data
     *
     * @return int New line id
     *
     * @url POST surveys/{id}/lines
     */
    public function createSurveyLine($id, $request_data = null)
    {
        return $this->_postLine('surveys', $id, $request_data);
    }

    /**
     * Update a line of a survey.
     *
     * @param int                       $id           Survey id
     * @param int                       $lineid       Line id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned line
     *
     * @url PUT surveys/{id}/lines/{lineid}
     */
    public function updateSurveyLine($id, $lineid, $request_data = null)
    {
        return $this->_putLine('surveys', $id, $lineid, $request_data);
    }

    /**
     * Delete a line of a survey.
     *
     * @param int $id     Survey id
     * @param int $lineid Line id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE surveys/{id}/lines/{lineid}
     */
    public function deleteSurveyLine($id, $lineid)
    {
        return $this->_deleteLine('surveys', $id, $lineid);
    }

    // =================================================================
    // sheets
    // =================================================================

    /**
     * Get one sheet by id.
     *
     * @param int $id Sheet id
     *
     * @return object Cleaned sheet
     *
     * @url GET sheets/{id}
     */
    public function getSheet($id)
    {
        return $this->_get('sheets', $id);
    }

    /**
     * List sheets.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET sheets
     */
    public function listSheets($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('sheets', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create a sheet.
     *
     * @param array<string, mixed>|null $request_data Sheet data
     *
     * @return int New sheet id
     *
     * @url POST sheets
     */
    public function createSheet($request_data = null)
    {
        return $this->_post('sheets', $request_data);
    }

    /**
     * Update a sheet.
     *
     * @param int                       $id           Sheet id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned sheet
     *
     * @url PUT sheets/{id}
     */
    public function updateSheet($id, $request_data = null)
    {
        return $this->_put('sheets', $id, $request_data);
    }

    /**
     * Delete a sheet.
     *
     * @param int $id Sheet id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE sheets/{id}
     */
    public function deleteSheet($id)
    {
        return $this->_delete('sheets', $id);
    }

    /**
     * Validate a sheet.
     *
     * @param int $id Sheet id
     *
     * @return object Cleaned sheet
     *
     * @url POST sheets/{id}/validate
     */
    public function validateSheet($id)
    {
        return $this->_setStatus('sheets', $id, 'validate');
    }

    /**
     * Move a sheet back to draft.
     *
     * @param int $id Sheet id
     *
     * @return object Cleaned sheet
     *
     * @url POST sheets/{id}/setdraft
     */
    public function setDraftSheet($id)
    {
        return $this->_setStatus('sheets', $id, 'setDraft');
    }

    /**
     * Lock a sheet.
     *
     * @param int $id Sheet id
     *
     * @return object Cleaned sheet
     *
     * @url POST sheets/{id}/setlocked
     */
    public function setLockedSheet($id)
    {
        return $this->_setStatus('sheets', $id, 'setLocked');
    }

    /**
     * Archive a sheet.
     *
     * @param int $id Sheet id
     *
     * @return object Cleaned sheet
     *
     * @url POST sheets/{id}/setarchived
     */
    public function setArchivedSheet($id)
    {
        return $this->_setStatus('sheets', $id, 'setArchived');
    }

    // =================================================================
    // questions
    // =================================================================

    /**
     * Get one question by id.
     *
     * @param int $id Question id
     *
     * @return object Cleaned question
     *
     * @url GET questions/{id}
     */
    public function getQuestion($id)
    {
        return $this->_get('questions', $id);
    }

    /**
     * List questions.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET questions
     */
    public function listQuestions($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('questions', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create a question.
     *
     * @param array<string, mixed>|null $request_data Question data
     *
     * @return int New question id
     *
     * @url POST questions
     */
    public function createQuestion($request_data = null)
    {
        return $this->_post('questions', $request_data);
    }

    /**
     * Update a question.
     *
     * @param int                       $id           Question id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned question
     *
     * @url PUT questions/{id}
     */
    public function updateQuestion($id, $request_data = null)
    {
        return $this->_put('questions', $id, $request_data);
    }

    /**
     * Delete a question.
     *
     * @param int $id Question id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE questions/{id}
     */
    public function deleteQuestion($id)
    {
        return $this->_delete('questions', $id);
    }

    /**
     * Validate a question.
     *
     * @param int $id Question id
     *
     * @return object Cleaned question
     *
     * @url POST questions/{id}/validate
     */
    public function validateQuestion($id)
    {
        return $this->_setStatus('questions', $id, 'validate');
    }

    /**
     * Move a question back to draft.
     *
     * @param int $id Question id
     *
     * @return object Cleaned question
     *
     * @url POST questions/{id}/setdraft
     */
    public function setDraftQuestion($id)
    {
        return $this->_setStatus('questions', $id, 'setDraft');
    }

    /**
     * Lock a question.
     *
     * @param int $id Question id
     *
     * @return object Cleaned question
     *
     * @url POST questions/{id}/setlocked
     */
    public function setLockedQuestion($id)
    {
        return $this->_setStatus('questions', $id, 'setLocked');
    }

    /**
     * Archive a question.
     *
     * @param int $id Question id
     *
     * @return object Cleaned question
     *
     * @url POST questions/{id}/setarchived
     */
    public function setArchivedQuestion($id)
    {
        return $this->_setStatus('questions', $id, 'setArchived');
    }

    // =================================================================
    // answers
    // =================================================================

    /**
     * Get one answer by id.
     *
     * @param int $id Answer id
     *
     * @return object Cleaned answer
     *
     * @url GET answers/{id}
     */
    public function getAnswer($id)
    {
        return $this->_get('answers', $id);
    }

    /**
     * List answers.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET answers
     */
    public function listAnswers($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('answers', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create an answer.
     *
     * @param array<string, mixed>|null $request_data Answer data
     *
     * @return int New answer id
     *
     * @url POST answers
     */
    public function createAnswer($request_data = null)
    {
        return $this->_post('answers', $request_data);
    }

    /**
     * Update an answer.
     *
     * @param int                       $id           Answer id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned answer
     *
     * @url PUT answers/{id}
     */
    public function updateAnswer($id, $request_data = null)
    {
        return $this->_put('answers', $id, $request_data);
    }

    /**
     * Delete an answer.
     *
     * @param int $id Answer id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE answers/{id}
     */
    public function deleteAnswer($id)
    {
        return $this->_delete('answers', $id);
    }

    /**
     * Validate an answer.
     *
     * @param int $id Answer id
     *
     * @return object Cleaned answer
     *
     * @url POST answers/{id}/validate
     */
    public function validateAnswer($id)
    {
        return $this->_setStatus('answers', $id, 'validate');
    }

    /**
     * Move an answer back to draft.
     *
     * @param int $id Answer id
     *
     * @return object Cleaned answer
     *
     * @url POST answers/{id}/setdraft
     */
    public function setDraftAnswer($id)
    {
        return $this->_setStatus('answers', $id, 'setDraft');
    }

    /**
     * Lock an answer.
     *
     * @param int $id Answer id
     *
     * @return object Cleaned answer
     *
     * @url POST answers/{id}/setlocked
     */
    public function setLockedAnswer($id)
    {
        return $this->_setStatus('answers', $id, 'setLocked');
    }

    /**
     * Archive an answer.
     *
     * @param int $id Answer id
     *
     * @return object Cleaned answer
     *
     * @url POST answers/{id}/setarchived
     */
    public function setArchivedAnswer($id)
    {
        return $this->_setStatus('answers', $id, 'setArchived');
    }

    // =================================================================
    // questiongroups
    // =================================================================

    /**
     * Get one question group by id.
     *
     * @param int $id Question group id
     *
     * @return object Cleaned question group
     *
     * @url GET questiongroups/{id}
     */
    public function getQuestiongroup($id)
    {
        return $this->_get('questiongroups', $id);
    }

    /**
     * List question groups.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET questiongroups
     */
    public function listQuestiongroups($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('questiongroups', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create a question group.
     *
     * @param array<string, mixed>|null $request_data Question group data
     *
     * @return int New question group id
     *
     * @url POST questiongroups
     */
    public function createQuestiongroup($request_data = null)
    {
        return $this->_post('questiongroups', $request_data);
    }

    /**
     * Update a question group.
     *
     * @param int                       $id           Question group id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned question group
     *
     * @url PUT questiongroups/{id}
     */
    public function updateQuestiongroup($id, $request_data = null)
    {
        return $this->_put('questiongroups', $id, $request_data);
    }

    /**
     * Delete a question group.
     *
     * @param int $id Question group id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE questiongroups/{id}
     */
    public function deleteQuestiongroup($id)
    {
        return $this->_delete('questiongroups', $id);
    }

    /**
     * Validate a question group.
     *
     * @param int $id Question group id
     *
     * @return object Cleaned question group
     *
     * @url POST questiongroups/{id}/validate
     */
    public function validateQuestiongroup($id)
    {
        return $this->_setStatus('questiongroups', $id, 'validate');
    }

    /**
     * Move a question group back to draft.
     *
     * @param int $id Question group id
     *
     * @return object Cleaned question group
     *
     * @url POST questiongroups/{id}/setdraft
     */
    public function setDraftQuestiongroup($id)
    {
        return $this->_setStatus('questiongroups', $id, 'setDraft');
    }

    /**
     * Lock a question group.
     *
     * @param int $id Question group id
     *
     * @return object Cleaned question group
     *
     * @url POST questiongroups/{id}/setlocked
     */
    public function setLockedQuestiongroup($id)
    {
        return $this->_setStatus('questiongroups', $id, 'setLocked');
    }

    /**
     * Archive a question group.
     *
     * @param int $id Question group id
     *
     * @return object Cleaned question group
     *
     * @url POST questiongroups/{id}/setarchived
     */
    public function setArchivedQuestiongroup($id)
    {
        return $this->_setStatus('questiongroups', $id, 'setArchived');
    }

    // =================================================================
    // activities
    // =================================================================

    /**
     * Get one activity by id.
     *
     * @param int $id Activity id
     *
     * @return object Cleaned activity
     *
     * @url GET activities/{id}
     */
    public function getActivity($id)
    {
        return $this->_get('activities', $id);
    }

    /**
     * List activities.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET activities
     */
    public function listActivities($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('activities', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create an activity.
     *
     * @param array<string, mixed>|null $request_data Activity data
     *
     * @return int New activity id
     *
     * @url POST activities
     */
    public function createActivity($request_data = null)
    {
        return $this->_post('activities', $request_data);
    }

    /**
     * Update an activity.
     *
     * @param int                       $id           Activity id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned activity
     *
     * @url PUT activities/{id}
     */
    public function updateActivity($id, $request_data = null)
    {
        return $this->_put('activities', $id, $request_data);
    }

    /**
     * Delete an activity.
     *
     * @param int $id Activity id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE activities/{id}
     */
    public function deleteActivity($id)
    {
        return $this->_delete('activities', $id);
    }

    /**
     * Validate an activity.
     *
     * @param int $id Activity id
     *
     * @return object Cleaned activity
     *
     * @url POST activities/{id}/validate
     */
    public function validateActivity($id)
    {
        return $this->_setStatus('activities', $id, 'validate');
    }

    /**
     * Move an activity back to draft.
     *
     * @param int $id Activity id
     *
     * @return object Cleaned activity
     *
     * @url POST activities/{id}/setdraft
     */
    public function setDraftActivity($id)
    {
        return $this->_setStatus('activities', $id, 'setDraft');
    }

    /**
     * Lock an activity.
     *
     * @param int $id Activity id
     *
     * @return object Cleaned activity
     *
     * @url POST activities/{id}/setlocked
     */
    public function setLockedActivity($id)
    {
        return $this->_setStatus('activities', $id, 'setLocked');
    }

    /**
     * Archive an activity.
     *
     * @param int $id Activity id
     *
     * @return object Cleaned activity
     *
     * @url POST activities/{id}/setarchived
     */
    public function setArchivedActivity($id)
    {
        return $this->_setStatus('activities', $id, 'setArchived');
    }

    // =================================================================
    // riskassessments
    // =================================================================

    /**
     * Get one risk assessment by id.
     *
     * @param int $id Risk assessment id
     *
     * @return object Cleaned risk assessment
     *
     * @url GET riskassessments/{id}
     */
    public function getRiskassessment($id)
    {
        return $this->_get('riskassessments', $id);
    }

    /**
     * List risk assessments.
     *
     * @param string $sortfield  Sort field
     * @param string $sortorder  Sort order (ASC or DESC)
     * @param int    $limit      Page size
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @url GET riskassessments
     */
    public function listRiskassessments($sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        return $this->_index('riskassessments', $sortfield, $sortorder, $limit, $page, $sqlfilters);
    }

    /**
     * Create a risk assessment.
     *
     * @param array<string, mixed>|null $request_data Risk assessment data
     *
     * @return int New risk assessment id
     *
     * @url POST riskassessments
     */
    public function createRiskassessment($request_data = null)
    {
        return $this->_post('riskassessments', $request_data);
    }

    /**
     * Update a risk assessment.
     *
     * @param int                       $id           Risk assessment id
     * @param array<string, mixed>|null $request_data Fields to update
     *
     * @return object Cleaned risk assessment
     *
     * @url PUT riskassessments/{id}
     */
    public function updateRiskassessment($id, $request_data = null)
    {
        return $this->_put('riskassessments', $id, $request_data);
    }

    /**
     * Delete a risk assessment.
     *
     * @param int $id Risk assessment id
     *
     * @return array<string, array<string, int|string>>
     *
     * @url DELETE riskassessments/{id}
     */
    public function deleteRiskassessment($id)
    {
        return $this->_delete('riskassessments', $id);
    }

    /**
     * Validate a risk assessment.
     *
     * @param int $id Risk assessment id
     *
     * @return object Cleaned risk assessment
     *
     * @url POST riskassessments/{id}/validate
     */
    public function validateRiskassessment($id)
    {
        return $this->_setStatus('riskassessments', $id, 'validate');
    }

    /**
     * Move a risk assessment back to draft.
     *
     * @param int $id Risk assessment id
     *
     * @return object Cleaned risk assessment
     *
     * @url POST riskassessments/{id}/setdraft
     */
    public function setDraftRiskassessment($id)
    {
        return $this->_setStatus('riskassessments', $id, 'setDraft');
    }

    /**
     * Lock a risk assessment.
     *
     * @param int $id Risk assessment id
     *
     * @return object Cleaned risk assessment
     *
     * @url POST riskassessments/{id}/setlocked
     */
    public function setLockedRiskassessment($id)
    {
        return $this->_setStatus('riskassessments', $id, 'setLocked');
    }

    /**
     * Archive a risk assessment.
     *
     * @param int $id Risk assessment id
     *
     * @return object Cleaned risk assessment
     *
     * @url POST riskassessments/{id}/setarchived
     */
    public function setArchivedRiskassessment($id)
    {
        return $this->_setStatus('riskassessments', $id, 'setArchived');
    }
}
