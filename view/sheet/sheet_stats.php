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
 *   	\file       view/sheet/sheet_stats.php
 *		\ingroup    digiquali
 *		\brief      Page to show sheet statistics
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/question.class.php';
require_once __DIR__ . '/../../class/questiongroup.class.php';
require_once __DIR__ . '/../../class/control.class.php';
require_once __DIR__ . '/../../class/answer.class.php';
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../../lib/digiquali_answer.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(["other", "product", 'bills', 'orders']);

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'sheetstats'; // To manage different context of search

// Initialize technical objects
$object = new Sheet($db);
$control = new Control($db);
$controlLine = new ControlLine($db);
$answer = new Answer($db);
$extrafields = new ExtraFields($db);

// View objects
$form = new Form($db);

$hookmanager->initHooks(array('sheetstats', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread = $user->rights->digiquali->sheet->read;

// Security check - Protection if external user
saturne_check_access($permissiontoread, $object);

/*
 * View
 */

$title = $langs->trans('Sheet') . ' - ' . $langs->trans('Statistics');
$help_url = 'FR:Module_Digiquali#Fiche_modèle';

saturne_header(0, '', $title, $help_url);

$linkback = '<a href="' . DOL_URL_ROOT . '/custom/digiquali/view/sheet/sheet_list.php' . (!empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

$morehtmlref = '<div class="refidno">';
$morehtmlref .= $object->ref;
$morehtmlref .= '</div>';

$head = sheet_prepare_head($object);
print dol_get_fiche_head($head, 'stats', $title, -1, $object->picto);

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 1, '');

print '<div class="fiche sheet-stats-container">';

$filter = ['fk_sheet' => $object->id];
$controls = $control->fetchAll('', '', 0, 0, $filter);

$controlsNumber = count($controls);

$averageVerdict = $object->getAverageVerdict($controls);

print '<div class="stats-section">';
print '<div class="stats-title">' . $langs->trans("GlobalStatistics") . '</div>';
print '<div class="stats-content">';

print '<div class="stat-card">';
print '<div class="stat-value">' . $controlsNumber . '</div>';
print '<div class="stat-label">' . $langs->trans("NumberOfControls") . '</div>';
print '</div>';

if ($controlsNumber > 0) {
    print '<div class="stat-card">';
    print '<div class="stat-value">' . round($averageVerdict * 100) . '%</div>';
    print '<div class="stat-label">' . $langs->trans("AverageVerdictScore") . '</div>';
    print '</div>';
}

print '</div>';
print '</div>';


$questions = $object->fetchAllQuestions();

if (!empty($questions) && !empty($controls)) {
    $questionAnswerStats = [];

    foreach ($questions as $question) {
        if (in_array($question->type, ['UniqueChoice', 'OkKo', 'OkKoToFixNonApplicable', 'MultipleChoices'])) {
            $possibleAnswers = $answer->fetchAll('ASC', 'position', 0, 0,  ['customsql' => 't.status > ' . Answer::STATUS_DELETED . ' AND t.fk_question = ' . $question->id]);
            if (!empty($possibleAnswers)) {
                foreach ($possibleAnswers as $possibleAnswer) {
                    $questionAnswerStats[$question->id][$possibleAnswer->id] = [
                        'nb_answers' => 0,
                    ];
                }
            }
        } else if ($question->type == 'Percentage') {
            $questionAnswerStats[$question->id][] = [
                'percentage' => 0,
            ];
        } else if ($question->type == 'Range') {
            $questionAnswerStats[$question->id] = [];
        }

    }
    $i = 0;
    foreach ($controls as $control) {
        $control->fetchLines();
        foreach ($control->lines as $controlAnswer) {
            $questionLinked = new Question($db);
            $questionLinked->fetch($controlAnswer->fk_question);
            if (in_array($questionLinked->type, ['UniqueChoice', 'OkKo', 'OkKoToFixNonApplicable', 'MultipleChoices'])) {
                if ($controlAnswer->answer > 0) {
                    $questionAnswerStats[$controlAnswer->fk_question][$controlAnswer->answer]['nb_answers'] += 1;
                }
            } else if ($questionLinked->type == 'Percentage') {
                $questionAnswerStats[$controlAnswer->fk_question][$i] = [
                    'percentage' => $controlAnswer->answer,
                ];
            } else if ($questionLinked->type == 'Range') {
                if (is_numeric($controlAnswer->answer)) {
                    $questionAnswerStats[$controlAnswer->fk_question][] = [
                        'range' => $controlAnswer->answer,
                    ];
                }
            }
        }
        $i++;
    }

    print '<div class="stats-section">';
    print '<div class="stats-title">' . $langs->trans("QuestionsStatistics") . '</div>';
    print '<div class="stats-content">';

    print '<table class="question-stats-table">';
    print '<thead>';
    print '<tr class="liste_titre">';
    print '<th>' . $langs->trans("Question") . '</th>';
    print '<th>' . $langs->trans("Type") . '</th>';
    print '<th>' . $langs->trans("AnswersStatistics") . '</th>';
    print '<th>' . $langs->trans("Graphe") . '</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';

    foreach ($questions as $question) {
        print '<tr class="question-answer-container ">';
        print '<td>' . $question->getNomUrl(1) . '</td>';
        print '<td>' . $langs->trans($question->type) . '</td>';

        print '<td>';
        $answers = $answer->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = ' . Answer::STATUS_VALIDATED . ' AND t.fk_question = ' . $question->id]);
        $pictos = get_answer_pictos_array();

        if (!empty($answers)) {
            $totalAnswers = array_sum(array_column($questionAnswerStats[$question->id] ?? [], 'nb_answers'));

            $maxCount = 0;
            foreach ($answers as $a) {
                $count = $questionAnswerStats[$question->id][$a->position]['nb_answers'] ?? 0;
                if ($count > $maxCount) {
                    $maxCount = $count;
                }
            }

            print '<div class="select-answer select-answer-stats">';

            foreach ($answers as $a) {
                $count = $questionAnswerStats[$question->id][$a->position]['nb_answers'] ?? 0;
                $percentage = $totalAnswers > 0 ? round(($count / $totalAnswers) * 100) : 0;

                $picto = !empty($a->pictogram) && !empty($pictos[$a->pictogram]) ? $pictos[$a->pictogram]['picto_source'] : $a->value;

                $highlightClass = ($count === $maxCount && $count > 0) ? ' highlight-answer' : '';

                print '<div class="answer answer-icon static' . $highlightClass . '" style="color: ' . $a->color . '; box-shadow: 0 0 0 3px ' . $a->color . ';">';
                print $picto;
                print '<span class="answer-stats-badge" style="font-size: 11px; padding: 4px 8px;">' . $percentage . '%</span>';
                print '</div>';
            }

            print '</div>';

            print '</td>';

        }

        print '<td class="answer-pie-container">';

        $object->showAnswerRepartition($question, $answers, $questionAnswerStats);

        print '</td>';
        print '</tr>';

    }

    print '</tbody>';
    print '</table>';

    print '</div>';
    print '</div>';
} else {
    print '<div class="stats-section">';
    print '<div class="stats-title">' . $langs->trans("QuestionsStatistics") . '</div>';
    print '<div class="stats-content">';
    if (empty($controls)) {
        print $langs->trans('NoControlsLinked');
        print '<br />';
    }
    if (empty($questions)) {
        print $langs->trans("NoQuestionsLinked");
    }
    print '</div>';
    print '</div>';
}

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
