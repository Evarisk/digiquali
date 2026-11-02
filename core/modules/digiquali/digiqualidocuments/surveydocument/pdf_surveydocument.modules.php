<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/digiqualidocuments/surveydocument/pdf_surveydocument.modules.php
 * \ingroup digiquali
 * \brief   File of class to generate survey document pdf
 */

// Load DigiQuali libraries
require_once __DIR__ . '/../controldocument/pdf_controldocument.modules.php';
require_once __DIR__ . '/../../../../../../digiquali/class/survey.class.php';

/**
 * Class to build survey document pdf : the layout of the control document, with the score and the attendants of the survey
 */
class pdf_surveydocument extends pdf_controldocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'surveydocument';

    /**
     * @var string Translation key of the model name, written in the page footer
     */
    protected string $modelLabelKey = 'SurveyDocumentPDF';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db);

        $this->name        = $langs->trans('SurveyDocumentPDF');
        $this->description = $langs->trans('SurveyDocumentPDFDescription');
    }

    /**
     * Objects the survey is linked to, one per line, e.g. "Utilisateur — Crouzet Jean-Marc"
     *
     * @param  Survey    $survey      Survey
     * @param  Translate $outputLangs Output langs
     * @return string
     */
    private function getLinkedObjectsText(Survey $survey, $outputLangs): string
    {
        $survey->fetchObjectLinked(null, '', $survey->id, 'digiquali_survey');

        $linkedObjectsText = [];
        foreach (saturne_get_objects_metadata() as $objectMetadata) {
            foreach ($survey->linkedObjects[$objectMetadata['link_name']] ?? [] as $linkedObject) {
                $linkedObjectsText[] = $this->getLinkedObjectText($linkedObject, $objectMetadata, $outputLangs);
            }
        }

        return implode("\n", $linkedObjectsText);
    }

    /**
     * Rows and result of the summary of a survey : its date, linked objects, project, progress, scores and attendants,
     * then whether it is passed
     *
     * @param  Survey    $survey            Survey, with its lines
     * @param  Project   $project           Project of the survey
     * @param  string    $linkedObjectsText Objects the survey is linked to, see getLinkedObjectsText()
     * @param  array     $signatures        Signatories of the survey
     * @param  int       $totalQ            Number of questions
     * @param  int       $answeredQ         Number of questions answered
     * @param  Translate $outputLangs       Output langs
     * @return array                        Rows and result box, as drawSynthesisSection() takes them
     */
    private function getSurveySynthesis(Survey $survey, $project, string $linkedObjectsText, array $signatures, int $totalQ, int $answeredQ, $outputLangs): array
    {
        // Scores and result as the survey card computes them
        [$numberOfAnsweredQuestions, $numberOfQuestions, $correctPoints, $totalPoints, $atLeastOneIncorrectSubGroup] = $survey->calculatePoints();
        $correctAnswersRate = 0;
        if ($totalPoints > 0) {
            $correctAnswersRate = round($correctPoints / $totalPoints * 100, 2);
        }
        $isPassed = $survey->isCorrectFromPoints($correctPoints, $totalPoints) && !$atLeastOneIncorrectSubGroup;

        $projectText = (!empty($project->ref) ? $project->ref . ' — ' : '') . $project->title;
        $pointsLabel = strtolower($outputLangs->transnoentities($totalPoints > 1 ? 'Points' : 'Point'));

        // Rows without value (no project, no linked object) are left out rather than shown empty
        $cellData = [
            [
                'label' => $outputLangs->transnoentities('DateCreation'),
                'value' => dol_print_date($survey->date_creation, 'dayhour', 'auto', $outputLangs),
                'teal'  => true,
            ],
        ];
        if ($linkedObjectsText !== '') {
            $cellData[] = ['label' => $outputLangs->transnoentities('LinkedObject'), 'value' => $linkedObjectsText, 'multiline' => true];
        }
        if ($projectText !== '') {
            $cellData[] = ['label' => $outputLangs->transnoentities('Project'), 'value' => $projectText, 'multiline' => true];
        }
        $cellData[] = ['label' => $outputLangs->transnoentities('Progress'), 'value' => $answeredQ . ' / ' . $totalQ . ' ' . $outputLangs->transnoentities('Questions')];
        $cellData[] = ['label' => $outputLangs->transnoentities('SuccessScore'), 'value' => (!empty($survey->success_rate) ? price2num($survey->success_rate, 2) : 0) . ' %'];
        $cellData[] = ['label' => $outputLangs->transnoentities('ObtainedScore'), 'value' => $correctAnswersRate . ' % (' . $correctPoints . ' / ' . $totalPoints . ' ' . $pointsLabel . ')'];
        $cellData[] = ['label' => $outputLangs->transnoentities('Attendants'), 'value' => $this->buildAuditorsText($signatures, $outputLangs), 'multiline' => true];

        $result = [
            'label'  => $outputLangs->transnoentities('PDFSurveyResult'),
            'value'  => $isPassed ? 'OK' : 'KO',
            'color'  => $isPassed ? [40, 167, 69] : [220, 53, 69],
            'detail' => $correctAnswersRate . ' %',
        ];

        return [$cellData, $result];
    }

    /**
     * Write the PDF file to disk
     *
     * @param Object $objectDocument Object to generate (ex: survey)
     * @param Translate $outputLangs Lang object
     * @param string $srcTemplatePath
     * @param int $hidedetails
     * @param int $hidedesc
     * @param int $hideref
     * @param null|array $moreparams
     * @return int                         1=OK, <0=KO
     */
    public function write_file($objectDocument, $outputLangs, $srcTemplatePath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = array()): int
    {
        $moreparams = self::getMoreParam($objectDocument, $moreparams);

        $survey = $moreparams['object'] ?? null;
        if (empty($survey)) {
            $this->error = 'MissingSurveyObject';
            return -1;
        }

        $file = $this->prepareDocument($objectDocument, $outputLangs, $survey, $moreparams);
        if (is_int($file)) {
            return -1;
        }

        // ── Load related objects ─────────────────────────────────────────────

        $sheet            = new Sheet($this->db);
        $project          = new Project($this->db);
        $saturneSignature = new SaturneSignature($this->db);

        $sheet->fetch($survey->fk_sheet);
        $project->fetch($survey->projectid);
        $signatures = $saturneSignature->fetchSignatories($survey->id, $survey->element);

        // The points of the survey are computed from its lines
        $survey->fetchLines();

        $questions = $this->loadQuestions($survey, $sheet, new SurveyLine($this->db));

        // ── Page 1: synthesis ────────────────────────────────────────────────

        $pdf = $this->createPdf($survey, $outputLangs);
        $pdf->AddPage();
        $this->drawTitleArea($pdf, $survey, $sheet, $project, $questions['total'], $outputLangs);
        $this->drawSectionBanner($pdf, $outputLangs->transnoentities('PDFSurveySummary'));
        [$cellData, $result] = $this->getSurveySynthesis($survey, $project, $this->getLinkedObjectsText($survey, $outputLangs), $signatures, $questions['total'], $questions['answered'], $outputLangs);
        $this->drawSynthesisSection($pdf, $survey, $cellData, $result, $outputLangs);

        // ── Questions section ────────────────────────────────────────────────

        $this->drawQuestionsSection($pdf, $survey, $questions, $outputLangs);

        // ── Signatures ───────────────────────────────────────────────────────

        $pdf->Ln(8);
        $this->checkPageBreak($pdf, 40);
        $this->drawSignatureSection($pdf, $signatures, 'Attendants', ['ExtSocietyAttendant', 'Attendant'], $outputLangs);

        // ── Action plan (last page) ──────────────────────────────────────────

        $this->drawActionPlanSection($pdf, $questions['questionsAndGroups'], $questions['tasks'], $outputLangs);

        return $this->saveDocument($pdf, $file);
    }
}
