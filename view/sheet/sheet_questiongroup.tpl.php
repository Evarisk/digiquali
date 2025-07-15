<?php 

// DISPLAY QUESTION GROUP
if ($questionOrGroup->element === 'questiongroup') {
    $group = $questionOrGroup;

    $group->displayInSheetCard($object);

} else { // DISPLAY QUESTION
    $question = $questionOrGroup;
    
    $sheetObject = $object;
    include DOL_DOCUMENT_ROOT . '/custom/digiquali/view/sheet/sheet_question.tpl.php';
}