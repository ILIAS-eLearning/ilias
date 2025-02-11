/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 ******************************************************************** */

var dcl = {};
$(document).ready(function () {

  dcl.removeHighlightedRows = function () {
    $('.dcl_comments_active').removeClass('dcl_comments_active');
  };

  /**
   * Returns true if a selected field does not support to be unique or required
   */
  dcl.checkForUnsupportedRequiredOrUniqueFields = function () {
    var fields = ['#datatype_11', '#datatype_7'];
    for (var i in fields) {
      var id = fields[i];
      if ($(id).attr('checked') == 'checked') {
        return true;
      }
    }

    return false;
  };

  dcl.onDatatypeChange = function () {
    var state = dcl.checkForUnsupportedRequiredOrUniqueFields();
    var required = $('#required');
    required.prop('disabled', state);
    var unique = $('#unique');
    unique.prop('disabled', state);
  };

  dcl.onDatatypeChange();

  $('#datatype').change(dcl.onDatatypeChange);

  /**
   * @var $tr tr object to highlight
   */
  dcl.highlightRow = function ($tr) {
    this.removeHighlightedRows();
    $tr.addClass('dcl_comments_active');
  };

  let drag_target;
  dcl.addSorting = function (button, id, url) {
    let item = button.closest('li');
    item.dataset.id = id;
    button.draggable = true;
    button.style.cursor = 'grab';
    button.addEventListener('dragstart', function (event) {
      drag_target = item;
    });
    item.addEventListener('dragover', function (event) {
      let size = item.getBoundingClientRect();
      if (size.top + (size.height / 2) > event.clientY) {
        item.before(drag_target);
      } else {
        item.after(drag_target);
      }
    });
    button.addEventListener('dragend', function (event) {
      let order = new FormData();
      item.closest('ul').children.forEach( element => {
        order.append('order[]', element.dataset.id);
      })
      fetch(url, { method: "POST", body: order, })
    });
  }

  $('a.dcl_comment').click(function () {
    $tr = $(this).parents('tr');
    dcl.highlightRow($tr);
  });

  $('.dcl_actions a[id$="comment"]').click(function () {
    $tr = $(this).parents('td.dcl_actions').parent('tr');
    dcl.highlightRow($tr);
  });

  $('#fixed_content').click(function () {
    dcl.removeHighlightedRows();
  });

  /**
   * Formula fields
   */
  $('a.dclPropExpressionField').click(function () {
    var placeholder = '[[' + $(this).attr('data-placeholder') + ']]';
    var $expression = $('#prop_expression');
    var caretPos = document.getElementById('prop_expression').selectionStart;
    var expression = $expression.val();
    $expression.val(expression.substring(0,
      caretPos) + placeholder + expression.substring(caretPos));
  });

  let form = document.querySelector('form div#datatype');
  if (form !== null) {
    document.querySelector('form div#datatype').closest('form').addEventListener('submit', (event) => {
      const types = event.target.querySelector('div#datatype');
      types.querySelectorAll('input[id^="datatype_"]:not(:checked)').forEach((radio) => {
        if (types.contains(types.querySelector(`div#subform_${radio.id}`))) {
          types.querySelector(`div#subform_${radio.id}`).remove();
        }
      });
    });
  }
});
