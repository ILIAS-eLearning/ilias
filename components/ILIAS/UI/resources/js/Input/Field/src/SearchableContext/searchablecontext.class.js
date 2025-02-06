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
 * @author Ferdinand Engländer <ferdinand.englaender@concepts-and-training.de>
 */

/**
 * Searchable Select Component
 * JS features:
 *    - search bar input filters (hides) list items
 *    - button to clear the filter
 *    - expanding and collapsing the component (hiding and showing elements) with button triggers
 * SCSS features:
 *    - pushing checked items to the top of the list using flex-box order
 *    - component expanding animation
 *    - item switching position animation
 * @author Ferdinand Engländer <ferdinand.englaender@concepts-and-training.de>
 */
export default class SearchableInputContext {
  /**
   * @type {HTMLFieldSetElement}
   */
  inputFieldContext;

  /**
   * @type {HTMLInputElement}
   */
  searchbar;

  /**
   * @type {HTMLFieldSetElement}
   */
  selectionComponent;

  /**
   * @type {string}
   */
  listType;

  /**
   * @type {HTMLElement}
   */
  itemList;

  /**
   * @type {NodeList}
   */
  items;

  /**
   * @type {HTMLButtonElement}
   */
  engageDisengageToggle;

  /**
   * @type {HTMLSpanElement}
   */
  toggleExpandText;

  /**
   * @type {HTMLSpanElement}
   */
  toggleCollapseText;

  /**
   * @type {HTMLButtonElement}
   */
  clearSearchButton;

  /**
   * @type {HTMLDivElement}
   */
  scrollContainer;

  /**
   * @type {boolean}
   */
  #isFiltered;

  /**
   * @type {HTMLDivElement}
   */
  messageNoMatch;

  constructor(inputFieldContext) {
    /* DOM Elements */
    this.inputFieldContext = inputFieldContext;
    this.scrollContainer = this.inputFieldContext.querySelector('.c-input--searchable__field');
    this.searchbar = this.inputFieldContext.querySelector('.c-input--searchable__search-input input');
    this.listType = this.inputFieldContext.getAttribute('data-il-ui-component');
    this.itemList = this.inputFieldContext.querySelector('.c-field--searchable__list');
    this.items = this.itemList.querySelectorAll('.c-field--searchable__item');
    this.messageNoMatch = this.inputFieldContext.querySelector('.message-no-match');

    /* Buttons */
    this.clearSearchButton = this.inputFieldContext.querySelector('.c-input--searchable__clear-search');
    this.engageDisengageToggle = this.inputFieldContext.querySelector('.c-input--searchable__visibility-toggle');
    this.toggleExpandText = this.engageDisengageToggle.querySelector('.text-expand');
    this.toggleCollapseText = this.engageDisengageToggle.querySelector('.text-collapse');

    /* Initialize states */
    this.isEngaged = false; // will also set isFiltered false

    /* Event Listeners */
    this.filterItemsSearch = this.filterItemsSearch.bind(this);
    this.searchbar.addEventListener('input', this.filterItemsSearch);

    this.clearSearchButton.addEventListener('click', () => { this.isFiltered = false; });

    this.toggleVisibility = this.toggleVisibility.bind(this);
    this.engageDisengageToggle.addEventListener('click', this.toggleVisibility);

    if (this.listType === 'radio-field-input') {
      this.scrollListToTop = this.scrollListToTop.bind(this);
      this.items.forEach((item) => {
        item.addEventListener('change', this.scrollListToTop);
      });
    }
  }

  /**
   * Getter for isFiltered state
   * @returns {boolean}
   */
  get isFiltered() {
    return this.#isFiltered;
  }

  /**
   * Setter for isFiltered state
   * @param {boolean} value
   */
  set isFiltered(value) {
    if (this.#isFiltered === value) return;
    this.#isFiltered = value;
    if (value) {
      this.clearSearchButton.style.removeProperty('display');
    } else {
      this.searchbar.value = '';
      this.clearSearchButton.style.display = 'none';
      this.messageNoMatch.style.display = 'none';
      this.resetItemsDisplay();
    }
  }

  toggleVisibility() {
    if (this.isEngaged) {
      this.isEngaged = false;
      this.inputFieldContext.classList.remove('engaged');
      this.isFiltered = false;
      this.engageDisengageToggle.setAttribute('aria-expanded', 'false');
      this.toggleExpandText.style.removeProperty('display');
      this.toggleCollapseText.style.display = 'none';
    } else {
      this.isEngaged = true;
      this.inputFieldContext.classList.add('engaged');
      this.engageDisengageToggle.setAttribute('aria-expanded', 'true');
      this.toggleExpandText.style.display = 'none';
      this.toggleCollapseText.style.removeProperty('display');
    }
  }

  /**
   * Filter items based on search input
   * @param {Event} event
   */
  filterItemsSearch(event) {
    const value = event.target.value.toLowerCase();
    this.isFiltered = !!value; // negates any search term input to false then flips it to true

    let foundMatch = false;
    this.items.forEach((item) => {
      const itemText = item.textContent.toLowerCase();
      const isMatch = itemText.includes(value);
      if (isMatch) {
        foundMatch = true;
        showItem(item);
      } else {
        hideItem(item);
      }
    });
    if (value !== '' && foundMatch === false) {
      this.messageNoMatch.style.removeProperty('display');
    } else if (value === '' || foundMatch) {
      this.messageNoMatch.style.display = 'none';
    }
  }

  /**
   * Reset the display of all items
   */
  resetItemsDisplay() {
    this.items.forEach((item) => showItem(item));
  }

  scrollListToTop() {
    this.scrollContainer.scrollTo({
      top: 0,
      behavior: 'smooth',
    });
  }
}

/**
 * Show a specific item
 * @param {HTMLElement} item
 */
function showItem(item) {
  item.style.removeProperty('display');
}

/**
 * Hide a specific item
 * @param {HTMLElement} item
 */
function hideItem(item) {
  item.style.display = 'none';
}
