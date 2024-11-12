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

namespace ILIAS\UI\Component\Navigation;

interface Factory
{
    /**
     * ---
     * description:
     *   purpose: >
     *      The Sequence Navigation is used to move through a series of elements
     *      in a particular order.
     *      Elements (or the amount of) may change during navigation, however,
     *      they will remain in a consecutive order.
     *   composition: >
     *      Sequence Navigation consists of several groups of buttons, mainly.
     *      First of all, there is the primary navigation of back- and next buttons
     *      to navigate through the parts of the sequence; a part is called a "Segment".
     *      While every Segment of a sequence is part of a bigger process (i.e.
     *      the perception of the entire sequence), there might be additional buttons
     *      for actions targeting outer context, or terminating the browsing
     *      of the sequence.
     *      Every shown segement may also add buttons with actions regarding the
     *      current segement.
     *      Finally, there may be view controls and filters to change the amount of
     *      and manner in which the segments are displayed,
     *   effect: >
     *      By pressing the forward and backward navigation buttons, users can
     *      switch between the segments of a sequence.
     *      Other buttons will trigger their respective actions.
     *      Operating view controls and filters will alter the sequence itself or
     *      the way its segments are rendered.
     *   rivals:
     *      pagination: >
     *          Paginations are to display a section of uniform data; segments may vary.
     *          Also, a pagination does not enforce linear browsing of its elements - sequences do.
     *
     * rules:
     *   usage:
     *     1: >
     *       Use a sequence when the order of presentation is crucial or the
     *       sequencial presentation aids focus. The sequence is not of an
     *       explorative nature but rather instructional.
     *   interaction:
     *     1: >
     *       Any actions included/provided by (parts of) a segment MUST NOT leave
     *       the context of the sequence.
     * ---
     * @return \ILIAS\UI\Component\Navigation\Sequence\Sequence
     */
    public function sequence(
        Sequence\Binding $binding
    ): Sequence\Sequence;
}
