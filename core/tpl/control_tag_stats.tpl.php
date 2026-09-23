<?php
/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/control_tag_stats.tpl.php
 * \ingroup digiquali
 * \brief   Per-tag answer statistics on the control card
 */

/**
 * The following vars must be defined:
 * Global  : $conf, $db, $langs
 * Objects : $object (Control with loaded lines), $answer (Answer), $category (Categorie)
 * Arrays  : $questionsAndGroups
 */

global $conf, $db, $langs;

if (empty($object->lines) || !isModEnabled('categorie')) {
    return;
}

// Build flat question map from questionsAndGroups, recursing into nested sub-groups so that
// questions located inside groups (and their sub-groups) are included in the tag statistics.
$allQuestionsMap  = [];
$collectQuestions = function ($items) use (&$collectQuestions, &$allQuestionsMap, $db) {
    if (!is_array($items)) {
        return;
    }
    foreach ($items as $item) {
        if ($item->element == 'question') {
            $allQuestionsMap[$item->id] = $item;
        } elseif ($item->element == 'questiongroup') {
            $qg = new QuestionGroup($db);
            $qg->fetch($item->id);
            $collectQuestions($qg->fetchQuestionsOrderedByPosition());
            $collectQuestions($qg->fetchQuestionGroupsOrderedByPosition());
        }
    }
};
$collectQuestions($questionsAndGroups);

$typesWithAnswers = ['UniqueChoice', 'OkKo', 'OkKoToFixNonApplicable', 'MarqueNF', 'MultipleChoices'];

// Load answers (position => meta) and categories for each qualifying question
$questionAnswers    = []; // qId => [position => ['value','color','correct']]
$questionCategories = []; // qId => [catId => cat]
$allTagsMap         = []; // catId => cat  (leaf/child categories only)

foreach ($allQuestionsMap as $qId => $q) {
    if (!in_array($q->type, $typesWithAnswers)) {
        continue;
    }

    $qAnswers = $answer->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.status = ' . Answer::STATUS_VALIDATED . ' AND t.fk_question = ' . (int)$qId]);
    if (is_array($qAnswers)) {
        foreach ($qAnswers as $qa) {
            $questionAnswers[$qId][$qa->position] = [
                'value'   => $qa->value,
                'color'   => $qa->color,
                'correct' => (int)$qa->correct,
            ];
        }
    }

    $qCats = $category->containing($qId, 'question');
    if (is_array($qCats) && !empty($qCats)) {
        foreach ($qCats as $cat) {
            // Only keep child categories (leaf tags), not parent/root ones
            if (empty($cat->fk_parent)) {
                continue;
            }

            $questionCategories[$qId][$cat->id] = $cat;
            $allTagsMap[$cat->id]               = $cat;
        }
    }
}

// Virtual category for questions with no tag
$noTagCatId = 0;
$noTagCat   = (object)['id' => $noTagCatId, 'label' => $langs->trans('NoTag'), 'fk_parent' => -1];

foreach ($allQuestionsMap as $qId => $q) {
    if (!in_array($q->type, $typesWithAnswers) || !empty($questionCategories[$qId])) {
        continue;
    }
    $questionCategories[$qId][$noTagCatId] = $noTagCat;
    $allTagsMap[$noTagCatId]               = $noTagCat;
}

if (empty($allTagsMap)) {
    return;
}

// Init tag stats preserving answer display order from question definitions
$tagStats         = []; // catId => [answerValue => count]
$tagAnswerMeta    = []; // catId => [answerValue => ['color','correct']]
$tagTotalAnswered = []; // catId => int

foreach ($allQuestionsMap as $qId => $q) {
    if (!in_array($q->type, $typesWithAnswers) || !isset($questionAnswers[$qId])) {
        continue;
    }

    foreach ($questionCategories[$qId] ?? [] as $catId => $cat) {
        if (!isset($tagStats[$catId])) {
            $tagStats[$catId]         = [];
            $tagAnswerMeta[$catId]    = [];
            $tagTotalAnswered[$catId] = 0;
        }

        foreach ($questionAnswers[$qId] as $aMeta) {
            $val = $aMeta['value'];
            if (!array_key_exists($val, $tagStats[$catId])) {
                $tagStats[$catId][$val]      = 0;
                $tagAnswerMeta[$catId][$val] = ['color' => $aMeta['color'], 'correct' => $aMeta['correct']];
            }
        }
    }
}

// Count answers from the loaded control lines
foreach ($object->lines as $line) {
    $qId = $line->fk_question;
    if (!isset($allQuestionsMap[$qId]) || !isset($questionAnswers[$qId])) {
        continue;
    }

    $q = $allQuestionsMap[$qId];
    if (!in_array($q->type, $typesWithAnswers)) {
        continue;
    }

    $pos   = (int)$line->answer;
    $aMeta = ($pos > 0) ? ($questionAnswers[$qId][$pos] ?? null) : null;
    if (!$aMeta) {
        continue;
    }

    foreach ($questionCategories[$qId] ?? [] as $catId => $cat) {
        if (!isset($tagStats[$catId])) {
            continue;
        }

        $val = $aMeta['value'];
        if (!array_key_exists($val, $tagStats[$catId])) {
            continue;
        }

        $tagStats[$catId][$val]++;
        $tagTotalAnswered[$catId]++;
    }
}

// Collect all unique answer values in display order + global colors
$allAnswerValues = [];
$answerColors    = [];
foreach ($allTagsMap as $catId => $cat) {
    if (!isset($tagAnswerMeta[$catId])) {
        continue;
    }

    foreach ($tagAnswerMeta[$catId] as $val => $meta) {
        if (!in_array($val, $allAnswerValues)) {
            $allAnswerValues[] = $val;
            $answerColors[$val] = $meta['color'];
        }
    }
}

if (empty($allAnswerValues)) {
    return;
}

// Build the full JSON payload consumed by control_tag_stats.js
$jsonTags = [];
foreach ($allTagsMap as $catId => $cat) {
    if (!isset($tagStats[$catId])) {
        continue;
    }

    $answerMeta = [];
    foreach ($tagAnswerMeta[$catId] as $val => $meta) {
        $answerMeta[$val] = ['color' => $meta['color'], 'correct' => $meta['correct']];
    }

    $jsonTags[(string)$catId] = [
        'label'      => $cat->label,
        'stats'      => $tagStats[$catId],
        'answerMeta' => $answerMeta,
        'total'      => $tagTotalAnswered[$catId] ?? 0,
    ];
}

$i18nDecode = function (string $key) use ($langs): string {
    return html_entity_decode($langs->trans($key), ENT_COMPAT | ENT_HTML5, 'UTF-8');
};

$jsonData = [
    'tags'         => $jsonTags,
    'answerValues' => $allAnswerValues,
    'answerColors' => $answerColors,
    'i18n'         => [
        'tag'            => $i18nDecode('Tag'),
        'total'          => $i18nDecode('Total'),
        'conformityRate' => $i18nDecode('ConformityRate'),
        'conformity'     => $i18nDecode('Conformity'),
        'allTags'        => $i18nDecode('AllTags'),
        'noTag'          => $i18nDecode('NoTag'),
    ],
];
?>

<script type="application/json" id="controlTagStatsData"><?php echo json_encode($jsonData, JSON_UNESCAPED_UNICODE); ?></script>
<script src="<?php echo DOL_URL_ROOT; ?>/includes/nnnick/chartjs/dist/chart.min.js"></script>
<script src="<?php echo dol_buildpath('/digiquali/js/modules/control_tag_stats.js', 1); ?>"></script>

<div id="controlTagStatsContainer">

    <!-- Tag filter (no submit button — change event triggers JS re-render) -->
    <div class="tag-stats-filter-bar">
        <span><b><?php echo $langs->trans('Tag'); ?></b></span>
        <select id="controlTagStatsFilter" class="flat">
            <option value="0"><?php echo $langs->trans('AllTags'); ?></option>
            <?php foreach ($allTagsMap as $catId => $cat) : ?>
            <option value="<?php echo (int)$catId; ?>"><?php echo dol_escape_htmltag($cat->label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Bar chart + Donut charts row (side-by-side when single tag selected) -->
    <div id="controlTagChartsRow">

        <!-- Bar chart -->
        <div id="controlTagBarSection" class="sheet-stats-container">
            <div class="stats-section">
                <div class="stats-title"><i class="fas fa-chart-bar"></i> <?php echo $langs->trans('AnswersRepartitionByTag'); ?></div>
                <div class="stats-content">
                    <canvas id="controlTagBarChart"></canvas>
                    <div class="bar-chart-legend">
                        <?php foreach ($allAnswerValues as $val) : ?>
                        <span>
                            <span class="legend-color-swatch" style="background:<?php echo dol_escape_htmltag($answerColors[$val] ?? '#999'); ?>;"></span>
                            <?php echo dol_escape_htmltag($val); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donut charts (populated by JS) -->
        <div id="controlTagDonutSection" class="sheet-stats-container">
            <div class="stats-section">
                <div class="stats-title"><i class="fas fa-chart-pie"></i> <?php echo $langs->trans('DetailByTag'); ?></div>
                <div id="controlTagDonutsContainer" class="stats-content"></div>
            </div>
        </div>

    </div><!-- /controlTagChartsRow -->

    <!-- Stats table (populated by JS) -->
    <div class="sheet-stats-container">
        <div class="stats-section">
            <div class="stats-title"><i class="fas fa-table"></i> <?php echo $langs->trans('StatsByTagAndStatus'); ?></div>
            <div class="stats-content">
                <table id="controlTagStatsTable" class="question-stats-table">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /controlTagStatsContainer -->
