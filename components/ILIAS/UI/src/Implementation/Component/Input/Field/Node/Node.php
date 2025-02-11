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

use ILIAS\UI\Implementation\Component\ComponentHelper;
use ILIAS\UI\Component as C;

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class Node implements C\Input\Field\Node\Node
{
    use ComponentHelper;

    public function __construct(
        protected string|int $id,
        protected string $name,
        protected ?C\Symbol\Icon\Icon $icon,
        protected array $children,
    ) {
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): ?C\Symbol\Icon\Icon
    {
        return $this->icon;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}
