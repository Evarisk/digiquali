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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/digiqualidocuments/controldocument/pdf_controldocument.modules.php
 * \ingroup digiquali
 * \brief   File of class to generate control document pdf
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../../../../../digiquali/class/sheet.class.php';
require_once __DIR__ . '/../../../../../../digiquali/class/question.class.php';
require_once __DIR__ . '/../../../../../../digiquali/class/questiongroup.class.php';
require_once __DIR__ . '/../../../../../../digiquali/class/answer.class.php';
require_once __DIR__ . '/../../../../../../digiquali/class/control.class.php';

/**
 * Class to build control document pdf
 */
class pdf_controldocument extends SaturneDocumentModel
{
    /**
     * @var DoliDB Database handler
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
     * @var string Module
     */
    public string $module = 'digiquali';

    /**
     * @var string Document type
     */
    public string $document_type = 'controldocument';

    // Color palette
    private array $colorNavy  = [26, 45, 64];
    private array $colorTeal  = [0, 157, 170];
    private array $colorGray  = [108, 117, 125];
    private array $colorLight = [245, 247, 250];
    private array $colorWhite = [255, 255, 255];
    private array $colorBlack = [40, 40, 40];

    // Answer pictogram → display config (fa = UTF-8 encoded FontAwesome 5 solid glyph)
    private array $answerConfig = [
        'check'        => ['abbrev' => 'C',   'rgb' => [56, 161, 105],  'fa' => "\xef\x80\x8c"], // fa-check      U+F00C
        'times'        => ['abbrev' => 'NC',  'rgb' => [220, 53, 69],   'fa' => "\xef\x80\x8d"], // fa-times      U+F00D
        'tools'        => ['abbrev' => 'TO',  'rgb' => [255, 152, 0],   'fa' => "\xef\x9f\x99"], // fa-tools      U+F7D9
        'N/A'          => ['abbrev' => 'N/A', 'rgb' => [108, 117, 125], 'fa' => null],
        'eye'          => ['abbrev' => 'Commentaire', 'rgb' => [26, 41, 128],   'fa' => "\xef\x81\xae"], // fa-eye        U+F06E
        'level-up-alt' => ['abbrev' => 'PP',  'rgb' => [255, 152, 0],   'fa' => "\xef\x8e\xbf"], // fa-level-up-alt U+F3BF
        'star'         => ['abbrev' => 'PF',  'rgb' => [103, 58, 183],  'fa' => "\xef\x80\x85"], // fa-star       U+F005
    ];

    private string $faFontName = '';

    private string $generatedFilename = '';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = $langs->trans('ControlDocumentPDF');
        $this->description = $langs->trans('ControlDocumentPDFDescription');
        $this->type        = 'pdf';
        $this->height      = 10;
    }

    /**
     * Kept for interface compatibility — not called by write_file.
     */
    protected function _pagehead(&$pdf, $object, $sheet, $project, $outputLangs, $defaultFontSize)
    {
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function getAnswerConfig(?string $pictogram): array
    {
        return $this->answerConfig[$pictogram] ?? ['abbrev' => '?', 'rgb' => [128, 128, 128]];
    }

    private function isChoiceType(string $type): bool
    {
        return in_array($type, ['UniqueChoice', 'OkKo', 'OkKoToFixNonApplicable', 'MarqueNF', 'Iso9001', 'MultipleChoices']);
    }

    private function getAbbrevConfig(string $abbrev): array
    {
        $map = [
            'C'   => [56, 161, 105],
            'NC'  => [220, 53, 69],
            'Commentaire' => [26, 41, 128],
            'PP'  => [255, 152, 0],
            'PF'  => [103, 58, 183],
            'N/A' => [108, 117, 125],
        ];
        return $map[$abbrev] ?? [128, 128, 128];
    }

    private function cleanText(?string $text): string
    {
        return html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function fillRect($pdf, float $x, float $y, float $w, float $h, array $rgb): void
    {
        $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
        $pdf->Rect($x, $y, $w, $h, 'F');
    }

    private function drawSectionTitle($pdf, string $text): void
    {
        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $y       = $pdf->GetY();
        $barH    = 8;
        $this->fillRect($pdf, $this->marge_gauche, $y, 3, $barH, $this->colorTeal);
        $this->fillRect($pdf, $this->marge_gauche + 3, $y, $usableW - 3, $barH, [232, 246, 248]);
        $pdf->SetFont('', 'B', 9);
        $pdf->SetTextColor(...$this->colorNavy);
        $pdf->SetXY($this->marge_gauche + 7, $y + ($barH - 5) / 2);
        $pdf->Cell(0, 5, $text, 0, 1, 'L');
        $pdf->Ln(3);
    }

    private function drawAnswerCircle($pdf, float $cx, float $cy, float $r, string $abbrev, array $rgb, ?string $faChar = null): void
    {
        $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
        $pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
        $pdf->Circle($cx, $cy, $r, 0, 360, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $useFa = !empty($faChar) && !empty($this->faFontName);

        // Save current font so we can restore it after the icon
        $prevFamily = $pdf->getFontFamily();
        $prevStyle  = $pdf->getFontStyle();
        $prevSize   = $pdf->getFontSizePt();

        if ($useFa) {
            // Material Design recommends min 16sp for small icons → ≥8pt in PDF, scale ≈ 75% of circle diameter
            $fontSize = max(8, min(12, (int)($r * 2.0)));
            $pdf->SetFont($this->faFontName, '', $fontSize);
        } else {
            $baseSize = max(6, min(9, (int)($r * 1.5)));
            $len      = mb_strlen($abbrev);
            $fontSize = $len >= 4 ? max(5, $baseSize - 2) : ($len === 3 ? max(5, $baseSize - 1) : $baseSize);
            $pdf->SetFont('', 'B', $fontSize);
        }
        $char  = $useFa ? $faChar : $abbrev;
        $textH = $fontSize * 0.35;
        $pdf->SetXY($cx - $r, $cy - $textH - 0.5);
        $pdf->Cell($r * 2, $textH * 2 + 1, $char, 0, 0, 'C');

        // Restore previous font
        if (!empty($prevFamily)) {
            $pdf->SetFont($prevFamily, $prevStyle, $prevSize);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(128, 128, 128);
    }

    // ─── Per-page decorations ──────────────────────────────────────────────────

    private function drawTopRight($pdf): void
    {
    }

    private function drawPageFooter($pdf): void
    {
        global $mysoc, $outputLangs;

        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $footerY = $pdf->getPageHeight() - $this->marge_basse - 4;

        $parts = [];
        if (!empty($mysoc->name)) {
            $parts[] = $mysoc->name;
        }
        if (!empty($mysoc->address)) {
            $parts[] = $mysoc->address;
        }
        if (!empty($mysoc->url)) {
            $parts[] = $mysoc->url;
        }
        if (!empty($mysoc->phone)) {
            $parts[] = $mysoc->phone;
        }
        $footerLeft = implode(' · ', $parts);

        $pageLabel = 'Page ' . $pdf->PageNo() . ' / ' . $pdf->getAliasNbPages();

        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetTextColor(160, 160, 160);
        $pdf->SetFont('', '', 7);

        // Row 1: generated filename (left) · model name (right)
        if (!empty($this->generatedFilename)) {
            $modelLabel = !empty($outputLangs) ? $outputLangs->convToOutputCharset('Modèle : ') . $this->name : 'Modele : ' . $this->name;
            $pdf->SetXY($this->marge_gauche, $footerY - 5);
            $pdf->Cell($usableW * 0.65, 4, $this->generatedFilename, 0, 0, 'L');
            $pdf->Cell($usableW * 0.35, 4, $modelLabel, 0, 0, 'R');
        }

        // Row 2: company info (left) · page number (right)
        $pdf->SetXY($this->marge_gauche, $footerY);
        $pdf->Cell($usableW * 0.75, 4, $footerLeft, 0, 0, 'L');
        $pdf->Cell($usableW * 0.25, 4, $pageLabel, 0, 0, 'R');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, $this->marge_basse + 8);
    }

    // ─── Page break management ─────────────────────────────────────────────────

    public function checkPageBreak($pdf, $neededHeight): void
    {
        $pageHeight  = $pdf->getPageHeight();
        $breakMargin = $this->marge_basse + 8;
        $currentY    = $pdf->GetY();

        if ($currentY + (float)$neededHeight + $breakMargin > $pageHeight) {
            $this->drawPageFooter($pdf);
            $pdf->AddPage();
            $this->drawTopRight($pdf);
            $pdf->SetY($this->marge_haute);
        }
    }

    // ─── Drawing blocks ────────────────────────────────────────────────────────

    private function drawTitleArea($pdf, $control, $sheet, $project, int $totalQ, $outputLangs): void
    {
        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $x       = $this->marge_gauche;
        $y       = $this->marge_haute;

        // Company logo (fallback: DQ badge)
        global $conf, $mysoc;
        $badgeSz      = 14;
        $logoBlockW   = $badgeSz;
        $logoDisplayed = false;
        if (!empty($mysoc->logo)) {
            $logodir  = $conf->mycompany->dir_output;
            if (!empty($conf->multicompany->enabled)) {
                $logodir = $conf->mycompany->multidir_output[$conf->entity];
            }
            $logoPath = (!empty($mysoc->logo_small) && is_readable($logodir . '/logos/thumbs/' . $mysoc->logo_small))
                ? $logodir . '/logos/thumbs/' . $mysoc->logo_small
                : $logodir . '/logos/' . $mysoc->logo;
            if (is_readable($logoPath)) {
                $info = @getimagesize($logoPath);
                if ($info && $info[0] > 0 && $info[1] > 0) {
                    $logoBlockW = min(40, ($info[0] / $info[1]) * $badgeSz);
                }
                $pdf->Image($logoPath, $x, $y, 0, $badgeSz);
                $logoDisplayed = true;
            }
        }
        if (!$logoDisplayed) {
            $this->fillRect($pdf, $x, $y, $badgeSz, $badgeSz, $this->colorNavy);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('', 'B', 9);
            $pdf->SetXY($x, $y + 4);
            $pdf->Cell($badgeSz, 6, 'DQ', 0, 0, 'C');
        }

        // Title
        $titleX = $x + $logoBlockW + 3;
        $titleW = $usableW - $logoBlockW - 3 - 30;
        $pdf->SetTextColor(...$this->colorNavy);
        $pdf->SetFont('', 'B', 13);
        $pdf->SetXY($titleX, $y + 1);
        $pdf->Cell($titleW, 7, $control->ref . ' — ' . $sheet->label, 0, 0, 'L');

        // DigiQuali branding (top-right)
        $pdf->SetTextColor(160, 160, 160);
        $pdf->SetFont('', '', 7);
        $pdf->SetXY($x, $y + 1);
        $pdf->Cell($usableW, 5, 'DigiQuali · digiquali.com', 0, 0, 'R');

        // Subtitle
        $subtitleParts = [];
        if (!empty($sheet->description)) {
            $subtitleParts[] = $this->cleanText($sheet->description);
        }
        $subtitleParts[] = $outputLangs->convToOutputCharset('Modèle ') . $sheet->ref;
        if (!empty($project->ref)) {
            $subtitleParts[] = 'Projet ' . $project->ref;
        }
        $subtitleParts[] = $totalQ . ' questions';
        $subtitle = implode(' · ', $subtitleParts);

        $pdf->SetTextColor(...$this->colorGray);
        $pdf->SetFont('', '', 8);
        $pdf->SetXY($titleX, $y + 9);
        $pdf->Cell($titleW, 5, $subtitle, 0, 0, 'L');

        $lineY = $y + $badgeSz + 3;

        // Teal separator line
        $pdf->SetDrawColor(...$this->colorTeal);
        $pdf->SetLineWidth(0.6);
        $pdf->Line($x, $lineY, $x + $usableW, $lineY);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(128, 128, 128);

        $pdf->SetY($lineY + 2);
        $pdf->SetTextColor(0, 0, 0);
    }

    private function drawSectionBanner($pdf, string $text): void
    {
        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $y       = $pdf->GetY();
        $h       = 8;

        $this->fillRect($pdf, $this->marge_gauche, $y, $usableW, $h, $this->colorNavy);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('', 'B', 10);
        $pdf->SetXY($this->marge_gauche + 3, $y + 1.5);
        $pdf->Cell($usableW - 6, $h - 3, $text, 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($y + $h);
    }

    private function drawSynthesisSection($pdf, $control, $sheet, $project, $linkedObject, $linkedElement, array $signatures, int $totalQ, int $answeredQ, $outputLangs): void
    {
        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $x       = $this->marge_gauche;
        $y       = $pdf->GetY() + 2;

        $photoW  = 32;
        $noteW   = 30;
        $rowH    = 7;
        $labelW  = 30;
        $infoX   = $x + $photoW + 3;
        $infoW   = $usableW - $photoW - $noteW - 6;

        $notePublicText = !empty($control->note_public) ? $this->cleanText($control->note_public) : '';
        $auditorsText   = $this->buildAuditorsText($signatures, $outputLangs);
        $projectText    = (!empty($project->ref) ? $project->ref . ' — ' : '') . ($project->title ?? '');
        $pdf->SetFont('', '', 8);
        $projectH     = !empty($projectText) ? max($rowH, (int)ceil($pdf->getStringHeight($infoW - $labelW - 2, $projectText)) + 4) : $rowH;
        $auditorsH    = max($rowH, (int)ceil($pdf->getStringHeight($infoW - $labelW - 2, $auditorsText)) + 4);
        $tableH       = $rowH * 3 + $projectH + $auditorsH;

        // Photo block — try $control->photo first, then scan directory (ref and id variants)
        $controlPhoto = null;
        $multdir      = getMultidirOutput($control, $control->module);
        $photoDirs    = [
            $multdir . '/control/' . $control->ref . '/photos',
            $multdir . '/control/' . $control->id  . '/photos',
        ];

        foreach ($photoDirs as $photoDir) {
            if (!empty($control->photo)) {
                $thumb     = saturne_get_thumb_name($control->photo, 'medium', $photoDir);
                $candidate = $photoDir . '/thumbs/' . $thumb;
                if (file_exists($candidate)) {
                    $controlPhoto = $candidate;
                    break;
                }
            }
            // Fallback: pick the first readable image in the directory
            if (is_dir($photoDir)) {
                $files = array_values(array_diff(scandir($photoDir), ['.', '..', 'thumbs']));
                foreach ($files as $photoFile) {
                    if (!in_array(strtolower(pathinfo($photoFile, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        continue;
                    }
                    $fullPath = realpath($photoDir . '/' . $photoFile);
                    if ($fullPath && is_readable($fullPath)) {
                        $thumb     = saturne_get_thumb_name($photoFile, 'medium', $photoDir);
                        $candidate = $photoDir . '/thumbs/' . $thumb;
                        $controlPhoto = file_exists($candidate) ? $candidate : $fullPath;
                        break;
                    }
                }
            }
            if (!empty($controlPhoto)) {
                break;
            }
        }

        if (!empty($controlPhoto)) {
            $this->fillRect($pdf, $x, $y, $photoW, $tableH, [210, 215, 220]);
            $info = @getimagesize($controlPhoto);
            if ($info && $info[0] > 0 && $info[1] > 0) {
                $scale = min($photoW / $info[0], $tableH / $info[1]);
                $dW    = $info[0] * $scale;
                $dH    = $info[1] * $scale;
                $imgX  = $x + ($photoW - $dW) / 2;
                $imgY  = $y + ($tableH - $dH) / 2;
            } else {
                $dW = $photoW; $dH = $tableH; $imgX = $x; $imgY = $y;
            }
            $pdf->Image($controlPhoto, $imgX, $imgY, $dW, $dH);
        } else {
            $this->fillRect($pdf, $x, $y, $photoW, $tableH, [210, 215, 220]);
            if (!empty($this->faFontName)) {
                $iconSize   = max(10, min(16, (int)($photoW * 0.45)));
                $prevFamily = $pdf->getFontFamily();
                $prevStyle  = $pdf->getFontStyle();
                $prevSize   = $pdf->getFontSizePt();
                $pdf->SetFont($this->faFontName, '', $iconSize);
                $pdf->SetTextColor(160, 165, 170);
                $iconH = $iconSize * 0.35;
                $pdf->SetXY($x, $y + ($tableH / 2) - $iconH - 0.5);
                $pdf->Cell($photoW, $iconH * 2 + 1, "\xef\x80\xb0", 0, 0, 'C');
                if (!empty($prevFamily)) {
                    $pdf->SetFont($prevFamily, $prevStyle, $prevSize);
                }
                $pdf->SetTextColor(0, 0, 0);
            }
        }
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect($x, $y, $photoW, $tableH);

        // NOTE box
        $noteX = $x + $usableW - $noteW;
        $pdf->SetDrawColor(...$this->colorTeal);
        $pdf->Rect($noteX, $y, $noteW, $tableH);

        // Status badge
        $noteStatusLabel = '';
        $noteStatusColor = $this->colorGray;
        if ($control->status >= 2) {
            $noteStatusLabel = $outputLangs->convToOutputCharset($outputLangs->transnoentities('Locked'));
            $noteStatusColor = [26, 45, 64];
        } elseif ($control->status == 1) {
            $noteStatusLabel = $outputLangs->convToOutputCharset($outputLangs->transnoentities('Validated'));
            $noteStatusColor = [40, 167, 69];
        } elseif ($control->status == 0) {
            $noteStatusLabel = $outputLangs->convToOutputCharset($outputLangs->transnoentities('Draft'));
        }
        if (!empty($noteStatusLabel)) {
            $this->fillRect($pdf, $noteX + 2, $y + 2, $noteW - 4, 5, $noteStatusColor);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($noteX + 2, $y + 2);
            $pdf->Cell($noteW - 4, 5, $noteStatusLabel, 0, 0, 'C');
        }

        // "Résultat du contrôle" label (2 lines)
        $pdf->SetTextColor(...$this->colorNavy);
        $pdf->SetFont('', 'B', 7);
        $pdf->SetXY($noteX, $y + 9);
        $pdf->Cell($noteW, 4, $outputLangs->convToOutputCharset('Résultat du'), 0, 0, 'C');
        $pdf->SetXY($noteX, $y + 13);
        $pdf->Cell($noteW, 4, $outputLangs->convToOutputCharset('contrôle'), 0, 0, 'C');

        // Score
        $score = !empty($control->success_rate) ? round((float)$control->success_rate) . ' %' : '—';
        $pdf->SetTextColor(...$this->colorTeal);
        $pdf->SetFont('', 'B', 14);
        $pdf->SetXY($noteX, $y + 18);
        $pdf->Cell($noteW, 9, $score, 0, 0, 'C');

        $cellData = [
            [
                'label' => $outputLangs->convToOutputCharset('Date du contrôle'),
                'value' => dol_print_date($control->control_date, 'dayhour'),
                'teal'  => true,
            ],
            [
                'label' => $outputLangs->convToOutputCharset('Objet lié'),
                'value' => isset($linkedObject, $linkedElement) ? ($outputLangs->transnoentities(ucfirst($linkedElement)) . ' — ' . ($linkedObject->$linkedElement ?? $linkedObject->ref ?? '')) : '',
            ],
            [
                'label'     => 'Projet',
                'value'     => $projectText,
                'height'    => $projectH,
                'multiline' => true,
            ],
            [
                'label' => 'Avancement',
                'value' => $answeredQ . ' / ' . $totalQ . ' questions',
            ],
        ];
        $cellData[] = [
            'label'     => 'Auditeurs',
            'value'     => $auditorsText,
            'height'    => $auditorsH,
            'multiline' => true,
        ];

        $cellY = $y;
        foreach ($cellData as $row) {
            $split = !empty($row['split']);
            $curH  = $row['height'] ?? $rowH;

            if ($split) {
                $halfInfoW = $infoW / 2;
                // Left half label
                $this->fillRect($pdf, $infoX, $cellY, $labelW, $curH, $this->colorLight);
                $pdf->SetTextColor(...$this->colorGray);
                $pdf->SetFont('', '', 7);
                $pdf->SetXY($infoX + 1, $cellY + 2);
                $pdf->Cell($labelW - 2, 3.5, $row['label'], 0, 0, 'L');

                // Left half value
                $pdf->SetTextColor(!empty($row['teal']) ? $this->colorTeal[0] : $this->colorBlack[0], !empty($row['teal']) ? $this->colorTeal[1] : $this->colorBlack[1], !empty($row['teal']) ? $this->colorTeal[2] : $this->colorBlack[2]);
                $pdf->SetFont('', !empty($row['teal']) ? 'B' : '', 8);
                $pdf->SetXY($infoX + $labelW + 1, $cellY + 2);
                $pdf->Cell($halfInfoW - $labelW - 2, 3.5, $row['value'], 0, 0, 'L');

                // Right half label
                $this->fillRect($pdf, $infoX + $halfInfoW, $cellY, $labelW, $curH, $this->colorLight);
                $pdf->SetTextColor(...$this->colorGray);
                $pdf->SetFont('', '', 7);
                $pdf->SetXY($infoX + $halfInfoW + 1, $cellY + 2);
                $pdf->Cell($labelW - 2, 3.5, $row['label2'] ?? '', 0, 0, 'L');

                // Right half value
                $pdf->SetTextColor(...$this->colorBlack);
                $pdf->SetFont('', '', 8);
                $pdf->SetXY($infoX + $halfInfoW + $labelW + 1, $cellY + 2);
                $pdf->Cell($halfInfoW - $labelW - 2, 3.5, $row['value2'] ?? '', 0, 0, 'L');
            } else {
                $this->fillRect($pdf, $infoX, $cellY, $labelW, $curH, $this->colorLight);
                $pdf->SetTextColor(...$this->colorGray);
                $pdf->SetFont('', '', 7);
                $pdf->SetXY($infoX + 1, $cellY + 2);
                $pdf->Cell($labelW - 2, 3.5, $row['label'], 0, 0, 'L');

                $pdf->SetTextColor(!empty($row['teal']) ? $this->colorTeal[0] : $this->colorBlack[0], !empty($row['teal']) ? $this->colorTeal[1] : $this->colorBlack[1], !empty($row['teal']) ? $this->colorTeal[2] : $this->colorBlack[2]);
                $pdf->SetFont('', !empty($row['teal']) ? 'B' : '', 8);
                $pdf->SetXY($infoX + $labelW + 1, $cellY + 2);
                if (!empty($row['multiline'])) {
                    $pdf->MultiCell($infoW - $labelW - 2, 3.5, $row['value'], 0, 'L');
                } else {
                    $pdf->Cell($infoW - $labelW - 2, 3.5, $row['value'], 0, 0, 'L');
                }
            }

            $pdf->SetDrawColor(210, 215, 220);
            $pdf->Rect($infoX, $cellY, $infoW, $curH);
            $pdf->SetDrawColor(128, 128, 128);
            $cellY += $curH;
        }

        // Note publique box (full-width, below main table)
        if (!empty($notePublicText)) {
            $boxY    = $y + $tableH + 2;
            $headerH = 5;
            $pdf->SetFont('', '', 8);
            $contentH = max(6, (int)ceil($pdf->getStringHeight($usableW - 4, $notePublicText)) + 2);
            $boxH    = $headerH + $contentH;

            $this->fillRect($pdf, $x, $boxY, $usableW, $headerH, $this->colorLight);
            $pdf->SetTextColor(...$this->colorGray);
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($x + 2, $boxY + 1);
            $pdf->Cell($usableW - 4, 3.5, $outputLangs->convToOutputCharset('Note publique'), 0, 0, 'L');

            $pdf->SetTextColor(...$this->colorBlack);
            $pdf->SetFont('', '', 8);
            $pdf->SetXY($x + 2, $boxY + $headerH + 1);
            $pdf->MultiCell($usableW - 4, 3.5, $notePublicText, 0, 'L');

            $pdf->SetDrawColor(210, 215, 220);
            $pdf->Rect($x, $boxY, $usableW, $boxH);
            $pdf->SetY($boxY + $boxH + 5);
        } else {
            $pdf->SetY($y + $tableH + 5);
        }
        $pdf->SetTextColor(0, 0, 0);
    }

    private function buildAuditorsText(array $signatures, $outputLangs): string
    {
        if (empty($signatures)) {
            return '';
        }
        $parts = [];
        foreach ($signatures as $sig) {
            $name = trim(($sig->firstname ?? '') . ' ' . ($sig->lastname ?? ''));
            if (!empty($sig->role)) {
                $name .= ' (' . $outputLangs->transnoentities($sig->role) . ')';
            }
            if (!empty($name)) {
                $parts[] = $name;
            }
        }
        if (empty($parts)) {
            return '';
        }
        return implode(' · ', $parts) . $outputLangs->convToOutputCharset(' — signés électroniquement');
    }

    private function drawGroupBanner($pdf, string $groupLabel, int $qCount, array $stats): void
    {
        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $x       = $this->marge_gauche;
        $y       = $pdf->GetY();
        $h       = 7;

        $this->fillRect($pdf, $x, $y, $usableW, $h, [232, 238, 245]);

        $pdf->SetTextColor(...$this->colorNavy);
        $pdf->SetFont('', 'B', 8.5);
        $pdf->SetXY($x + 3, $y + 1.5);
        $pdf->Cell(80, 4, $groupLabel, 0, 0, 'L');

        $pdf->SetTextColor(...$this->colorGray);
        $pdf->SetFont('', '', 7.5);
        $pdf->SetXY($x + 84, $y + 1.5);
        $pdf->Cell(30, 4, $qCount . ' questions', 0, 0, 'L');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetY($y + $h);
    }

    private function drawQuestionCard($pdf, $question, $controlLine, ?string $pictogram, ?string $answerColor, array $photos, $outputLangs, array $questionAnswers = []): void
    {
        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $x       = $this->marge_gauche;

        $isChoice     = $this->isChoiceType($question->type);
        $isMultiple   = ($question->type === 'MultipleChoices');
        $isText       = ($question->type === 'Text');
        $isPercentage = ($question->type === 'Percentage');
        $isRange      = ($question->type === 'Range');

        // ── Answer data ───────────────────────────────────────────────────────

        $answerText = '';
        if ($isText && !empty($controlLine) && $controlLine->answer !== null && $controlLine->answer !== '') {
            $answerText = $this->cleanText($controlLine->answer);
        }

        // MultipleChoices: build ordered list of selected answer display configs
        $selectedAnswers = [];
        if ($isMultiple && !empty($controlLine) && !empty($controlLine->answer) && $controlLine->answer !== '0') {
            $positions = array_map('trim', explode(',', (string)$controlLine->answer));
            foreach ($questionAnswers as $ans) {
                if (in_array((string)$ans->position, $positions, true)) {
                    $cfg              = $this->getAnswerConfig($ans->pictogram);
                    $rgb              = !empty($ans->color) ? $this->hexToRgb($ans->color) : $cfg['rgb'];
                    $selectedAnswers[] = ['abbrev' => $cfg['abbrev'], 'rgb' => $rgb, 'fa' => $cfg['fa'] ?? null];
                }
            }
        }
        $multiCount = count($selectedAnswers);

        // Percentage / Range: pre-format the value string
        $numericVal = '';
        if (($isPercentage || $isRange) && !empty($controlLine) && $controlLine->answer !== null && $controlLine->answer !== '') {
            $numericVal = $isPercentage
                ? round((float)$controlLine->answer) . ' %'
                : (string)$controlLine->answer;
        }

        // ── Right column dimensions ───────────────────────────────────────────
        $circleD    = 9;
        $numericColW = 26;  // Percentage / Range value display
        $textColW    = 44;  // Text answer column

        if ($isChoice && !$isMultiple) {
            $rightW = $circleD + 12;                      // 23 mm — single circle + margins
        } elseif ($isMultiple) {
            $gap       = 2;
            $drawCount = max(1, $multiCount);
            $rightW    = $drawCount * $circleD + ($drawCount - 1) * $gap + 6;
        } elseif ($isPercentage || $isRange) {
            $rightW = $numericColW;                       // 26 mm — large numeric value
        } elseif ($isText && $answerText !== '') {
            $rightW = $textColW;                          // 44 mm — text answer column
        } else {
            $rightW = 0;
        }

        // Leave a 2 mm gap between text area and right column to avoid overlap
        $textAreaW  = $rightW > 0 ? ($usableW - $rightW - 8) : ($usableW - 6);
        $rightAreaX = $x + $usableW - $rightW;

        $obsText = !empty($controlLine) ? $this->cleanText($controlLine->comment ?? '') : '';
        $desc    = !empty($question->description) ? $this->cleanText($question->description) : '';

        // ── Height calculations ───────────────────────────────────────────────
        $pdf->SetFont('', '', 8);
        $titleH = $pdf->getNumLines($question->label, $textAreaW) * 4;
        $pdf->SetFont('', '', 7);
        $descH  = !empty($desc) ? $pdf->getNumLines($desc, $textAreaW) * 3.5 : 0;
        $obsLabelW = 23;
        $obsH      = !empty($obsText) ? $pdf->getNumLines($obsText, $textAreaW - $obsLabelW) * 3.5 : 0;

        $rightTextH = 0;
        if ($isText && $answerText !== '') {
            $rightTextH = $pdf->getNumLines($answerText, $textColW - 6) * 3.5;
        }

        $innerPad = 1.5;
        $leftH    = $innerPad + 5 + $titleH
            + ($descH > 0 ? $descH + 1 : 0)
            + ($obsH > 0 ? $obsH + 2 : 0)
            + $innerPad;

        $minH = $isChoice ? ($circleD + $innerPad * 2) : 15;
        if ($isText && $answerText !== '') {
            $minH = max($minH, $rightTextH + $innerPad * 2 + 5);
        }

        $cardH = max($leftH, $minH);

        $hasPhotos   = !empty($photos);
        $photoRowH   = $hasPhotos ? 20 : 0;
        $totalNeeded = $cardH + $photoRowH + 3;

        $this->checkPageBreak($pdf, $totalNeeded);
        $y = $pdf->GetY();

        // ── Card background ───────────────────────────────────────────────────
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(220, 224, 228);
        $pdf->Rect($x, $y, $usableW, $cardH, 'FD');

        // Left teal accent
        $this->fillRect($pdf, $x, $y, 2.5, $cardH, $this->colorTeal);


        // ── Right-side content ────────────────────────────────────────────────
        if ($isChoice && !$isMultiple) {
            // Single-choice: circle with FA icon
            $circleR  = $circleD / 2;
            $circleCX = $x + $usableW - $circleD - 3 + $circleR;
            $circleCY = $y + $cardH / 2;
            $cfg      = $this->getAnswerConfig($pictogram);
            $faChar   = $cfg['fa'] ?? null;
            $rgb      = !empty($answerColor) ? $this->hexToRgb($answerColor) : $cfg['rgb'];
            $this->drawAnswerCircle($pdf, $circleCX, $circleCY, $circleR, $cfg['abbrev'], $rgb, $faChar);

        } elseif ($isMultiple) {
            // Multiple-choice: one circle per selected answer, displayed horizontally
            $circleR  = $circleD / 2;
            $draws    = !empty($selectedAnswers) ? $selectedAnswers : [['abbrev' => '?', 'rgb' => $this->colorGray, 'fa' => null]];
            $n        = count($draws);
            $gap      = 2;
            $totalW   = $n * $circleD + ($n - 1) * $gap;
            $startCX  = $rightAreaX + ($rightW - $totalW) / 2 + $circleR;
            $circleCY = $y + $cardH / 2;
            foreach ($draws as $i => $sel) {
                $circleCX = $startCX + $i * ($circleD + $gap);
                $this->drawAnswerCircle($pdf, $circleCX, $circleCY, $circleR, $sel['abbrev'], $sel['rgb'], $sel['fa'] ?? null);
            }

        } elseif ($isPercentage || $isRange) {
            // Numeric value: large bold text centered in right column (no circle)
            $display = $numericVal !== '' ? $numericVal : '-';
            $rgb     = $isPercentage ? $this->colorTeal : $this->colorGray;
            $len     = mb_strlen($display);
            $fs      = $len >= 6 ? 10 : ($len >= 4 ? 12 : 14);
            $textH   = $fs * 0.35;
            $pdf->SetTextColor(...$rgb);
            $pdf->SetFont('', 'B', $fs);
            $pdf->SetXY($rightAreaX, $y + $cardH / 2 - $textH - 0.5);
            $pdf->Cell($rightW, $textH * 2 + 1, $display, 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);

        } elseif ($isText && $answerText !== '') {
            // Text answer: right column with label + wrapped answer
            $pdf->SetTextColor(...$this->colorTeal);
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($rightAreaX + 3, $y + $innerPad);
            $pdf->Cell($textColW - 6, 3.5, $outputLangs->convToOutputCharset('Réponse'), 0, 0, 'L');
            $pdf->SetTextColor(...$this->colorBlack);
            $pdf->SetFont('', '', 7);
            $ansRowY = $y + $innerPad + 4;
            $pdf->SetXY($rightAreaX + 3, $ansRowY);
            $pdf->MultiCell($textColW - 6, 4, $answerText, 0, 'L', 0, 0, $rightAreaX + 3, $ansRowY, true, 0, false, true, $cardH - $innerPad - 4.5, 'T', false);
        }

        // ── Left content (ref · label · desc · obs) ───────────────────────────
        $pdf->SetTextColor(...$this->colorNavy);
        $pdf->SetFont('', 'B', 8);
        $pdf->SetXY($x + 6, $y + $innerPad);
        $pdf->Cell(40, 4, $question->ref, 0, 0, 'L');

        $rowY = $y + $innerPad + 4.5;
        $pdf->SetTextColor(...$this->colorBlack);
        $pdf->SetFont('', '', 8);
        $pdf->SetXY($x + 6, $rowY);
        $pdf->MultiCell($textAreaW, 4, $question->label, 0, 'L', 0, 0, $x + 6, $rowY, true, 0, false, true, $cardH, 'T', false);
        $rowY += $titleH;

        if (!empty($desc)) {
            $pdf->SetTextColor(...$this->colorGray);
            $pdf->SetFont('', '', 7);
            $pdf->SetXY($x + 6, $rowY);
            $pdf->MultiCell($textAreaW, 3.5, $desc, 0, 'L', 0, 0, $x + 6, $rowY, true, 0, false, true, $cardH, 'T', false);
            $rowY += $descH + 1;
        }

        if (!empty($obsText)) {
            $rowY += 1;
            $pdf->SetTextColor(...$this->colorTeal);
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($x + 6, $rowY);
            $pdf->Cell($obsLabelW, 3.5, 'Commentaire :', 0, 0, 'L');
            $pdf->SetTextColor(...$this->colorGray);
            $pdf->SetFont('', '', 7);
            $pdf->SetXY($x + 6 + $obsLabelW, $rowY);
            $pdf->MultiCell($textAreaW - $obsLabelW, 3.5, $obsText, 0, 'L', 0, 0, $x + 6 + $obsLabelW, $rowY, true, 0, false, true, $cardH, 'T', false);
        }

        $pdf->SetY($y + $cardH);

        // Photos row
        if ($hasPhotos) {
            $this->checkPageBreak($pdf, $photoRowH + 2);
            $photoY = $pdf->GetY();
            $pdf->SetDrawColor(220, 224, 228);
            $pdf->Rect($x, $photoY, $usableW, $photoRowH);

            $maxPer = 4;
            $imgW   = ($usableW - 10) / $maxPer;
            $imgH   = $photoRowH - 4;
            $posX   = $x + 3;
            $posY   = $photoY + 2;
            $i      = 0;

            foreach ($photos as $img) {
                clearstatcache();
                if ($i >= $maxPer) {
                    break;
                }
                if (file_exists($img)) {
                    $info = @getimagesize($img);
                    if ($info && $info[0] > 0 && $info[1] > 0) {
                        $scale = min($imgW / $info[0], $imgH / $info[1]);
                        $dW    = $info[0] * $scale;
                        $dH    = $info[1] * $scale;
                    } else {
                        $dW = $imgW;
                        $dH = $imgH;
                    }
                    $pdf->Image($img, $posX, $posY, $dW, $dH, '', '', '', false, 300);
                    $posX += $imgW + 2;
                    $i++;
                }
            }
            $pdf->SetY($photoY + $photoRowH);
        }

        $pdf->SetY($pdf->GetY() + 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(128, 128, 128);
    }

    private function drawQuestionTasks($pdf, array $tasks, $outputLangs): void
    {
        if (empty($tasks)) {
            return;
        }

        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $x       = $this->marge_gauche + 4;
        $w       = $usableW - 4;
        $wRef    = 20;
        $wDate   = 25;
        $wProg   = 18;
        $wLabel  = $w - $wRef - $wDate - $wProg - 6;

        foreach ($tasks as $task) {
            $labelText = $task->label ?? '';

            // Compute the row height so long task names wrap instead of overflowing the row
            $pdf->SetFont('', '', 7);
            $rowH = max(6, $pdf->getStringHeight($wLabel, $labelText));

            $this->checkPageBreak($pdf, $rowH + 1);
            $sy = $pdf->GetY();

            $this->fillRect($pdf, $x, $sy, $w, $rowH, [255, 248, 225]);
            $this->fillRect($pdf, $x, $sy, 2, $rowH, [255, 152, 0]);

            $pdf->SetTextColor(180, 100, 0);
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($x + 3, $sy + ($rowH - 4) / 2);
            $pdf->Cell($wRef, 4, $task->ref, 0, 0, 'L');

            $pdf->SetTextColor(...$this->colorBlack);
            $pdf->SetFont('', '', 7);
            $pdf->MultiCell($wLabel, 4, $labelText, 0, 'L', 0, 0, $x + 3 + $wRef, $sy + 1, true, 0, false, true, $rowH - 2, 'M', false);

            $dateStr = '';
            if (!empty($task->date_end)) {
                $dateStr = dol_print_date($task->date_end, 'day');
            } elseif (!empty($task->date_start)) {
                $dateStr = dol_print_date($task->date_start, 'day');
            }
            $pdf->SetTextColor(...$this->colorGray);
            $pdf->SetFont('', '', 7);
            $pdf->SetXY($x + 3 + $wRef + $wLabel, $sy + ($rowH - 4) / 2);
            $pdf->Cell($wDate, 4, $dateStr, 0, 0, 'C');

            $prog    = (int)($task->progress ?? 0);
            $progRgb = $prog >= 100 ? [56, 161, 105] : ($prog > 0 ? [255, 152, 0] : [220, 53, 69]);
            $pdf->SetTextColor(...$progRgb);
            $pdf->SetFont('', 'B', 7);
            $pdf->SetXY($x + 3 + $wRef + $wLabel + $wDate, $sy + ($rowH - 4) / 2);
            $pdf->Cell($wProg, 4, $prog . '%', 0, 0, 'C');

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(240, 220, 180);
            $pdf->Rect($x, $sy, $w, $rowH);
            $pdf->SetDrawColor(128, 128, 128);
            $pdf->SetY($sy + $rowH);
        }
        $pdf->SetY($pdf->GetY() + 1);
    }

    private function drawActionPlanSection($pdf, array $questionsAndGroups, array $questionTasksMap, $outputLangs): void
    {
        $hasTasks = false;
        foreach ($questionTasksMap as $tasks) {
            if (!empty($tasks)) {
                $hasTasks = true;
                break;
            }
        }
        if (!$hasTasks) {
            return;
        }

        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;
        $x       = $this->marge_gauche;

        // Force new page for action plan
        $this->drawPageFooter($pdf);
        $pdf->AddPage();
        $this->drawTopRight($pdf);
        $pdf->SetY($this->marge_haute);

        $this->drawSectionBanner($pdf, $outputLangs->convToOutputCharset($outputLangs->transnoentities('ActionPlan')));
        $pdf->Ln(3);

        // Column widths
        $wTask  = 22;
        $wLabel = 65;
        $wQRef  = 25;
        $wDate  = 32;
        $wProg  = 15;
        $wNote  = $usableW - $wTask - $wLabel - $wQRef - $wDate - $wProg;

        // Table header
        $this->checkPageBreak($pdf, 8);
        $this->fillRect($pdf, $x, $pdf->GetY(), $usableW, 8, $this->colorTeal);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont('', 'B', 7);
        $cols = [
            ['w' => $wTask,  'text' => $outputLangs->convToOutputCharset('Tâche')],
            ['w' => $wLabel, 'text' => 'Action corrective'],
            ['w' => $wQRef,  'text' => 'Question'],
            ['w' => $wDate,  'text' => $outputLangs->convToOutputCharset('Échéance')],
            ['w' => $wNote,  'text' => 'Commentaire'],
            ['w' => $wProg,  'text' => '%'],
        ];
        $hX = $x;
        foreach ($cols as $col) {
            $pdf->SetXY($hX, $pdf->GetY());
            $pdf->Cell($col['w'], 8, $col['text'], 1, 0, 'C');
            $hX += $col['w'];
        }
        $pdf->Ln(8);

        $rowIdx = 0;
        $pdf->SetDrawColor(210, 220, 230);
        foreach ($questionsAndGroups as $question) {
            if (!($question instanceof Question)) {
                continue;
            }
            $tasks = $questionTasksMap[$question->id] ?? [];
            if (empty($tasks)) {
                continue;
            }

            foreach ($tasks as $task) {
                $dateStr = '';
                if (!empty($task->date_end)) {
                    $dateStr = dol_print_date($task->date_end, 'day');
                } elseif (!empty($task->date_start)) {
                    $dateStr = dol_print_date($task->date_start, 'day');
                } elseif (!empty($task->date_c)) {
                    $dateStr = dol_print_date($task->date_c, 'day');
                }

                $noteText  = $this->cleanText($task->description ?? '');
                $labelText = $task->label ?? '';
                $qRef      = $question->ref;

                $pdf->SetFont('', '', 7.5);
                $rowH = max(8,
                    $pdf->getStringHeight($wLabel, $labelText),
                    $pdf->getStringHeight($wNote, $noteText)
                );

                $this->checkPageBreak($pdf, $rowH);
                $sy = $pdf->GetY();

                if ($rowIdx % 2 !== 0) {
                    $this->fillRect($pdf, $x, $sy, $usableW, $rowH, [245, 247, 250]);
                }
                $rowIdx++;

                $pdf->Rect($x, $sy, $usableW, $rowH);

                // Task ref
                $pdf->SetTextColor(...$this->colorTeal);
                $pdf->SetFont('', 'B', 7);
                $pdf->SetXY($x + 1, $sy + ($rowH - 4) / 2);
                $pdf->Cell($wTask - 2, 4, $task->ref, 0, 0, 'C');

                // Label
                $pdf->SetTextColor(...$this->colorBlack);
                $pdf->SetFont('', '', 7.5);
                $pdf->MultiCell($wLabel, 4, $labelText, 0, 'L', 0, 0, $x + $wTask, $sy + 2, true, 0, false, true, $rowH - 4, 'M', false);

                // Question ref
                $pdf->SetTextColor(...$this->colorGray);
                $pdf->SetFont('', '', 7);
                $pdf->SetXY($x + $wTask + $wLabel + 1, $sy + ($rowH - 4) / 2);
                $pdf->Cell($wQRef - 2, 4, $qRef, 0, 0, 'C');

                // Date
                $pdf->SetTextColor(...$this->colorBlack);
                $pdf->SetXY($x + $wTask + $wLabel + $wQRef + 1, $sy + ($rowH - 4) / 2);
                $pdf->Cell($wDate - 2, 4, $dateStr, 0, 0, 'C');

                // Note/description
                $pdf->SetTextColor(...$this->colorGray);
                $pdf->SetFont('', '', 7);
                $pdf->MultiCell($wNote, 4, $noteText, 0, 'L', 0, 0, $x + $wTask + $wLabel + $wQRef + $wDate, $sy + 2, true, 0, false, true, $rowH - 4, 'M', false);

                // Progress
                $prog    = (int)($task->progress ?? 0);
                $progRgb = $prog >= 100 ? [56, 161, 105] : ($prog > 0 ? [255, 152, 0] : [220, 53, 69]);
                $pdf->SetTextColor(...$progRgb);
                $pdf->SetFont('', 'B', 7.5);
                $pdf->SetXY($x + $usableW - $wProg + 1, $sy + ($rowH - 4) / 2);
                $pdf->Cell($wProg - 2, 4, $prog . '%', 0, 0, 'C');

                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln($rowH);
            }
        }
        $pdf->SetDrawColor(0, 0, 0);
    }

    private function drawSignatureSection($pdf, array $signatures, string $title, array $roles, $outputLangs): void
    {
        $pageW   = $pdf->getPageWidth();
        $usableW = $pageW - $this->marge_gauche - $this->marge_droite;

        $this->drawSectionTitle($pdf, $outputLangs->convToOutputCharset($outputLangs->transnoentities($title)));

        $wName = 55;
        $wPre  = 55;
        $wDate = 35;
        $wSign = 45;

        // Header
        $headerData = [
            ['w' => $wName, 'text' => $outputLangs->convToOutputCharset($outputLangs->transnoentities('LastName'))],
            ['w' => $wPre,  'text' => $outputLangs->convToOutputCharset($outputLangs->transnoentities('FirstName'))],
            ['w' => $wDate, 'text' => $outputLangs->convToOutputCharset($outputLangs->transnoentities('SignatureDate'))],
            ['w' => $wSign, 'text' => $outputLangs->convToOutputCharset($outputLangs->transnoentities('Signature'))],
        ];

        $this->checkPageBreak($pdf, 10);
        $this->fillRect($pdf, $this->marge_gauche, $pdf->GetY(), $usableW, 8, $this->colorTeal);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont('', 'B', 8);
        $hX = $this->marge_gauche;
        foreach ($headerData as $hCol) {
            $pdf->SetXY($hX, $pdf->GetY());
            $pdf->Cell($hCol['w'], 8, $hCol['text'], 1, 0, 'C', false);
            $hX += $hCol['w'];
        }
        $pdf->Ln(8);

        $found  = false;
        $rowIdx = 0;
        $pdf->SetFont('', '', 8);
        $pdf->SetTextColor(...$this->colorBlack);
        $pdf->SetDrawColor(210, 220, 230);

        foreach ($signatures as $sig) {
            if (!in_array($sig->role, $roles)) {
                continue;
            }
            $found  = true;
            $height = max(20, $pdf->getStringHeight($wName, $sig->lastname));
            $this->checkPageBreak($pdf, $height);

            $sx = $this->marge_gauche;
            $sy = $pdf->GetY();

            if ($rowIdx % 2 !== 0) {
                $this->fillRect($pdf, $sx, $sy, $usableW, $height, [245, 247, 250]);
            }
            $rowIdx++;

            $pdf->MultiCell($wName, $height, $sig->lastname,  1, 'C', 0, 0, $sx,              $sy, true, 0, false, true, $height, 'M', false);
            $pdf->MultiCell($wPre,  $height, $sig->firstname, 1, 'C', 0, 0, $sx + $wName,     $sy, true, 0, false, true, $height, 'M', false);
            $pdf->MultiCell($wDate, $height, dol_print_date($sig->signature_date, 'day'), 1, 'C', 0, 0, $sx + $wName + $wPre, $sy, true, 0, false, true, $height, 'M', false);

            if (!empty($sig->signature)) {
                $encoded = explode(',', $sig->signature);
                $img     = base64_decode($encoded[1] ?? $encoded[0]);
                $pdf->Image('@' . $img, $sx + $wName + $wPre + $wDate, $sy, $wSign, $height, 'PNG', '', 'C', false, 300, '', false, false, 1);
                $pdf->SetXY($sx + $wName + $wPre + $wDate, $sy);
                $pdf->Cell($wSign, $height, '', 1);
            } else {
                $pdf->MultiCell($wSign, $height, 'N/A', 1, 'C', 0, 0, $sx + $wName + $wPre + $wDate, $sy, true, 0, false, true, $height, 'M', false);
            }
            $pdf->Ln($height);
        }

        if (!$found) {
            $pdf->SetTextColor(...$this->colorGray);
            $pdf->Cell($wName + $wPre + $wDate + $wSign, 8, $outputLangs->convToOutputCharset($outputLangs->transnoentities('NoData')), 1, 1, 'C');
        }
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Write the PDF file to disk
     *
     * @param Object $objectDocument Object to generate (ex: control)
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
        global $action, $langs, $hookmanager, $user;

        // Dolibarr core calls write_file without passing moreparams (stored in context instead)
        if (empty($moreparams)) {
            $moreparams = $objectDocument->context['moreparams'] ?? [];
        }

        $control = $moreparams['object'] ?? null;
        if (empty($control)) {
            $this->error = 'MissingControlObject';
            return -1;
        }

        $moreparams['hideTemplateName'] = 1;
        if (empty($moreparams['user'])) {
            $moreparams['user'] = $user;
        }
        $file = $this->buildDocumentFilename($objectDocument, $outputLangs, $control, $moreparams);
        if (is_int($file) && $file < 0) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $this->generatedFilename = basename($file);
        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $control, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $control, $action);

        // ── Load related objects ─────────────────────────────────────────────

        $sheet            = new Sheet($this->db);
        $controlLine      = new ControlLine($this->db);
        $answerObj        = new Answer($this->db);
        $project          = new Project($this->db);
        $saturneSignature = new SaturneSignature($this->db);
        $controlEquipment = new ControlEquipment($this->db);
        $productLot       = new Productlot($this->db);

        $sheet->fetch($control->fk_sheet);
        $project->fetch($control->projectid);
        $signatures        = $saturneSignature->fetchSignatories($control->id, $control->element);
        $controlEquipments = $controlEquipment->fetchFromParent($control->id);

        // Linked element for synthesis header
        $objectsMetadata = saturne_get_objects_metadata();
        $control->fetchObjectLinked('', '', $control->id, 'digiquali_control');
        $linkedObjectType = key($control->linkedObjects);
        $linkedElementRaw = json_decode($sheet->element_linked, true);
        $linkedElement    = !empty($linkedElementRaw) ? array_keys($linkedElementRaw)[0] : null;
        if ($linkedElement === 'productlot') {
            $linkedElement = 'batch';
        }
        $linkedObject = null;
        foreach ($objectsMetadata as $objectMetadata) {
            if (empty($objectMetadata['conf']) || $objectMetadata['link_name'] != $linkedObjectType) {
                continue;
            }
            if (!empty($control->linkedObjects[$objectMetadata['link_name']])) {
                $linkedObject = $control->linkedObjects[$objectMetadata['link_name']][key($control->linkedObjects[$objectMetadata['link_name']])];
            }
        }

        // ── Questions & groups ───────────────────────────────────────────────

        $questionsAndGroups = $sheet->fetchQuestionsAndGroups(null, 'digiquali_sheet', true);

        // Build ordered structure: [groupId => ['group' => QuestionGroup|null, 'questions' => []]]
        // Fetch only top-level items (no recursive expansion) so we can correctly separate
        // questions directly on the sheet from questions belonging to a group.
        $structuredGroups = [];
        $topLevelItems    = $sheet->fetchQuestionsAndGroups(null, 'digiquali_sheet', false);

        foreach ($topLevelItems as $item) {
            if ($item instanceof QuestionGroup) {
                $groupQuestions = [];
                $subItems       = $sheet->fetchQuestionsAndGroups($item->id, 'digiquali_questiongroup', true);
                foreach ($subItems as $sub) {
                    if ($sub instanceof Question) {
                        $groupQuestions[] = $sub;
                    }
                }
                $structuredGroups[$item->id] = ['group' => $item, 'questions' => $groupQuestions];
            } elseif ($item instanceof Question) {
                if (!isset($structuredGroups['ungrouped'])) {
                    $structuredGroups['ungrouped'] = ['group' => null, 'questions' => []];
                }
                $structuredGroups['ungrouped']['questions'][] = $item;
            }
        }

        // Fetch control lines and answers per question
        $controlLineByQuestion = [];
        $answersByQuestion     = [];
        $totalQuestions        = 0;
        $answeredQuestions     = 0;

        foreach ($questionsAndGroups as $item) {
            if (!($item instanceof Question)) {
                continue;
            }
            $totalQuestions++;
            $lines = $controlLine->fetchFromParentWithQuestion((int)$control->id, (int)$item->id);
            $cl    = is_array($lines) && !empty($lines) ? reset($lines) : null;
            $controlLineByQuestion[$item->id] = $cl;
            if (!empty($cl) && $cl->answer !== null && $cl->answer !== '') {
                $answeredQuestions++;
            }
            $answers = $answerObj->fetchAll('', 'position', 0, 0, ['customsql' => 't.fk_question = ' . (int)$item->id]);
            $answersByQuestion[$item->id] = is_array($answers) ? $answers : [];
        }

        // Fetch tasks linked to each control line (action plan)
        // Use direct SQL on llx_element_element to avoid fetchObjectLinked SQL errors (actioncomm join issue)
        $questionTasksMap = [];
        foreach ($questionsAndGroups as $item) {
            if (!($item instanceof Question)) {
                continue;
            }
            $cl = $controlLineByQuestion[$item->id] ?? null;
            if (empty($cl)) {
                $questionTasksMap[$item->id] = [];
                continue;
            }

            $clId  = (int)$cl->id;
            $sql   = 'SELECT fk_source AS task_id FROM ' . MAIN_DB_PREFIX . 'element_element';
            $sql  .= " WHERE sourcetype = 'project_task' AND fk_target = " . $clId . " AND targettype = 'controldet'";
            $sql  .= ' UNION ';
            $sql  .= 'SELECT fk_target AS task_id FROM ' . MAIN_DB_PREFIX . 'element_element';
            $sql  .= " WHERE targettype = 'project_task' AND fk_source = " . $clId . " AND sourcetype = 'controldet'";
            $resql = $this->db->query($sql);

            $tasks = [];
            if ($resql) {
                while ($row = $this->db->fetch_object($resql)) {
                    $task = new Task($this->db);
                    if ($task->fetch((int)$row->task_id) > 0) {
                        $tasks[] = $task;
                    }
                }
            }
            $questionTasksMap[$item->id] = $tasks;
        }

        // Fetch photos per question
        $multdirOutput     = getMultidirOutput($control, $control->module);
        $questionPhotosMap = [];

        foreach ($questionsAndGroups as $item) {
            if (!($item instanceof Question)) {
                continue;
            }
            $photoPath = $multdirOutput . '/control/' . $control->ref . '/answer_photo/' . $item->ref;
            $photos    = [];
            if (is_dir($photoPath)) {
                $files = array_values(array_diff(scandir($photoPath), ['.', '..', 'thumbs']));
                foreach ($files as $photoFile) {
                    $fullPath = realpath($photoPath . '/' . $photoFile);
                    if ($fullPath && file_exists($fullPath) && is_readable($fullPath)) {
                        $thumb    = saturne_get_thumb_name($photoFile, 'small', $photoPath);
                        $photos[] = $photoPath . '/thumbs/' . $thumb;
                    }
                }
            }
            $questionPhotosMap[$item->id] = $photos;
        }

        // ── Create PDF instance ──────────────────────────────────────────────

        pdf_getInstance($this->format); // charge les constantes TCPDF et tcpdf.php

        // Load FontAwesome Solid for picto rendering in circles
        $faFontPath = DOL_DOCUMENT_ROOT . '/public/theme/common/fontawesome-5/webfonts/fa-solid-900.ttf';
        if (file_exists($faFontPath) && class_exists('TCPDF_FONTS')) {
            try {
                $this->faFontName = TCPDF_FONTS::addTTFfont($faFontPath, 'TrueTypeUnicode', '', 32);
            } catch (Exception $e) {
                $this->faFontName = '';
            }
        }

        $pdfa            = getDolGlobalInt('PDF_USE_A', 0);
        $pdf             = new TCPDF('P', 'mm', $this->format, true, 'UTF-8', false, $pdfa);
        $defaultFontSize = pdf_getPDFFontSize($outputLangs);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->Open();
        $pdf->SetFont(pdf_getPDFFont($outputLangs));
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetTitle($outputLangs->convToOutputCharset($this->document_type));
        $pdf->SetSubject($outputLangs->transnoentities($this->document_type));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->SetKeyWords($outputLangs->convToOutputCharset($control->ref) . ' ' . $outputLangs->transnoentities($this->document_type));
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->SetAutoPageBreak(1, $this->marge_basse + 8);

        // ── Page 1: synthesis ────────────────────────────────────────────────

        $pdf->AddPage();
        $this->drawTopRight($pdf);
        $this->drawTitleArea($pdf, $control, $sheet, $project, $totalQuestions, $outputLangs);
        $this->drawSectionBanner($pdf, $outputLangs->convToOutputCharset('Synthèse du contrôle & détails des questions'));
        $this->drawSynthesisSection($pdf, $control, $sheet, $project, $linkedObject, $linkedElement, $signatures, $totalQuestions, $answeredQuestions, $outputLangs);

        // ── Questions section ────────────────────────────────────────────────

        $this->checkPageBreak($pdf, 12);
        $pdf->SetTextColor(...$this->colorTeal);
        $pdf->SetFont('', 'B', 11);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell(0, 7, $outputLangs->convToOutputCharset('Détails des questions et réponses'), 0, 1, 'L');
        $pdf->Ln(2);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($structuredGroups as $groupData) {
            $group     = $groupData['group'];
            $questions = $groupData['questions'];

            if (empty($questions)) {
                continue;
            }

            // Build group stats (choice-type questions only) — ['abbrev' => ['count' => N, 'rgb' => [R,G,B]]]
            $groupStats = [];
            foreach ($questions as $q) {
                if (!$this->isChoiceType($q->type)) {
                    continue;
                }
                $cl          = $controlLineByQuestion[$q->id] ?? null;
                $pictogram   = null;
                $answerColor = null;
                if (!empty($cl) && !empty($cl->answer) && $cl->answer !== '0') {
                    $positions = array_map('trim', explode(',', (string)$cl->answer));
                    foreach ($answersByQuestion[$q->id] ?? [] as $ans) {
                        if (in_array((string)$ans->position, $positions, true)) {
                            $pictogram   = $ans->pictogram;
                            $answerColor = $ans->color;
                            break;
                        }
                    }
                }
                $cfg    = $this->getAnswerConfig($pictogram);
                $abbrev = $cfg['abbrev'];
                $rgb    = !empty($answerColor) ? $this->hexToRgb($answerColor) : $cfg['rgb'];
                if (!isset($groupStats[$abbrev])) {
                    $groupStats[$abbrev] = ['count' => 0, 'rgb' => $rgb];
                }
                $groupStats[$abbrev]['count']++;
            }

            if ($group !== null) {
                $this->checkPageBreak($pdf, 12);
                $this->drawGroupBanner($pdf, 'Groupe : ' . $group->label, count($questions), $groupStats);
            }

            foreach ($questions as $question) {
                $cl          = $controlLineByQuestion[$question->id] ?? null;
                $pictogram   = null;
                $answerColor = null;
                if ($this->isChoiceType($question->type) && !empty($cl) && !empty($cl->answer) && $cl->answer !== '0') {
                    $positions = array_map('trim', explode(',', (string)$cl->answer));
                    foreach ($answersByQuestion[$question->id] ?? [] as $ans) {
                        if (in_array((string)$ans->position, $positions, true)) {
                            $pictogram   = $ans->pictogram;
                            $answerColor = $ans->color;
                            break;
                        }
                    }
                }
                $this->drawQuestionCard($pdf, $question, $cl, $pictogram, $answerColor, $questionPhotosMap[$question->id] ?? [], $outputLangs, $answersByQuestion[$question->id] ?? []);
                $this->drawQuestionTasks($pdf, $questionTasksMap[$question->id] ?? [], $outputLangs);
            }

            $pdf->Ln(1);
        }

        // ── Equipment section ────────────────────────────────────────────────

        if (!empty($controlEquipments)) {
            $this->checkPageBreak($pdf, 30);
            $pdf->Ln(5);

            $pageW   = $pdf->getPageWidth();
            $usableW = $pageW - $this->marge_gauche - $this->marge_droite;

            $this->drawSectionTitle($pdf, $outputLangs->convToOutputCharset($outputLangs->transnoentities('ControlEquipementList')));

            $wRef    = 25;
            $wLib    = 35;
            $wLot    = 30;
            $wDesc   = 50;
            $wDluo   = 20;
            $wRest   = $usableW - $wRef - $wLib - $wLot - $wDesc - $wDluo;

            $equipHeaders = [
                ['w' => $wRef,  'text' => $outputLangs->transnoentities('Ref') . "\n" . $outputLangs->transnoentities('Equipement')],
                ['w' => $wLib,  'text' => $outputLangs->transnoentities('Label')],
                ['w' => $wLot,  'text' => $outputLangs->transnoentities('Ref') . "\n" . $outputLangs->transnoentities('batch_number')],
                ['w' => $wDesc, 'text' => $outputLangs->transnoentities('Description')],
                ['w' => $wDluo, 'text' => $outputLangs->transnoentities('OptimalExpirationDate')],
                ['w' => $wRest, 'text' => $outputLangs->transnoentities('EstimatedLife')],
            ];

            $this->checkPageBreak($pdf, 10);
            $this->fillRect($pdf, $this->marge_gauche, $pdf->GetY(), $usableW, 8, $this->colorTeal);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(255, 255, 255);
            $pdf->SetFont('', 'B', 7.5);
            $hX = $this->marge_gauche;
            foreach ($equipHeaders as $hCol) {
                $pdf->writeHTMLCell($hCol['w'], 8, $hX, $pdf->GetY(), '<div style="text-align:center;">' . nl2br($hCol['text']) . '</div>', 1, 0, false, true, 'C', true);
                $hX += $hCol['w'];
            }
            $pdf->Ln(8);

            $pdf->SetFont('', '', 8);
            $pdf->SetTextColor(...$this->colorBlack);
            $pdf->SetDrawColor(210, 220, 230);
            $rowIdx = 0;

            foreach ($controlEquipments as $equip) {
                $equipData = json_decode($equip->json);
                $productLot->fetch($equip->fk_lot);

                $h  = max(8, $pdf->getStringHeight($wDesc, $equipData->description ?? ''));
                $sx = $this->marge_gauche;
                $sy = $pdf->GetY();

                $this->checkPageBreak($pdf, $h);
                if ($rowIdx % 2 !== 0) {
                    $this->fillRect($pdf, $sx, $sy, $usableW, $h, [245, 247, 250]);
                }
                $rowIdx++;

                $pdf->MultiCell($wRef,  $h, $equip->ref,                   1, 'C', 0, 0, $sx,                                              $sy, true, 0, false, true, $h, 'M');
                $pdf->MultiCell($wLib,  $h, $equipData->label ?? '',        1, 'L', 0, 0, $sx + $wRef,                                    $sy, true, 0, false, true, $h, 'M');
                $pdf->MultiCell($wLot,  $h, $productLot->batch ?? '',       1, 'C', 0, 0, $sx + $wRef + $wLib,                            $sy, true, 0, false, true, $h, 'M');
                $pdf->MultiCell($wDesc, $h, $equipData->description ?? '',  1, 'L', 0, 0, $sx + $wRef + $wLib + $wLot,                    $sy, true, 0, false, true, $h, 'M');
                $pdf->MultiCell($wDluo, $h, dol_print_date($equipData->dluo ?? null), 1, 'C', 0, 0, $sx + $wRef + $wLib + $wLot + $wDesc,      $sy, true, 0, false, true, $h, 'M');
                $pdf->MultiCell($wRest, $h, $equipData->lifetime ?? '',     1, 'C', 0, 0, $sx + $wRef + $wLib + $wLot + $wDesc + $wDluo, $sy, true, 0, false, true, $h, 'M');
                $pdf->Ln($h);
            }
            $pdf->SetDrawColor(0, 0, 0);
        }

        // ── Signatures ───────────────────────────────────────────────────────

        $pdf->Ln(8);
        $this->checkPageBreak($pdf, 40);
        $this->drawSignatureSection($pdf, $signatures, 'LinkedContactsControl', ['ExtSocietyAttendant', 'Attendant'], $outputLangs);
        $pdf->Ln(8);
        $this->checkPageBreak($pdf, 40);
        $this->drawSignatureSection($pdf, $signatures, 'LinkedControllerControl', ['Controller'], $outputLangs);

        // ── Plan d'action (dernière page) ────────────────────────────────────

        $this->drawActionPlanSection($pdf, $questionsAndGroups, $questionTasksMap, $outputLangs);

        // ── Last page footer ─────────────────────────────────────────────────

        $this->drawPageFooter($pdf);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = 'Error generating PDF: ' . $exception->getMessage();
            return -1;
        }

        $this->result = ['fullpath' => $file];
        return 1;
    }
}
