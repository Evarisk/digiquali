<?php
/**
 *    \file       htdocs/custom/digiquali/documents/doctemplates/controldocument/controldocument.pdf.php
 *    \ingroup    control document pdf
 *    \brief      This template is to create a control pdf
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

/**
 *    Class to build control documents
 */
class pdf_control_document
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

    /**
     * @var string model name
     */
    public $name;

    /**
     * @var string model description (short text)
     */
    public $description;

    /**
     * @var string document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.6 = array(5, 6)
     */
    public $phpmin = array(5, 6);

    /**
     * Dolibarr version of the loaded document
     * @var string
     */
    public $version = 'dolibarr';

    /**
     * @var int page_largeur
     */
    public $page_largeur;

    /**
     * @var int page_hauteur
     */
    public $page_hauteur;

    /**
     * @var array format
     */
    public $format;

    /**
     * @var int marge_gauche
     */
    public $marge_gauche;

    /**
     * @var int marge_droite
     */
    public $marge_droite;

    /**
     * @var int marge_haute
     */
    public $marge_haute;

    /**
     * @var int marge_basse
     */
    public $marge_basse;

    /**
     * Page orientation
     * @var string 'P' or 'Portait' (default), 'L' or 'Landscape'
     */
    private $orientation = 'P';

    /**
     * Issuer
     * @var Societe Object that emits
     */
    public $emetteur;

    /**
     * @var string Module
     */
    public string $module = 'digiquali';

    /**
     * @var string Document type
     */
    public string $document_type = 'controldocument';

    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        $this->db           = $db;
        $this->name         = 'controldocument';
        $this->description  = $langs->trans("ControlDocumentPDFDescription");
        $this->type         = 'pdf_control_document';
        $this->format       = 'A4';
        $this->orientation  = 'P';
        $this->marge_gauche = 5;
        $this->marge_droite = 5;
        $this->marge_haute  = 5;
        $this->marge_basse  = 5;
    }

    /**
     *  Add a page in the pdf if the height is between two pages
     *
     * @param Object $pdf
     * @param float $neededHeight
     */
    public function checkPageBreak($pdf, $neededHeight) {
        $bottomMargin = $pdf->getBreakMargin();
        $pageHeight   = $pdf->getPageHeight();
        $currentY     = $pdf->GetY();

        if ($currentY + $neededHeight + $bottomMargin > $pageHeight) {
            $pdf->AddPage();
        }
    }

    /**
     *  Write the PDF file to disk
     *
     * @param Object $object Object to generate (ex: control)
     * @param Translate $outputlangs Lang object
     * @param string $srctemplatepath
     * @param int $hidedetails
     * @param int $hidedesc
     * @param int $hideref
     * @param null|array $moreparams
     * @return int                         1=OK, <0=KO
     */
    public function write_file($objectDocument, $outputlangs, $srcTemplatePath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = array()): int
    {
        global $conf, $langs, $user, $hookmanager, $action;

        $langs->load("main");
        $langs->load("digiquali@digiquali");

        $id = GETPOST('id');

        $object           = new Control($this->db);
        $sheet            = new Sheet($this->db);
        $questions        = new Question($this->db);
        $controlDet       = new ControlLine($this->db);
        $user             = new User($this->db);
        $project          = new Project($this->db);
        $saturneSignature = new SaturneSignature($this->db);
        $controlEquipment = new ControlEquipment($this->db);
        $productLot       = new Productlot($this->db);

        $object->fetch($id);
        $sheet->fetch($object->fk_sheet);
        $questions->fetchObjectLinked($sheet->id, 'digiquali_sheet', null, 'digiquali_question');
        $user->fetch($object->fk_user_controller);
        $project->fetch($object->projectid);

        $signatures        = $saturneSignature->fetchSignatories($object->id, $object->element);
        $controlEquipments = $controlEquipment->fetchFromParent($object->id);

        foreach ($questions->linkedObjects['digiquali_question'] as $question) {
            $controlDets = $controlDet->fetchFromParentWithQuestion($id, $question->id);
            foreach ($controlDets as $data) {
                $answerRef[] = $data->ref;
                $answer[]    = $data->answer;
            }
        }

        $objectsMetadata = saturne_get_objects_metadata();
        $object->fetchObjectLinked('', '', $object->id, 'digiquali_control');
        $linkedObjectType = key($object->linkedObjects);
        foreach ($objectsMetadata as $objectMetadata) {
            if ($objectMetadata['conf'] == 0 || $objectMetadata['link_name'] != $linkedObjectType) {
                continue;
            }
            $linkedObject = $object->linkedObjects[$objectMetadata['link_name']][key($object->linkedObjects[$objectMetadata['link_name']])];
        }
        $diroutput = $conf->digiquali->dir_output ?? '';
        if (empty($diroutput)) {
            $this->error = "Configuration manquante: conf->digiquali->dir_output";
            return -1;
        }
        $ref = !empty($object->ref) ? dol_sanitizeFileName($object->ref) : 'no_ref';
        $dir = $diroutput . "/" . $ref;
        if (!file_exists($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = "Impossible de créer le répertoire: $dir";
                return -1;
            }
        }
        $date              = dol_print_date(dol_now(), "dayxcard");
        $file_name         = dol_sanitizeFileName($date . "_" . $ref . "_controldocument") . ".pdf";
        $file              = $dir . "/" . $file_name;
        $pdf               = pdf_getInstance($this->format);
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $pdf->SetAutoPageBreak(1, $this->marge_basse);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

        if (class_exists("TCPDF")) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->AddPage($this->orientation);
        $pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size);

        $widthFirstColumn  = 50;
        $widthSecondColumn = 80;
        $widthThirdColumn  = 70;

        $pdf->Cell($widthFirstColumn, 10, $langs->transnoentities('Ref') . $langs->transnoentities('Control'), 1, 0, 'L');
        $pdf->Cell($widthSecondColumn, 10, $object->ref, 1, 0, 'L');
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $path  = $conf->digiquali->multidir_output[$conf->entity] . '/control/' . $object->ref . '/photos';
        $thumb = saturne_get_thumb_name($object->photo);
        $image = $path . '/thumbs/' . $thumb;

        if (!empty($object->photo)) {
            $pdf->Image($image, $x, $y * 2.5, $widthThirdColumn, 0, 'PNG', '', 'C', false, 300, '', false, false, 1);
            $pdf->SetXY($x, $y);
            $pdf->Cell($widthThirdColumn, 60 , '', 1, 0, 'C', false, '',  0, false, 'T', 'M');
        } else {
            $pdf->Cell($widthThirdColumn, 60, $langs->transnoentities('NoPhoto'), 1, 0, 'C' );
        }
        $pdf->Ln(10);

        $linkedElement = json_decode($sheet->element_linked, true);
        $linkedElement = array_keys($linkedElement)[0];
        if ($linkedElement == 'productlot') {
            $linkedElement = 'batch';
        }

        $pdf->Cell($widthFirstColumn, 10, $langs->transnoentities('ControlObject'), 1, 0, 'L');
        $pdf->Cell($widthSecondColumn, 10, $langs->transnoentities(ucfirst($linkedElement)) . ' : ' . $linkedObject->$linkedElement, 1, 0, 'L');
        $pdf->Ln(10);

        $pdf->Cell($widthFirstColumn, 10, $langs->transnoentities('ControlDate'), 1, 0, 'L');
        $pdf->Cell($widthSecondColumn, 10, dol_print_date($object->control_date, 'day'), 1, 0, 'L');
        $pdf->Ln(10);

        $pdf->Cell($widthFirstColumn, 10, $langs->transnoentities('Project'), 1, 0, 'L');
        $pdf->Cell($widthSecondColumn, 10, $project->title, 1, 0, 'L');
        $pdf->Ln(10);

        $pdf->Cell($widthFirstColumn, 10, $langs->transnoentities('Ref') . $langs->transnoentities('Sheet'), 1, 0, 'L');
        $pdf->Cell($widthSecondColumn, 10, $sheet->ref . ' - ' . $sheet->label, 1, 0, 'L');
        $pdf->Ln(10);

        $pdf->SetFillColor(153, 204, 204);
        $pdf->SetFont('', 'B', $default_font_size+2);

        $pdf->Cell($widthFirstColumn + $widthSecondColumn, 12, $langs->transnoentities('VerdictObject'), 1, 0, 'C', true);

        $verdict = '';
        if ($object->verdict == 1) {
            $verdict = 'OK';
        } elseif ($object->verdict == 2) {
            $verdict = 'KO';
        }

        $pdf->Cell($widthThirdColumn, 12, $verdict, 1, 1, 'C', true);

        $pdf->Ln(5  );

        $pdf->SetFont('', '', $default_font_size);
        $pdf->Cell(0, 8, $langs->transnoentities('NoteControl') . ': ' . $object->note_public, 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(0, 8, $langs->transnoentities('ControlObservationList'), 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetFillColor(153, 204, 204);

        $widthQuestion    = 20;
        $widthTitle       = 80;
        $widthRefAnswer   = 20;
        $widthObservation = 50;
        $widthAnswer      = 30;

        $pdf->writeHTMLCell($widthQuestion, 10, '', '', '<b>' .  $langs->transnoentities('Ref') . '</b> <span style="font-size:8px;">' .  $langs->transnoentities('Question') . '</span>', 1, 0, true, true, 'C');
        $pdf->writeHTMLCell($widthTitle, 10, '', '', '<b>' .  $langs->transnoentities('Title') . ' - ' . $langs->transnoentities('Description') .'</b>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthRefAnswer, 10, '', '', '<b>' .  $langs->transnoentities('Ref') . '</b> <span style="font-size:8px;">' .  $langs->transnoentities('Answer') . '</span>', 1, 0, true, true, 'C');
        $pdf->writeHTMLCell($widthObservation, 10, '', '', '<b>' .  $langs->transnoentities('Observations') . '</b>', 1, 0, true, true, 'C');
        $pdf->writeHTMLCell($widthAnswer, 10, '', '', '<b>' .  $langs->transnoentities('Answer') . '</b>', 1, 1, true, true, 'C');

        $pdf->SetFont('', '', $default_font_size);
        $controlLine = 0;

        foreach ($questions->linkedObjects['digiquali_question'] as $question) {
            $hRef       = $pdf->getStringHeight($widthQuestion, $question->ref);
            $hTitle     = $pdf->getStringHeight($widthTitle, $question->label . "\nDescription : " . $question->description);
            $hRefAnswer = $pdf->getStringHeight($widthRefAnswer, $question->ref_answer);
            $hComment   = $pdf->getStringHeight($widthObservation, $question->comment);
            $hAnswer    = $pdf->getStringHeight($widthAnswer, $question->answer);
            $h          = max($hRef, $hTitle, $hRefAnswer, $hComment, $hAnswer);
            $x          = $pdf->GetX();
            $y          = $pdf->GetY();
            $this->checkPageBreak($pdf, $h);

            $pdf->MultiCell($widthQuestion, $h, strip_tags($question->ref), 1, 'C', false, 0, $x, $y, true, 0, false, true, $h, 'M');
            $pdf->MultiCell($widthTitle, $h, strip_tags($question->label) . "\nDescription : " . html_entity_decode(strip_tags($question->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 1, 'C', false, 0, $x + $widthQuestion, $y, true, 0, false, true, $h, 'M');
            $pdf->MultiCell($widthRefAnswer, $h, strip_tags($answerRef[$controlLine]), 1, 'C', false, 0, $x + $widthQuestion + $widthTitle, $y, true, 0, false, true, $h, 'M');
            $pdf->MultiCell($widthObservation, $h, strip_tags($object->lines[$controlLine]->comment), 1, 'C', false, 0, $x + $widthQuestion + $widthTitle + $widthRefAnswer, $y, true, 0, false, true, $h, 'M');
            $pdf->MultiCell($widthAnswer, $h, strip_tags($answer[$controlLine]), 1, 'C', false, 1, $x + $widthQuestion + $widthTitle + $widthRefAnswer + $widthObservation, $y, true, 0, false, true, $h, 'M');
            $controlLine++;
        }

        $pdf->Ln(10);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(0, 8, 'Liste des photos liées aux questions', 0, 1, 'L');
        $pdf->Ln(3);

        foreach ($questions->linkedObjects['digiquali_question'] as $question) {
            $path = $conf->digiquali->multidir_output[$conf->entity] . '/control/' . $object->ref . '/answer_photo/' . $question->ref . '/thumbs/';
            if (!is_dir($path)) {
                continue;
            }
            $files = array_values(array_diff(scandir($path), ['.', '..']));
            if (empty($files)) {
                continue;
            }
            $pdf->Ln(6);
            $pdf->SetFont('', '', 10);

            $totalWidth      = 200;
            $imagesPerRow    = 4;
            $cellHeight      = 35;
            $labelWidth      = 70;
            $photoCols       = $imagesPerRow - 1;
            $photoWidth      = ($totalWidth - $labelWidth) / $photoCols;
            $imgWidth        = 25;
            $rows            = ceil(count($files) / $photoCols);
            $estimatedHeight = ($rows * ($cellHeight + 8)) + 10;
            $this->checkPageBreak($pdf, $estimatedHeight);
            $photoIndex = 0;

            for ($r = 0; $r < $rows; $r++) {
                if ($r == 0) {
                    $pdf->MultiCell($labelWidth, $cellHeight, "Question :\n" . strip_tags($question->label), 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
                } else {
                    $pdf->MultiCell($labelWidth, $cellHeight, '', 1, 'L', false, 0, '', '', true, 0, false, true, $cellHeight, 'M');
                }
                for ($p = 0; $p < $photoCols; $p++) {
                    if ($photoIndex < count($files)) {
                        $imagePath = realpath($path . '/' . $files[$photoIndex]);
                        $imagePath = str_replace('\\', '/', $imagePath);
                        $xImgCell  = $pdf->GetX();
                        $yImgCell  = $pdf->GetY();

                        $pdf->Cell($photoWidth, $cellHeight, '', 1, 0, 'C');

                        if (file_exists($imagePath) && is_readable($imagePath)) {
                            $imgX = $xImgCell + ($photoWidth - $imgWidth) / 2;
                            $imgY = $yImgCell + 5;
                            $pdf->Image($imagePath, $imgX, $imgY, $imgWidth, 0, 'PNG');
                        }
                        $photoIndex++;
                    } else {
                        $pdf->Cell($photoWidth, $cellHeight, '', 1, 0, 'C');
                    }
                }
                $pdf->Ln($cellHeight);
                $pdf->Cell($labelWidth, 8, '', 1, 0, 'L');
                for ($p = 0; $p < $photoCols; $p++) {
                    $index = ($r * $photoCols) + $p;
                    if ($index < count($files)) {
                        $pdf->Cell($photoWidth, 8, 'Photo ' . ($index + 1), 1, 0, 'C');
                    } else {
                        $pdf->Cell($photoWidth, 8, '', 1, 0, 'C');
                    }
                }
                $pdf->Ln(8);
            }
        }

        $pdf->Ln(10);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(0, 8, $langs->transnoentities('ControlEquipementList'), 0, 1, 'L');
        $pdf->Ln(3);
        $widthRef  = 20;
        $widthLib  = 40;
        $widthLot  = 30;
        $widthDesc = 60;
        $widthDluo = 20;
        $widthRest = 30;

        $pdf->writeHTMLCell($widthRef, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('Ref') . '</span><br><span style="font-size:8pt;">' .  $langs->transnoentities('Equipement') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthLib, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('Label') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthLot, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('Ref') . '</span><br><span style="font-size:8pt;">' .  $langs->transnoentities('batch_number') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthDesc, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('Description') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthDluo, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('OptimalExpirationDate') . '/</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthRest, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('EstimatedLife') . '</span></div>', 1, 1, true, true, 'C', true);

        $pdf->SetFont('', '', $default_font_size);

        if (!empty($controlEquipments)) {
            foreach ($controlEquipments as $controlEquipement) {
                $equipement = json_decode($controlEquipement->json);
                $productLot->fetch($controlEquipement->fk_lot);

                // --- On calcule la hauteur max ---
                $h1 = $pdf->getStringHeight($widthRef,  $controlEquipement->ref);
                $h2 = $pdf->getStringHeight($widthLib,  $equipement->label);
                $h3 = $pdf->getStringHeight($widthLot,  $productLot->batch);
                $h4 = $pdf->getStringHeight($widthDesc, $equipement->description);
                $h5 = $pdf->getStringHeight($widthDluo, $equipement->dluo);
                $h6 = $pdf->getStringHeight($widthRest, $equipement->lifetime);
                $h  = max($h1, $h2, $h3, $h4, $h5, $h6);
                $this->checkPageBreak($pdf, $h);
                $x  = $pdf->GetX();
                $y  = $pdf->GetY();

                $pdf->MultiCell($widthRef,  $h, $controlEquipement->ref,       1, 'C', 0, 0, $x, $y);
                $pdf->MultiCell($widthLib,  $h, $equipement->label,            1, 'L', 0, 0, $x+$widthRef, $y);
                $pdf->MultiCell($widthLot,  $h, $productLot->batch,            1, 'C', 0, 0, $x+$widthRef+$widthLib, $y);
                $pdf->MultiCell($widthDesc, $h, $equipement->description,      1, 'L', 0, 0, $x+$widthRef+$widthLib+$widthLot, $y);
                $pdf->MultiCell($widthDluo, $h, dol_print_date($equipement->dluo),             1, 'C', 0, 0, $x+$widthRef+$widthLib+$widthLot+$widthDesc, $y);
                $pdf->MultiCell($widthRest, $h, $equipement->lifetime,         1, 'C', 0, 0, $x+$widthRef+$widthLib+$widthLot+$widthDesc+$widthDluo, $y);

                $pdf->Ln($h);
            }
        } else {
            $pdf->Cell($widthRef + $widthLib + $widthLot + $widthDesc + $widthDluo + $widthRest, 8, "Aucun équipement trouvé", 1, 1, 'C');
        }

        $pdf->Ln(10);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(0, 8, $langs->transnoentities('LinkedContactsControl'), 0, 1, 'L');
        $pdf->Ln(3);

        $widthName = 40;
        $widthPre = 40;
        $widthRole = 40;
        $widthDate = 30;
        $widthSign = 50;

        $pdf->SetFillColor(153, 204, 204);
        $pdf->writeHTMLCell($widthName, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('LastName') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthPre, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('FirstName') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthRole, 10, '', '', '<div style="text-align:center;">' .  $langs->transnoentities('Role') . '</div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthDate, 10, '', '', '<div style="text-align:center;">' .  $langs->transnoentities('SignatureDate') . '</div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthSign, 10, '', '', '<div style="text-align:center;">' .  $langs->transnoentities('Signature') . '</div>', 1, 1, true, true, 'C', true);

        $pdf->SetFont('', '', $default_font_size);

        if (!empty($signatures)) {
            foreach ($signatures as $signature) {
                if ($signature->role == 'ExtSocietyAttendant' || $signature->role == 'Attendant') {
                    // Hauteur max
                    $h1 = $pdf->getStringHeight($widthName,  $signature->lastname);
                    $h2 = $pdf->getStringHeight($widthPre,  $signature->firstname);
                    $h3 = $pdf->getStringHeight($widthRole, $signature->role);
                    $h4 = $pdf->getStringHeight($widthDate, $signature->signature_date);
                    $h  = max($h1, $h2, $h3, $h4, 20); // au moins 20 pour signature image
                    $this->checkPageBreak($pdf, $h);
                    $x  = $pdf->GetX();
                    $y  = $pdf->GetY();

                    $pdf->MultiCell($widthName,  $h, $signature->lastname,       1, 'C', 0, 0, $x, $y);
                    $pdf->MultiCell($widthPre,  $h, $signature->firstname,      1, 'C', 0, 0, $x + $widthName, $y);
                    $pdf->MultiCell($widthRole, $h, $langs->transnoentities($signature->role), 1, 'C', 0, 0, $x + $widthName + $widthPre, $y);
                    $pdf->MultiCell($widthDate, $h, dol_print_date($signature->signature_date, 'day'), 1, 'C', 0, 0, $x + $widthName + $widthPre + $widthRole, $y);
                    if (!empty($signature->signature)) {
                        $encoded_image  = explode(",", $signature->signature)[1];
                        $signatureImage = base64_decode($encoded_image);
                        $pdf->Image('@' . $signatureImage, $x + $widthName + $widthPre + $widthRole + $widthDate, $y, $widthSign, $h, 'PNG', '', 'C', false, 300, '', false, false, 1);
                        $pdf->SetXY($x + $widthName + $widthPre + $widthRole + $widthDate, $y);
                        $pdf->Cell($widthSign, $h, '', 1, 0, 'C');
                    } else {
                        $pdf->MultiCell($widthSign,  $h, $langs->transnoentities('NA'), 1, 'C', 0, 0, $x + $widthName + $widthPre + $widthRole + $widthDate, $y);
                    }

                    $pdf->Ln($h);
                }
            }
        } else {
            $pdf->Cell($widthName+$widthPre+$widthRole+$widthDate+$widthSign, 8, "Aucun contact associé", 1, 1, 'C');
        }

        $pdf->Ln(10);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(0, 8, $langs->transnoentities('Controller'), 0, 1, 'L');
        $pdf->Ln(3);

        $widthName  = 55;
        $widthPre  = 55;
        $widthDate = 40;
        $widthSign = 50;

        $pdf->SetFillColor(153, 204, 204);
        $pdf->writeHTMLCell($widthName, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('LastName') . '</span><br><span style="font-size:8pt;">' .  $langs->transnoentities('Controller') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthPre, 10, '', '', '<div style="text-align:center;"><span style="font-size:10pt;">' .  $langs->transnoentities('FirstName') . '</span><br><span style="font-size:8pt;">' .  $langs->transnoentities('Controller') . '</span></div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthDate, 10, '', '', '<div style="text-align:center;">' .  $langs->transnoentities('SignatureDate') . '</div>', 1, 0, true, true, 'C', true);
        $pdf->writeHTMLCell($widthSign, 10, '', '', '<div style="text-align:center;">' .  $langs->transnoentities('Signature') . '</div>', 1, 1, true, true, 'C', true);

        $pdf->SetFont('', '', $default_font_size);

        if (!empty($signatures)) {
            foreach ($signatures as $signature) {
                if ($signature->role == 'Controller') {
                    $h1 = $pdf->getStringHeight($widthName,  $signature->lastname);
                    $h2 = $pdf->getStringHeight($widthPre,  $signature->firstname);
                    $h3 = $pdf->getStringHeight($widthDate, $signature->signature_date);
                    $h  = max($h1, $h2, $h3, 20);
                    $this->checkPageBreak($pdf, $h);
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();

                    $pdf->MultiCell($widthName,  $h, $signature->lastname,       1, 'C', 0, 0, $x, $y);
                    $pdf->MultiCell($widthPre,  $h, $signature->firstname,      1, 'C', 0, 0, $x + $widthName, $y);
                    $pdf->MultiCell($widthDate, $h, dol_print_date($signature->signature_date, 'day'), 1, 'C', 0, 0, $x + $widthName + $widthPre, $y);
                    if (!empty($signature->signature)) {
                        $encoded_image  = explode(",", $signature->signature)[1];
                        $signatureImage = base64_decode($encoded_image);
                        $pdf->Image('@' . $signatureImage, $x + $widthName + $widthPre + $widthDate, $y, $widthSign - 4, $h - 4, 'PNG', '', 'C', false, 300, '', false, false, 1);
                        $pdf->SetXY($x + $widthName + $widthPre + $widthDate, $y);
                        $pdf->Cell($widthSign, $h, '', 1, 0, 'C');
                    } else {
                        $pdf->MultiCell($widthSign, $h, $langs->transnoentities('NA'), 1, 'C', 0, 0, $x + $widthName + $widthPre + $widthDate, $y);
                    }

                    $pdf->Ln($h);
                }
            }
        } else {
            $pdf->Cell($widthName+$widthPre+$widthDate+$widthSign, 8, "Aucun contrôleur", 1, 1, 'C');
        }

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $e) {
            $this->error = "Erreur lors de la création du PDF : " . $e->getMessage();
            return -1;
        }
        if (!file_exists($file)) {
            $this->error = "PDF non généré (fichier introuvable après Output) : $file";
            return -1;
        }

        if (is_object($objectDocument) && method_exists($objectDocument, "setValueFrom")) {
            $res = $objectDocument->setValueFrom(
                "last_main_doc",
                $file_name,
                '',
                null,
                '',
                '',
                $user,
                '',
                ''
            );
            if ($res <= 0 && !empty($objectDocument->error)) {
                $this->error = $objectDocument->error;
                return -1;
            }
        }

        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
        }

        $this->result = ['fullpath' => $file];

        return 1;
    }
}

?>
