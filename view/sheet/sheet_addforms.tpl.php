<?php 

$isAddFormsVisible = $isAddFormsVisible ?? false;
$question = new Question($db);
$questionGroup = new QuestionGroup($db);

// Ensure distinct actions are set in the form
if ($object->status < $object::STATUS_LOCKED) {
    print '<tr id="addLine-'. $groupId .'" class="add-line' . ($isAddFormsVisible ? '' : ' hidden') . '">';
    print '<td class="maxwidth300 widthcentpercentminusx" colspan="9" ' . $tdOffsetStyle . '>';
    print '<div id="addChoice-'. $groupId .'" class="addChoice">';
    print '<button type="button" class="addQuestionButton butAction" data-action="addQuestionButton" data-group-id="'. $groupId .'">' . img_picto('', $question->picto) . ' ' . $langs->trans("Question") . ' ' . img_picto('', 'fa-link') . '</button>';
    print '<button type="button" class="addGroupButton butAction" data-action="addGroupButton" data-group-id="'. $groupId .'">' . img_picto('', $questionGroup->picto) . ' ' . $langs->trans("QuestionGroup") . ' ' . img_picto('', 'fa-link') . '</button>';
    print '</div>';
    print '</td>';
    print '</tr>';

    // Form for adding a question
    print '<tr id="addQuestionRow-'. $groupId .'" class="hidden">';
    print '<td class="maxwidth300" colspan="9" ' . $tdOffsetStyle . '>';
    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';

    print img_picto('', $question->picto, 'class="pictofixedwidth"') . $question->selectQuestionList(0, 'questionId', '', '1', 0, 0, array(), '', 0, 0, 'maxwidth600 minwidth400', '', false);
    print '<input type="hidden" name="action" value="addQuestion">';
    print '<input type="hidden" name="targetGroupId" value="' . $groupId . '">';
    print '<input type="submit" id="actionButtonAdd" class="button hideifnotset button-save" name="add" value="' . $langs->trans("Add") . '">';
    print '</td>';
    print '</form>';
    print '</tr>';

    // Form for adding a group
    print '<tr id="addGroupRow-'. $groupId .'" class="hidden">';
    print '<td class="maxwidth300" colspan="9" ' . $tdOffsetStyle . '>';
    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    print img_picto('', $questionGroup->picto, 'class="pictofixedwidth"') . $questionGroup->selectQuestionGroupList(0, 'questionGroupId', 's.status IN (' . QuestionGroup::STATUS_VALIDATED . ', ' . QuestionGroup::STATUS_LOCKED . ')', '1', 0, 0, array(), '', 0, 0, 'maxwidth600 minwidth400', '', false);
    print '<input type="hidden" name="action" value="addQuestionGroup">';
    print '<input type="hidden" name="targetGroupId" value="' . $groupId . '">';
    print '<input type="submit" id="actionButtonAddQuestionGroup" class="button hideifnotset button-save" name="add" value="' . $langs->trans("Add") . '">';
    print '</td>';
    print '</form>';
    print '</tr>';
}