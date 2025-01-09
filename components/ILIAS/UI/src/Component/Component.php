<?php

declare(strict_types=1);

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

namespace ILIAS\UI\Component;

/**
 * A component is the most general form of an entity in the UI. Every entity
 * is a component.
 */
interface Component
{
    /**
     * Get the canonical name of the component.
     */
    public function getCanonicalName(): string;

    /**
     * This implements the catamorphism (https://en.wikipedia.org/wiki/Catamorphism)
     * for components, which is a clever way to implement a generalized fold over
     * data structures.
     *
     * The scheme starts at the leaves of the structure and applys the function to
     * each leave and moves up the tree recursively. The return value of the function
     * is put into the "sub structure" to be consumed when the function is applied
     * to the upper levels. By using this method, the structure can be broken down
     * completely or it can be modified.
     */
    public function foldWith(callable $f): mixed;

    /**
     * This contains the sub structure of the component to support `foldWith`. For
     * pristine Components, it shall return all Components that are contained in
     * the component. When applying `foldWith` it will contain the results of the
     * function for these sub components. A component might contain no substructure
     * whatsoever, hence this might return null;
     *
     * Implementations of Component shall simply pass back their sub components,
     * and, most probably, use the implementation of foldWith from the trait
     * ComponentHelper and overwrite "getSubComponents" according to their requirements.
     *
     * @return ?array<mixed>
     */
    public function getSubStructure(): ?array;
}
