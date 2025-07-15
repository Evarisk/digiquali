<?php 

print '<tr id="question-' . $question->id . '" class="' . ($question->getParentGroupId() > 0 ? 'hidden ' : '') . 'group-question group-question-'. $this->id .' line-row" data-group-id="' . $question->getParentGroupId() . '">';
print '<td ' . $tdOffsetStyle . '>' . $question->getNomUrl(1) . '</td>';
print '<td>' . $question->label . '</td>';
print '<td>' . $question->description . '</td>';
print '<td>' . $langs->transnoentities($question->type) . '</td>';
print '<td class="center"><input type="checkbox" disabled></td>';
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
    if ($this->status < $sheetObject::STATUS_LOCKED) {
        print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=unlinkQuestion&questionId=' . $question->id . '&token=' . newToken() . '">';
        print img_delete();
        print '</a>';
    }
print '</td>';
if ($sheetObject->status < $sheetObject::STATUS_LOCKED) {
    print '<td class="sheet-move-line ui-sortable-handle group-question-handle" data-group-id="' . $this->id . '">';
} else {
    print '<td>';
}
print '</td>';
print '</tr>';