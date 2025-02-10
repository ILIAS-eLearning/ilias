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
 * - if no node is selected, enable all buttons
 * - if a node is selected, disable all other buttons
 *
 * @param {Map<string, TreeSelectNode>} nodeMap (node-id => node)
 * @param {Set<string>} nodeSelectionSet (node-ids)
 */
export default function toggleTreeSelectButtonsAvailability(nodeMap, nodeSelectionSet) {
  nodeMap.forEach((node, nodeId) => {
    if (nodeSelectionSet.size > 0) {
      node.selectButton.disabled = !nodeSelectionSet.has(nodeId);
      node.selectButton.querySelector('.glyph').classList.toggle('disabled', !nodeSelectionSet.has(nodeId));
    } else {
      node.selectButton.disabled = false;
      node.selectButton.querySelector('.glyph').classList.toggle('disabled', false);
    }
  });
}
