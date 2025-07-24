<?php 

// DISPLAY QUESTION GROUP
if ($questionOrGroup->element === 'questiongroup') {
    $group = $questionOrGroup;

    $group->displayInSheetCard($object, $positionPath);

} else { // DISPLAY QUESTION
    $question = $questionOrGroup;
    $sheetObject = $object;
    $question->displayInSheetCard($sheetObject, $positionPath);
}