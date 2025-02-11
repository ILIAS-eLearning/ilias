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
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */

import TreeSelectNode from './TreeSelectNode.js';

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {string}
 * @throws {Error} if the data-node-id attribute is not found.
 */
function getNodeId(nodeElement) {
  const nodeId = nodeElement.getAttribute('data-node-id');
  if (nodeId === null) {
    throw new Error('Could not find data-node-id attribute.');
  }
  return nodeId;
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {string}
 * @throws {Error} if the data-node-id attribute is not found.
 */
function getNodeName(nodeElement) {
  const nodeName = nodeElement.querySelector('[data-node-name]');
  if (nodeName === null) {
    throw new Error('Could not find element with data-node-name attribute.');
  }
  return nodeName.textContent;
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {HTMLButtonElement}
 * @throws {Error} if the select button cannot be found.
 */
function getSelectButton(nodeElement) {
  const selectButton = nodeElement.querySelector(':scope > .c-input-node__select');
  if (selectButton === null) {
    throw new Error('Could not find node select button.');
  }
  return selectButton;
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {HTMLButtonElement}
 * @throws {Error} if the drilldown level cannot be found
 */
function getNodeDrilldownParentLevel(nodeElement) {
  const parentDrilldownMenu = nodeElement.closest('ul[data-ddindex]');
  if (parentDrilldownMenu === null) {
    throw new Error('Could not find drilldown menu of node.');
  }
  return parentDrilldownMenu.getAttribute('data-ddindex');
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {HTMLButtonElement|null}
 * @throws {Error} if button cannot be found
 */
function getNodeDrilldownButton(nodeElement) {
  if (!isBranchNode(nodeElement)) {
    return null;
  }
  const drilldownButton = nodeElement.querySelector('.c-drilldown__menulevel--trigger');
  if (drilldownButton === null) {
    throw new Error('Could not find drilldown menu button of branch node.');
  }
  return drilldownButton;
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {HTMLUListElement|null}
 */
function getNodeListElementButton(nodeElement) {
  if (!isBranchNode(nodeElement)) {
    return null;
  }
  const listElement = nodeElement.querySelector('ul');
  if (listElement === null) {
    throw new Error('Could not find list element of branch node.');
  }
  return listElement;
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {string|null}
 */
function getNodeAsyncUrl(nodeElement) {
  if (isAsyncNode(nodeElement) && nodeElement.hasAttribute('data-render-url')) {
    return nodeElement.getAttribute('data-render-url');
  }
  return null;
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {boolean}
 */
function isBranchNode(nodeElement) {
  return !nodeElement.classList.contains('c-input-node__leaf')
    && nodeElement.classList.contains('c-input-node');
}

/**
 * @param {HTMLLIElement} nodeElement
 * @returns {boolean}
 */
function isAsyncNode(nodeElement) {
  return nodeElement.classList.contains('c-input-node__async');
}

/**
 * @param {HTMLLIElement[]} nodeElements
 * @param {Map<string, TreeSelectNode>|null} existingNodeMap (node-id => node)
 * @returns {Map<string, TreeSelectNode>} (node-id => node)
 * @throws {Error} if a node cannot be parsed, or already is.
 */
export default function createTreeSelectNodes(nodeElements, existingNodeMap = null) {
  return nodeElements.reduce(
    (nodes, nodeElement) => {
      const nodeId = getNodeId(nodeElement);
      if (nodes.has(nodeId)) {
        throw new Error(`Node '${nodeId}' has already been parsed. There might be a rendering issue.`);
      }
      return nodes.set(nodeId, new TreeSelectNode(
        nodeId,
        getNodeName(nodeElement),
        nodeElement,
        getSelectButton(nodeElement),
        getNodeDrilldownParentLevel(nodeElement),
        getNodeDrilldownButton(nodeElement),
        getNodeListElementButton(nodeElement),
        getNodeAsyncUrl(nodeElement),
      ));
    },
    new Map(existingNodeMap ?? []),
  );
}
