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
 *********************************************************************/

(function () {
  const longmenu = () => {
    let autocompleteLength;
    let answerOptions;
    const init = (autocompleteLengthParam, answerOptionsParam) => {
      autocompleteLength = autocompleteLengthParam;
      answerOptions = answerOptionsParam;
      const longMenuInputs = document.querySelectorAll('.long_menu_input');
      const longMenuInputsIgnore = document.querySelectorAll('.long_menu_input_ignore');

      longMenuInputs.forEach((input, index) => {
        if (input.nodeName === 'INPUT') {
          let longest = answerOptions[index].reduce((a, b) => {
            return a.length > b.length ? a : b;
          });
          input.setAttribute('size', longest.length);
          input.addEventListener('keyup', onChangeHandler);
          input.addEventListener('focus', onChangeHandler);
        }
      });

      longMenuInputsIgnore.forEach(
              (input) => {
        if (input.nodeName === 'INPUT') {
          let longest = input.value;
          input.setAttribute('size', longest.length);
        }
      }
      );
    };

    const onChangeHandler = (e) => {
      const name = e.target.name;
      const index = name.substring(name.indexOf('[') + 1, name.indexOf(']'));

      if (e.target.nextElementSibling?.nodeName === 'UL') {
        e.target.nextElementSibling.remove();
      }

      if (e.key === 'Tab' || e.target.value.length < autocompleteLength) {
        return;
      }

      const matchingAnswers = answerOptions[index].filter((answer) => { return answer.includes(e.target.value) });

      if (matchingAnswers === []) {
        return;
      }

      let list = document.createElement('ul');
      matchingAnswers.forEach((answer) => {
        let listElement = document.createElement('li');
        listElement.textContent = answer;
        list.appendChild(listElement);
      });
      list.addEventListener('click', onSelectHandler);
      e.target.parentNode.appendChild(list);
    };

    const onSelectHandler = (e) => {
      e.target.parentNode.previousElementSibling.value = e.target.textContent;
      e.target.parentNode.remove();
    };

    const public_interface = {
      init
    };
    return public_interface;
  };

  il = il || {};
  il.test = il.test || {};
  il.test.player = il.test.player || {};
  il.test.player.longmenu = longmenu();
}());
