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

namespace ILIAS\Badge;

use ILIAS\DI\Container;
use Closure;
use ILIAS\UI\Component\Component;
use ilWACSignedPath;

class Modal
{
    /** @var Closure(string): string */
    private readonly Closure $sign_file;
    private ?ilBadgeImage $badge_image_service;

    public function __construct(
        private readonly Container $container,
        $sign_file = [ilWACSignedPath::class, 'signFile']
    ) {
        $this->sign_file = Closure::fromCallable($sign_file);
        $this->badge_image_service = new ilBadgeImage($container->resourceStorage(), $container->upload(), $container->ui()->mainTemplate());
    }

    /**
     * @return list<Component>
     */
    public function components(ModalContent $content): array
    {
        $modal_content = [];

        $image_src = $this->badge_image_service->getImageFromBadge($content->badge());

        $modal_content[] = $this->container->ui()->factory()->image()->responsive(
            ($this->sign_file)($image_src),
            $image_src
        );
        $modal_content[] = $this->container->ui()->factory()->divider()->horizontal();
        $modal_content[] = $this->item($content);

        return $modal_content;
    }

    private function item(ModalContent $content): Component
    {
        return $this->container
            ->ui()
            ->factory()
            ->item()
            ->standard($content->badge()->getTitle())
            ->withDescription($content->badge()->getDescription())
            ->withProperties($content->properties());
    }
}
