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
 */

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Input\Field\Node;

use ILIAS\UI\Implementation\Component\Input\Field\TreeMultiSelect;
use ILIAS\UI\Implementation\Component\Input\Field\TreeSelect;
use ILIAS\UI\Component\Input as C;

/**
 * This trait can be used to populate a method which retrieves Nodes from a
 * NodeRetrieval, without triggering unnecessary server/database roundtrips.
 *
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
trait NodeRetrievalCache
{
    /** @var array<string|int, C\Field\Node\Node|null> (node-id => node) */
    protected array $node_instance_cache = [];

    /**
     * Retrieves the given $node_id from the NodeRetrieval and caches it for
     * consecutive calls to this method (null will also be cached).
     */
    public function getNodeOnce(string|int $node_id): ?C\Field\Node\Node
    {
        if (isset($this->node_instance_cache[$node_id])) {
            return $this->node_instance_cache[$node_id];
        }

        $this->node_instance_cache[$node_id] = $this->getNodeRetrieval()->getNode($this->getNodeFactory(), $node_id);

        return $this->node_instance_cache[$node_id];
    }

    /** mirrors @see TreeSelect::getNodeRetrieval(), TreeMultiSelect::getNodeRetrieval() */
    abstract public function getNodeRetrieval(): C\Field\Node\NodeRetrieval;

    abstract protected function getNodeFactory(): Factory;
}
