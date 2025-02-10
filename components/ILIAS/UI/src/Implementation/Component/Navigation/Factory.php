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

namespace ILIAS\UI\Implementation\Component\Navigation;

use ILIAS\UI\Component\Navigation as INavigation;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\Factory as Refinery;

class Factory implements INavigation\Factory
{
    public function __construct(
        protected DataFactory $data_factory,
        protected Refinery $refinery,
        protected \ArrayAccess $storage,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function sequence(
        INavigation\Sequence\Binding $binding
    ): INavigation\Sequence\Sequence {
        return new Sequence\Sequence(
            $this->data_factory,
            $this->refinery,
            $this->storage,
            $binding
        );
    }

}
