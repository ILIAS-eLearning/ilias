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

/**
 * Updates the TreeSelectNode.selectButton state in the following manner:
 * - if one or more node is selected, disable their descendant buttons and enable all others
 * - if no node is selected, enable all buttons
 *
 * @param {Map<string, TreeSelectNode>} nodeMap (node-id => node)
 * @param {Set<string>} nodeSelectionSet (node-ids)
 */
export default function toggleTreeMultiSelectButtonsAvailability(nodeMap, nodeSelectionSet) {
  nodeMap.forEach((node) => {
    node.selectButton.disabled = false;
    node.selectButton.querySelector('.glyph').classList.remove('disabled');
  });
  nodeSelectionSet.forEach((nodeId) => {
    const node = nodeMap.get(nodeId);
    if (node === null) {
      throw new Error(`Could not toggle availability of node '${nodeId}'.`);
    }
    // ignore leaf nodes
    if (node.listElement === null) {
      return;
    }
    // disable all descending select buttons
    node.listElement
      .querySelectorAll('.c-input-node__select')
      .forEach((button) => {
        button.disabled = true;
        button.querySelector('.glyph').classList.add('disabled');
      });
  });
}
