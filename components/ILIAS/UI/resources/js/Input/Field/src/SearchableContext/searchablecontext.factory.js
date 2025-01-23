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

import SearchableInputContext from './searchablecontext.class.js';

export default class SearchableInputContextFactory {
  /**
     * @type {Array<string, SearchableInputContext>}
     */
  instances = [];

  /**
     * @param {HTMLElement} searchableField
     * @return {void}
     * @throws {Error} if the input was already initialized.
     */
  init(searchableField) {
    if (undefined !== this.instances[searchableField.id]) {
      throw new Error(`A searchable field with input-id '${searchableField.id}' has already been initialized.`);
    }
    this.instances[searchableField.id] = new SearchableInputContext(searchableField);
  }

  /**
     * @param {string} inputID
     * @return {SearchableInputContext|null}
     */
  get(inputID) {
    return this.instances[inputID] ?? null;
  }
}
