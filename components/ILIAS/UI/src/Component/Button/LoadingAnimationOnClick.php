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

namespace ILIAS\UI\Component\Button;

/**
 * Interface for buttons with loading animation on click
 *
 * @author	killing@leifos.de
 */
interface LoadingAnimationOnClick
{
    /**
     * If clicked the button will display a spinner
     * wheel to show that a request is being processed
     * in the background.
     *
     * @return static
     */
    public function withLoadingAnimationOnClick(bool $loading_animation_on_click);

    /**
     * Return whether loading animation has been activated
     */
    public function hasLoadingAnimationOnClick(): bool;
}
