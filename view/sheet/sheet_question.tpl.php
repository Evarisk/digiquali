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
    if ($sheetObject->status < $sheetObject::STATUS_LOCKED) {
        print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=unlinkQuestion&questionId=' . $question->id . '&token=' . newToken() . '">';
        print '<svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 36 36"><path fill="#666" d="M5 5L3.59 6.41l9 9l-4.49 4.38a5.91 5.91 0 0 0 0 8.39a6 6 0 0 0 8.44 0l4.46-4.4l8.63 8.63L31 31Zm10.13 21.76a4 4 0 0 1-5.62 0a3.92 3.92 0 0 1 0-5.55L14 16.79l5.58 5.58Z" class="clr-i-outline clr-i-outline-path-1"/><path fill="#666" d="M21.53 9.22a4 4 0 0 1 5.62 0a3.92 3.92 0 0 1 0 5.55l-4.79 4.76L23.78 21l4.79-4.76a5.92 5.92 0 0 0 0-8.39a6 6 0 0 0-8.44 0l-4.76 4.74L16.78 14Z" class="clr-i-outline clr-i-outline-path-2"/><path fill="none" d="M0 0h36v36H0z"/></svg>';
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