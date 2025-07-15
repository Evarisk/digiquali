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
};

/**
 * La méthode contenant tous les événements pour la fiche modèle.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.sheet.event = function() {

  $( document ).on( 'click', '.toggle-group-in-tree', window.digiquali.sheet.toggleGroupInTree );
  $( document ).on( 'click', '.addQuestionButton, .addGroupButton', window.digiquali.sheet.buttonActions );
  $(document).on('mouseenter', '.sheet-move-line.ui-sortable-handle', window.digiquali.sheet.draganddrop);
  $(document).on('mouseenter', '.group-question-handle', window.digiquali.sheet.dragandDropInGroup);

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
 * Show or hide the add question or group row
 *
 * @since   20.1.0
 * @version 20.1.0
 */
window.digiquali.sheet.buttonActions = function(evt) {

  const currentGroupId = evt.currentTarget.dataset.groupId;
    
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
 * @version 20.1.0
 * @param groupId
 */
window.digiquali.sheet.toggleGroup = function(groupId) {
  const groupQuestions = $(`.group-question-${groupId}`);
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

  groupQuestions.each(function () {
    const question = $(this);
    question.toggleClass('hidden');
  });
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
  const savedState = JSON.parse(localStorage.getItem('digiqualiGroupStates') || '{}');

  savedState[groupId] = !isCollapsed;
  localStorage.setItem('digiqualiGroupStates', JSON.stringify(savedState));
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
 * Drag and drop on move-line action
 *
 * @since   20.1.0
 * @version 20.1.0
 *
 * @return {void}
 */
window.digiquali.sheet.draganddrop = function () {
  $(this).css('cursor', 'pointer');

  $('#tablelines tbody').sortable({
    handle: '.sheet-move-line',
    connectWith:'#tablelines tbody .line-row-group',
    tolerance:'intersect',
    over:function(){
      $(this).css('cursor', 'grabbing');
    },
    stop: function(event, ui) {
      $(this).css('cursor', 'default');
      let token = $('.fiche').find('input[name="token"]').val();

      let separator = '&'
      if (document.URL.match(/action=/)) {
        document.URL = document.URL.split(/\?/)[0]
        separator = '?'
      }
      let lineOrder = [];
      // find new parent
      const movedEl = ui.item;
      let siblingEl = movedEl.prev('.question-group');
      // TODO group id devrait être le vrai id du groupe
      // TODO parent id devrait être le groupe parent
      // TODO ajouter data-position ?
      movedEl.data('group-id', siblingEl.data('group-id'));
      
      $('.line-row').each(function() {
        lineOrder.push($(this).attr('id'));
      });
      // TODO récupérer tous les éléments ayant un data-parent-id
      // et faire un tableau avec id élément => id parent dans l'ordre d'apparition dans le DOM et sa position

      window.saturne.loader.display($('.side-nav .question-list'));

      $.ajax({
        url: document.URL + separator + "action=moveLine&token=" + token,
        type: "POST",
        data: JSON.stringify({
          order: lineOrder
        }),
        processData: false,
        contentType: false,
        success: function (resp) {
          $('.side-nav .question-list').html($(resp).find('.side-nav .question-list'));
          window.saturne.loader.remove($('.side-nav .question-list'));
        },
        error: function() {}
      });
    }
  });
};

/**
 * Drag and drop for questions within a group
 *
 * @since   20.1.0
 * @version 20.1.0
 *
 * @return {void}
 */
window.digiquali.sheet.dragandDropInGroup = function () {
  const $handle = $(this);
  const groupId = $handle.data('group-id');

  $(this).css('cursor', 'pointer');

  if ($('#tablelines tbody').hasClass('ui-sortable')) {
    $('#tablelines tbody').sortable('destroy');
  }

  $('#tablelines tbody').sortable({
    handle: '.group-question-handle',
    items: `.group-question-${groupId}`,
    tolerance: 'intersect',
    over: function() {
      $(this).css('cursor', 'grabbing');
    },
    stop: function(event, ui) {
      $(this).css('cursor', 'default');
      let token = $('.fiche').find('input[name="token"]').val();

      let separator = '&'
      if (document.URL.match(/action=/)) {
        document.URL = document.URL.split(/\?/)[0]
        separator = '?'
      }

      let lineOrder = [];
      $(`.group-question-${groupId}`).each(function() {
        const questionId = $(this).attr('id').replace('question-', '');
        lineOrder.push(questionId);
      });

      window.saturne.loader.display($('.side-nav .question-list'));

      $.ajax({
        url: document.URL + separator + "action=moveLineInGroup&token=" + token + "&groupId=" + groupId,
        type: "POST",
        data: JSON.stringify({
          order: lineOrder
        }),
        processData: false,
        contentType: false,
        success: function (resp) {
          $('.side-nav .question-list').html($(resp).find('.side-nav .question-list'));
          window.saturne.loader.remove($('.side-nav .question-list'));
        },
        error: function() {}
      });
    }
  });
};


