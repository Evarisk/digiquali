<?php

$parentGroupId = $question->getParentGroupId();

print '<tr id="question-' . $question->id . '" class="' . ($parentGroupId > 0 ? 'hidden ' : '') . 'question line-row" data-id="' . $question->id . '" data-parent-id="' . $parentGroupId . '" data-position-path="' . $positionPath . '">';
print '<td ' . $tdOffsetStyle . '>' . $question->getNomUrl(1) . '</td>';
print '<td>' . $question->label . '</td>';
print '<td>' . $question->description . '</td>';
print '<td>' . $langs->transnoentities($question->type) . '</td>';
print '<td>' . $question->points . '</td>';
print '<td>' . (!empty($question->grading_policy) ? $langs->trans($question->fields['grading_policy']['arrayofkeyval'][$question->grading_policy]) : $langs->trans('Proportional')) . '</td>';
$mandatoryArray = json_decode($sheetObject->mandatory_questions, true);

print '<td class="center">';
print '<form class="mandatory-form" method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $sheetObject->id . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_mandatory">';
print '<input type="hidden" name="questionId" value="' . $question->id . '">';
print '<input type="hidden" name="questionRef" value="' . $question->ref . '">';
print '<input type="checkbox" onchange="submit();" id="mandatory" name="mandatory" value="' . $question->id . '"' . (in_array($question->id, $mandatoryArray) ? ' checked ' : '') . '" ' . ($sheetObject->status < Sheet::STATUS_LOCKED ? '>' : 'disabled>');
print '</form>';
print '</td>';

print '<td class="center">' . saturne_show_medias_linked(
        'digiquali',
        $conf->digiquali->multidir_output[$conf->entity] . '/question/' . $question->ref . '/photo_ok',
        1, '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/' . $question->ref . '/photo_ok',
        $question, 'photo_ok', 0, 0, 1, 1
    ) . '</td>';
print '<td class="center">' . saturne_show_medias_linked(
        'digiquali',
        $conf->digiquali->multidir_output[$conf->entity] . '/question/' . $question->ref . '/photo_ko',
        1, '', 0, 0, 0, 50, 50, 0, 0, 0, 'question/' . $question->ref . '/photo_ko',
        $question, 'photo_ko', 0, 0, 1, 1
    ) . '</td>';
print '<td class="center">' . $question->getLibStatut(5) . '</td>';
print '<td class="center">';
    if ($sheetObject->status < $sheetObject::STATUS_LOCKED) {
        print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $sheetObject->id . '&amp;action=unlinkQuestion&questionId=' . $question->id . '&token=' . newToken() . '">';
        print '<i class="fa fa-unlink" aria-hidden="true"></i>';
        print '</a>';
    }
print '</td>';
if ($sheetObject->status < $sheetObject::STATUS_LOCKED) {
    print '<td class="sheet-move-line ui-sortable-handle" data-parent-id="' . $parentGroupId . '">';
} else {
    print '<td>';
}
print '</td>';
print '</tr>';
