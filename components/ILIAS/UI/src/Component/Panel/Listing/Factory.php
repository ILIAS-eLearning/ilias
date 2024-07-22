<?php

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

declare(strict_types=1);

namespace ILIAS\UI\Component\Panel\Listing;

use ILIAS\UI\Component\Item\Group;

/**
 * Interface Factory
 * @package ILIAS\UI\Component\Panel\Listing
 */
interface Factory
{
    /**
     * ---
     * description:
     *   purpose: >
     *       Standard item lists present lists of items with similar presentation.
     *       All items are passed by using Item Groups.
     *   composition: >
     *      This Listing is composed of title and a set of Item Groups. The set of Item Groups can optionally
     *      be made collapsible. Additionally an optional dropdown to select the number/types of items
     *      to be shown at the top of the Listing.
     *
     * rules:
     *   interaction:
     *      1: >
     *         Standard Listing Panels MAY be expandable to make the Item Groups collapsible by clicking on the title area.
     *         Standard Panels MAY also get asynchronous expand and collapse actions to e.g. store the expanded status
     *         of the Component in the session.
     * ---
     * @param string $title Title of the Listing
     * @param \ILIAS\UI\Component\Item\Group[] $item_groups Item groups
     * @return \ILIAS\UI\Component\Panel\Listing\Standard
     */
    public function standard(string $title, array $item_groups): Standard;
}
