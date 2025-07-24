/**
 * Initialise l'objet "sheet" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.digiquali.sheet = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.sheet.init = function() {
	window.digiquali.sheet.event();
  window.digiquali.sheet.displayOpenedGroups();
};

/**
 * La méthode contenant tous les événements pour la fiche modèle.
 *
 * @since   1.3.0
 * @version 21.2.0
 *
 * @return {void}
 */
window.digiquali.sheet.event = function() {

  $( document ).on( 'click', '.toggle-group-in-tree', window.digiquali.sheet.toggleGroupInTree );
  $( document ).on( 'click', '.addQuestionButton, .addGroupButton', window.digiquali.sheet.buttonActions );
  window.digiquali.sheet.initDragAndDropInParent();

  const savedState = JSON.parse(localStorage.getItem('digiqualiGroupStates') || '{}');

  $('.group-item').each(function () {
    const groupId = $(this).data('id');
    const subQuestions = $(this).next('.sub-questions');

    if (savedState[groupId] === false) {
      subQuestions.addClass('collapsed');
      $(this).find('.toggle-group-in-tree').removeClass('fa-chevron-right').addClass('fa-chevron-down');
    } else if (savedState[groupId] === true) {
      subQuestions.removeClass('collapsed');
      $(this).find('.toggle-group-in-tree').removeClass('fa-chevron-down').addClass('fa-chevron-right');
    }
  });
};

/**
 * La méthode permettant d'afficher le contenu des groupes 'ouverts'
 *
 * @since   21.2.0
 * @version 21.2.0
 *
 * @return {void}
 */
window.digiquali.sheet.displayOpenedGroups = function() {

  const savedState = JSON.parse(localStorage.getItem('digiqualiGroupStates') || '{}');

  $('#tablelines .question-group').each(function () {
    const groupId = $(this).data('id');

    if (savedState[groupId] === true) {
      window.digiquali.sheet.toggleGroup(groupId);
    }
  });
};

/**
 * Show or hide the add question or group row
 *
 * @since   20.1.0
 * @version 21.2.0
 */
window.digiquali.sheet.buttonActions = function(evt) {

  const currentGroupId = evt.currentTarget.dataset.parentId;
    
  const addQuestionRow = $(`#addQuestionRow-${currentGroupId}`);
  const addGroupRow = $(`#addGroupRow-${currentGroupId}`);

  if ($(this).data('action') === 'addQuestionButton') {
    addQuestionRow.removeClass('hidden');
    addGroupRow.addClass('hidden');
  } else if ($(this).data('action') === 'addGroupButton') {
    addGroupRow.removeClass('hidden');
    addQuestionRow.addClass('hidden');
  } else {
    addGroupRow.addClass('hidden');
    addQuestionRow.addClass('hidden');
  }
}

/**
 * Show or hide the sub-questions of a group
 *
 * @since   20.1.0
 * @version 21.2.0
 * @param groupId
 */
window.digiquali.sheet.toggleGroup = function(groupId) {
  const groupQuestions = $(`.question[data-parent-id="${groupId}"]`);
  groupQuestions.toggleClass('hidden');
  const toggleIcon = $(`#group-${groupId} .toggle-icon`);

  const addLine = $(`#addLine-${groupId}`);
  addLine.toggleClass('hidden');

  const isHidden = addLine.hasClass('hidden');
  toggleIcon.text(isHidden ? '+' : '-');

  // When closing the group
  if (isHidden) {
    // hide questions/groups add forms
    const groupRow = $(`#addGroupRow-${groupId}`);
    groupRow.addClass('hidden');
    const questionRow = $(`#addQuestionRow-${groupId}`);
    questionRow.addClass('hidden');
  }

  window.digiquali.sheet.saveGroupState(groupId, !isHidden);
}

/**
 * Show or hide the sub-questions of a group
 *
 * @since   21.2.0
 * @version 21.2.0
 * @param groupId
 * @param state boolean true if opened, false if closed
 */
window.digiquali.sheet.saveGroupState = function(groupId, state) {
  const openGroupStates = JSON.parse(localStorage.getItem('digiqualiGroupStates') || '{}');
  openGroupStates[groupId] = state;
  localStorage.setItem('digiqualiGroupStates', JSON.stringify(openGroupStates));
}

/**
 * Close all groups
 *
 * @since   20.1.0
 * @version 20.1.0
 */
window.digiquali.sheet.closeAllGroups = function () {
  const groupQuestions = $('.group-question');
  const toggleIcons = $('.toggle-icon');

  groupQuestions.addClass('hidden');
  toggleIcons.text('+');
}

/**
 * Show or hide the sub-questions of a group
 *
 * @since   20.1.0
 * @version 20.1.0
 */
window.digiquali.sheet.toggleGroupInTree = function () {
  const groupItem = $(this).closest('.group-item');
  const groupId = groupItem.data('id');
  const subQuestions = groupItem.next('.sub-questions');

  $(this).toggleClass('fa-chevron-up fa-chevron-down');
  subQuestions.toggleClass('collapsed');

  const isCollapsed = subQuestions.hasClass('collapsed');
  window.digiquali.sheet.saveGroupState(groupId, !isCollapsed);
};

/**
 * Grey out the question or group selected
 *
 * @since   20.1.0
 * @version 20.1.0
 */
window.digiquali.sheet.greyOut = function () {
  const questionGroups = $('.group-item');
  const questions = $('.question-item');
  const sheets = $('.sheet-header');

  questionGroups.removeClass('selected');
  questions.removeClass('selected');
  sheets.removeClass('selected');
}

/**
 * Drag and drop for questions and groups inside their parent
 *
 * @since   21.2.0
 * @version 21.2.0
 *
 * @return {void}
 */
window.digiquali.sheet.initDragAndDropInParent = function () {
  $('#tablelines tbody').sortable(
    {
      handle: $('.sheet-move-line'),
      tolerance: 'intersect',
      update: function(event, ui) {
        // Get the row element of the dragged item
        const movedEl = ui.item.closest('tr');

        // Get the parent id of the current dragged item
        const currentParentId = movedEl.data('parentId');
        let newParentId = 0; // default is sheet root

        const movedElNewIndex = ui.item.index();

        // If parent is not the sheet root
        if (currentParentId > 0) {
          // Search if dragged item stays in the same parent
          let prevGroupEl = $('#tablelines tbody tr:nth-child(' + (movedElNewIndex + 1) + ')');
          do {  
            prevGroupEl = prevGroupEl.prev();
            // check path to be sure
          } while (prevGroupEl.length > 0 && (!prevGroupEl.hasClass('question-group') || prevGroupEl.data('parentId') == currentParentId));

          // If not stays in the same parent, cancel move
          if (prevGroupEl.data('id') != currentParentId) {
            $("#dialog-moved-error").dialog({
              modal: true,
              buttons: {
                Ok: function() {
                  $(this).dialog("close");
                }
              }
            });
            $(this).sortable("cancel");
            return;
          } else {
            newParentId = prevGroupEl.data('id');
          }
        }

        let token = $('.fiche').find('input[name="token"]').val();

        let separator = '&'
        if (document.URL.match(/action=/)) {
          document.URL = document.URL.split(/\?/)[0]
          separator = '?'
        }

        let questionPositions = [];
        let questionGroupPositions = [];
        let positionIndex = 1;

        $(`#tablelines tr[data-parent-id="${newParentId}"]`).each(function(index, element) {
          element = $(element);
          const newPosition = {
              'id': element.data('id'),
              'position': positionIndex
          }

          if (element.hasClass('question-group')) {
            questionGroupPositions.push(newPosition);
          } else if (element.hasClass('question')) {
            questionPositions.push(newPosition);
          }
          positionIndex++;
        });

        const newPositionsData = {
          question_positions: questionPositions,
          questiongroup_positions: questionGroupPositions
        }

        if ($('.side-nav .question-list').length > 0) {
          window.saturne.loader.display($('.side-nav .question-list'));
        }

        $.ajax({
          url: document.URL + separator + "action=moveLine&token=" + token + "&groupId=" + newParentId + '&movedItemId=' + movedEl.data('id') + '&movedItemType=' + (movedEl.hasClass('question') ? 'question' : 'questiongroup'),
          type: "POST",
          data: JSON.stringify(newPositionsData),
          processData: false,
          contentType: false,
          success: function (resp) {
            if ($('.side-nav .question-list')) {
              $('.side-nav .question-list').html($(resp).find('.side-nav .question-list'));
              window.saturne.loader.remove($('.side-nav .question-list'));
            }
            location.reload();
          },
          error: function() {}
        });
      }
    }
  );
};


