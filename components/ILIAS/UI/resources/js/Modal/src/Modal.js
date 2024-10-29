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
 */

export default class Modal {
  /**
    * @type {jQuery}
    */
  #jquery;

  /**
   * @type {array}
   */
  #triggeredSignalsStorage = [];

  /**
   * @type {array}
   */
  #initializedModalboxes = {};

  /**
   * @param {jQuery} jquery
   */
  constructor(jquery) {
    this.#jquery = jquery;
  }

  /**
   * @param {HTMLDialogElement} component
   * @param {string} closeSignal
   * @param {array} options
   * @param {array} signalData
   */
  showModal(component, options, signalData, closeSignal) {
    if (!component
        || (component?.tagName !== 'DIALOG' && !options?.ajaxRenderUrl)
    ) {
      throw new Error('component is not a dialog (or triggers one).');
    }

    if (closeSignal) {
      this.#jquery(component.ownerDocument).on(
        closeSignal,
        () => component.close(),
      );
    }

    if (this.#triggeredSignalsStorage[signalData.id] === true) {
      return;
    }
    this.#triggeredSignalsStorage[signalData.id] = true;

    if (options.ajaxRenderUrl) {
      this.#jquery(component).load(options.ajaxRenderUrl, () => {
        const dialog = component.querySelector('dialog');
        if (!dialog) {
          throw new Error('url did not return a dialog');
        }
        dialog.showModal();
        this.#triggeredSignalsStorage[signalData.id] = false;
      });
    } else {
      component.showModal();
      this.#triggeredSignalsStorage[signalData.id] = false;
    }
    this.#initializedModalboxes[signalData.id] = component.id;

    this.#maybeInitCarousel(component);
  }

  /**
   * @param {HTMLDialogElement} component
   */
  #maybeInitCarousel(component) {
    const container = component.querySelector('.carousel-inner');
    if (!container) {
      return;
    }

    const left = component.querySelector('.carousel-control.left');
    const right = component.querySelector('.carousel-control.right');
    const indicators = component.querySelectorAll('.carousel-indicators > li');

    left.addEventListener('click', () => this.#nextPage(component, -1));
    right.addEventListener('click', () => this.#nextPage(component, 1));
    indicators.forEach(
      (i) => i.addEventListener(
        'click',
        () => this.#gotoPage(component, parseInt(i.getAttribute('data-slide-to'))),
      ),
    );
  }

  #nextPage(component, direction) {
    const pages = component.querySelectorAll('.carousel-inner > div.item');
    let index = 0;
    let current = 0;
    pages.forEach((p) => {
      if (p.classList.contains('active')) {
        current = index;
      }
      index += 1;
    });
    let next = current + direction;
    if (next < 0) {
      next = index - 1;
    }
    if (next === index) {
      next = 0;
    }
    this.#gotoPage(component, next);
  }

  #gotoPage(component, number) {
    const pages = component.querySelectorAll('.carousel-inner > div.item');
    const indicators = component.querySelectorAll('.carousel-indicators > li');
    this.#setActiveInList(pages, number);
    this.#setActiveInList(indicators, number);

    const title = component.querySelector('h1.modal-title');
    pages.forEach((item) => {
      if (item.classList.contains('active')) {
        title.innerHTML = item.getAttribute('data-title');
      }
    });
  }

  #setActiveInList(list, number) {
    let index = 0;
    list.forEach((item) => {
      if (index !== number) {
        item.classList.remove('active');
      } else {
        item.classList.add('active');
      }
      index += 1;
    });
  }
}
