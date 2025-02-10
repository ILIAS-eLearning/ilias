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

import il from 'ilias';
import createTreeSelectNodes from './createTreeSelectNodes.js';

/**
 * @param {HTMLElement} element
 * @param {string} selector
 * @param {number} [limit=255]
 * @returns {HTMLElement[]}
 */
function querySelectorParents(element, selector, limit = 255) {
  const result = [];
  let current = element;
  for (let count = 0; count < limit; count += 1) {
    const match = current.closest(selector);
    if (!match || !match.parentElement) {
      break;
    }
    current = match.parentElement;
    result.push(match);
  }
  return result.reverse();
}

/**
 * @param {HTMLElement} element
 * @returns {string}
 * @throws {Error} if no data-node-id attribute exists.
 */
function getNodeIdOrAbort(element) {
  const nodeId = element.getAttribute('data-node-id');
  if (nodeId === null) {
    throw new Error("Could not find 'data-node-id' attribbute of element.");
  }
  return nodeId;
}

/**
 * This function will be used to iterate arrays instead of Array.forEach(),
 * during async processing.
 * @param {Array} array
 * @param {function(*, number)} callback
 */
function walkArray(array, callback) {
  for (let index = 0; index < array.length; index += 1) {
    callback(array[index], index);
  }
}

/**
 * Returns a Map with all VALUES of larger which are not contained in smaller.
 *
 * @param {Map} larger
 * @param {Map} smaller
 * @returns {Array}
 */
function getMapDifference(larger, smaller) {
  return Array
    .from(larger.entries())
    .filter(([key]) => !smaller.has(key))
    .map(([, value]) => value);
}

/**
 * @param {HTMLLIElement} nodeElement
 * @param {boolean} selected
 */
function toggleSelectedNodeElementClass(nodeElement, selected) {
  nodeElement.classList.toggle('c-input-node--selected', selected);
}

/**
 * @param {HTMLElement} element
 * @returns {HTMLLIElement[]}
 */
function getNodeElements(element) {
  return Array.from(element.querySelectorAll('.c-input-node'));
}

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
export default class TreeSelect {
  /** @type {Set<string>} (node-ids) */
  #nodeSelectionSet = new Set();

  /** @type {Set<string>} (async-node-ids) */
  #finishedRendering = new Set();

  /** @type {Set<string>} (async-node-ids) */
  #renderingQueue = new Set();

  /** @type {TemplateRenderer} */
  #templateRenderer;

  /** @type {AsyncRenderer} */
  #asyncRenderer;

  /** @type {{txt: function(string): string}} */
  #language;

  /** @type {Drilldown} */
  #drilldownComponent;

  /** @type {HTMLElement} */
  #breadcrumbsElement;

  /** @type {HTMLTemplateElement} */
  #breadcrumbTemplate;

  /** @type {HTMLUListElement} */
  #nodeSelectionElement;

  /** @type {HTMLTemplateElement} */
  #nodeSelectionTemplate;

  /** @type {HTMLButtonElement} */
  #dialogSelectButton;

  /** @type {HTMLButtonElement} */
  #dialogOpenButton;

  /** @type {HTMLDialogElement} */
  #dialogElement;

  /** @type {function(Map<string, TreeSelectNode>, Set<string>)} */
  #toggleNodeSelectButtonsAvailability;

  /** @type {Map<string, TreeSelectNode>} (node-id => node) */
  #nodeMap;

  /**
   * @param {JQueryEventListener} jqueryEventListener
   * @param {TemplateRenderer} templateRenderer
   * @param {AsyncRenderer} asyncRenderer
   * @param {{txt: function(string): string}} language
   * @param {Drilldown} drilldownComponent
   * @param {HTMLElement} breadcrumbsElement
   * @param {HTMLTemplateElement} breadcrumbTemplate
   * @param {HTMLUListElement} nodeSelectionElement
   * @param {HTMLTemplateElement} nodeSelectionTemplate
   * @param {HTMLButtonElement} dialogSelectButton
   * @param {HTMLButtonElement} dialogOpenButton
   * @param {HTMLDialogElement} dialogElement
   * @param {function(Map<string, TreeSelectNode>, Set<string>)} toggleNodeSelectButtonsAvailability
   */
  constructor(
    jqueryEventListener,
    templateRenderer,
    asyncRenderer,
    language,
    drilldownComponent,
    breadcrumbsElement,
    breadcrumbTemplate,
    nodeSelectionElement,
    nodeSelectionTemplate,
    dialogSelectButton,
    dialogOpenButton,
    dialogElement,
    toggleNodeSelectButtonsAvailability,
  ) {
    this.#templateRenderer = templateRenderer;
    this.#asyncRenderer = asyncRenderer;
    this.#language = language;
    this.#drilldownComponent = drilldownComponent;
    this.#breadcrumbsElement = breadcrumbsElement;
    this.#breadcrumbTemplate = breadcrumbTemplate;
    this.#nodeSelectionElement = nodeSelectionElement;
    this.#nodeSelectionTemplate = nodeSelectionTemplate;
    this.#dialogSelectButton = dialogSelectButton;
    this.#dialogOpenButton = dialogOpenButton;
    this.#dialogElement = dialogElement;
    this.#toggleNodeSelectButtonsAvailability = toggleNodeSelectButtonsAvailability;

    // parse tree select nodes first in case of failure.
    this.#nodeMap = createTreeSelectNodes(getNodeElements(dialogElement));

    jqueryEventListener.on(
      this.#dialogElement.ownerDocument,
      this.#drilldownComponent.getBackSignal(),
      () => {
        this.#removeLastBreadcrumb();
      },
    );
    this.#dialogElement
      .querySelectorAll('[data-action="close"]')
      .forEach((button) => {
        button.addEventListener('click', () => {
          this.#closeDialog();
        });
      });
    this.#nodeSelectionElement
      .querySelectorAll('li')
      .forEach((entry) => {
        const nodeId = getNodeIdOrAbort(entry);
        this.#addRemoveNodeSelectionEntryClickHandler(entry, nodeId);
        this.#selectNode(nodeId);
      });
    this.#drilldownComponent.addEngageListener((drilldownLevel) => {
      this.#engageDrilldownLevelHandler(drilldownLevel);
    });
    this.#dialogOpenButton.addEventListener('click', () => {
      this.#openDialog();
    });
    this.#nodeMap.forEach((node) => {
      this.#hydrateNode(node);
    });

    this.#updateDialogSelectButton();
  }

  /**
   * Fetches child nodes from the given async node render URL and hydrates them.
   * This function will only fetch children once, and once at the same time.
   *
   * @param {string} asyncNodeId
   * @param {string} renderUrl
   * @param {HTMLUListElement} asyncNodeList
   * @returns {Promise<void>}
   */
  async #renderAsyncNodeChildren(asyncNode) {
    // only render the an async node once, and once at the same time.
    if (this.#finishedRendering.has(asyncNode.id) || this.#renderingQueue.has(asyncNode.id)) {
      return;
    }
    try {
      this.#renderingQueue.add(asyncNode.id);
      const childNodeElements = await this.#asyncRenderer.loadContent(asyncNode.renderUrl);
      asyncNode.listElement.append(...childNodeElements.children);
      this.#drilldownComponent.parseLevels();

      const updatedNodeMap = createTreeSelectNodes(
        getNodeElements(asyncNode.listElement),
        this.#nodeMap,
      );
      const addedNodes = getMapDifference(updatedNodeMap, this.#nodeMap);
      this.#nodeMap = updatedNodeMap;

      walkArray(addedNodes, (childNode) => {
        if (this.#nodeSelectionSet.has(childNode.id)) {
          this.#performSelect(childNode.id);
        } else {
          this.#performUnselect(childNode.id);
        }
        this.#hydrateNode(childNode);
      });
      this.#finishedRendering.add(asyncNode.id);
    } catch (error) {
      throw new Error(`Could not render async node children: ${error.message}`);
    } finally {
      this.#renderingQueue.delete(asyncNode.id);
    }
  }

  /**
   * @param {TreeSelectNode} node
   */
  #renderAllBreadcrumbs(node) {
    this.#removeAllBreadcrumbs();
    walkArray(querySelectorParents(node.element, '.c-input-node'), (parentNodeElement) => {
      const parentNodeId = parentNodeElement.getAttribute('data-node-id');
      if (parentNodeId === null || !this.#nodeMap.has(parentNodeId)) {
        throw new Error("Could not find 'data-node-id' of node element.");
      }
      const parentNode = this.#nodeMap.get(parentNodeId);
      this.#renderBreadcrumb(parentNode);
    });
  }

  /**
   * @param {string} drilldownLevel
   * @param {HTMLButtonElement} drilldownButton
   * @param {string} nodeName
   */
  #renderBreadcrumb(node) {
    const breadcrumb = this.#templateRenderer
      .createContent(this.#breadcrumbTemplate)
      .querySelector('.crumb');

    breadcrumb.setAttribute('data-ddindex', node.drilldownParentLevel);
    breadcrumb.firstElementChild.textContent = node.name;

    breadcrumb.addEventListener('click', () => {
      this.#drilldownComponent.engageLevel(node.drilldownParentLevel);
      node.drilldownButton.click();
    });

    this.#breadcrumbsElement.append(breadcrumb);
  }

  #removeLastBreadcrumb() {
    const breadcrumbs = this.#breadcrumbsElement.querySelectorAll('.crumb');
    breadcrumbs.item(breadcrumbs.length - 1)?.remove();
  }

  #removeAllBreadcrumbs() {
    walkArray(this.#breadcrumbsElement.querySelectorAll('.crumb'), (breadcrumb) => {
      breadcrumb.remove();
    });
  }

  /**
   * @param {string} drilldownLevel
   */
  #engageDrilldownLevelHandler(drilldownLevel) {
    // it should not be a string, this will definitely break here sometime.
    if (drilldownLevel === '0') {
      this.#removeAllBreadcrumbs();
      return;
    }
    const engagedNodeId = this.#dialogElement
      .querySelector(`ul[data-ddindex="${drilldownLevel}"]`)
      ?.closest('.c-input-node')
      ?.getAttribute('data-node-id');
    if (engagedNodeId === null || !this.#nodeMap.has(engagedNodeId)) {
      throw new Error(`Could not find node for drilldown-level '${drilldownLevel}'.`);
    }
    this.#renderAllBreadcrumbs(this.#nodeMap.get(engagedNodeId));
  }

  /**
   * @param {HTMLButtonElement} button
   * @param {TreeSelectNode} node
   */
  #addNodeDrilldownButtonClickHandler(button, node) {
    button.addEventListener('click', () => {
      if (node.renderUrl !== null) {
        this.#renderAsyncNodeChildren(node);
      }
    });
  }

  /**
   * @param {HTMLLIElement} nodeSelectionEntry
   * @param {string} nodeId
   */
  #addRemoveNodeSelectionEntryClickHandler(nodeSelectionEntry, nodeId) {
    nodeSelectionEntry
      .querySelector('[data-action="remove"]')
      ?.addEventListener('click', () => {
        this.#performUnselect(nodeId);
        nodeSelectionEntry.remove();
      });
  }

  /**
   * @param {HTMLButtonElement} button
   * @param {TreeSelectNode} node
   */
  #addNodeSelectButtonClickHandler(button, node) {
    button.addEventListener('click', () => {
      if (this.#nodeSelectionSet.has(node.id)) {
        this.#performUnselect(node.id);
      } else {
        this.#performSelect(node.id);
      }
    });
  }

  /**
   * @param {TreeSelectNode} node
   */
  #renderNodeSelectionEntry(node) {
    if (this.#nodeSelectionElement.querySelector(`li[data-node-id="${node.id}"]`) !== null) {
      return;
    }
    const nodeSelectionEntry = this.#templateRenderer.createContent(this.#nodeSelectionTemplate);
    const listElement = nodeSelectionEntry.querySelector('[data-node-id]');

    listElement.setAttribute('data-node-id', node.id);
    listElement.querySelector('[data-node-name]').textContent = node.name;
    listElement.querySelector('input').value = node.id;

    this.#addRemoveNodeSelectionEntryClickHandler(listElement, node.id);

    this.#nodeSelectionElement.append(...nodeSelectionEntry.children);
  }

  /**
   * @param {string} nodeId
   */
  #removeNodeSelectionEntry(nodeId) {
    this.#nodeSelectionElement.querySelector(`li[data-node-id="${nodeId}"]`)?.remove();
  }

  /**
   * @param {string} nodeId
   */
  #performUnselect(nodeId) {
    this.#unselectNode(nodeId);
    this.#updateDialogSelectButton();
    this.#removeNodeSelectionEntry(nodeId);
    // in case the node was not yet loaded (async).
    if (this.#nodeMap.has(nodeId)) {
      const node = this.#nodeMap.get(nodeId);
      toggleSelectedNodeElementClass(node.element, false);
      this.#changeNodeSelectButtonToSelect(node.selectButton);
      this.#toggleNodeSelectButtonsAvailability(this.#nodeMap, this.#nodeSelectionSet);
    }
  }

  /**
   * @param {string} nodeId
   */
  #performSelect(nodeId) {
    this.#selectNode(nodeId);
    this.#updateDialogSelectButton();
    // in case the node was not yet loaded (async).
    if (this.#nodeMap.has(nodeId)) {
      const node = this.#nodeMap.get(nodeId);
      toggleSelectedNodeElementClass(node.element, true);
      this.#changeNodeSelectButtonToUnselect(node.selectButton);
      this.#toggleNodeSelectButtonsAvailability(this.#nodeMap, this.#nodeSelectionSet);
      this.#renderNodeSelectionEntry(node);
    }
  }

  /**
   * @param {TreeSelectNode} node
   */
  #hydrateNode(node) {
    this.#addNodeSelectButtonClickHandler(node.selectButton, node);
    if (node.drilldownButton !== null) {
      this.#addNodeDrilldownButtonClickHandler(node.drilldownButton, node);
    }
  }

  /**
   * @param {HTMLButtonElement} button
   */
  #changeNodeSelectButtonToSelect(button) {
    button.querySelector('[data-action="remove"]')?.classList.add('hidden');
    button.querySelector('[data-action="select"]')?.classList.remove('hidden');
    button.setAttribute('aria-label', this.#translate('select_node'));
  }

  /**
   * @param {HTMLButtonElement} button
   */
  #changeNodeSelectButtonToUnselect(button) {
    button.querySelector('[data-action="select"]')?.classList.add('hidden');
    button.querySelector('[data-action="remove"]')?.classList.remove('hidden');
    button.setAttribute('aria-label', this.#translate('unselect_node'));
  }

  #updateDialogSelectButton() {
    this.#dialogSelectButton.disabled = (this.#nodeSelectionSet.size <= 0);
  }

  /**
   * @param {string} node
   */
  #unselectNode(nodeId) {
    if (this.#nodeSelectionSet.has(nodeId)) {
      this.#nodeSelectionSet.delete(nodeId);
    }
  }

  /**
   * @param {string} nodeId
   */
  #selectNode(nodeId) {
    if (!this.#nodeSelectionSet.has(nodeId)) {
      this.#nodeSelectionSet.add(nodeId);
    }
  }

  /**
   * Workaround due to the order of scripts / initialisation code.
   * @param {string} variable
   * @returns {string}
   */
  #translate(variable) {
    return this.#language?.txt(variable) ?? il.Language.txt(variable);
  }

  #closeDialog() {
    this.#dialogElement.close();
  }

  #openDialog() {
    this.#dialogElement.showModal();
  }
}
