<?php

namespace ILIAS\UI\Component\Toast;

use Closure;
use ILIAS\UI\Component\Button\Shy;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Link\Link;
use ILIAS\UI\Component\Symbol\Icon\Icon;
use ILIAS\UI\Implementation\Component\Signal;

/**
 * Interface Toast
 * @package ILIAS\UI\Component\Toast
 */
interface Toast extends Component
{
    /**
     * Gets the title of the toast
     *
     * @return string|Shy|Link
     */
    public function getTitle();

    /**
     * Create a copy of this toast with an attached description.
     */
    public function withDescription(string $description) : Toast;

    /**
     * Get the description of the toast.
     */
    public function getDescription() : string;

    /**
     * Create a copy of this toast with a set of actions to perform on it.
     */
    public function withActions(Link $actions) : Toast;

    /**
     * Get the actions of the toast.
     */
    public function getActions() : Link;

    /**
     * Create a copy of this toast with an url, which is called when the item title is clicked.
     */
    public function withTitleAction(string|Signal|Closure $url) : Toast;

    /**
     * Get the url, which is called when the user clicks the item title.
     */
    public function getTitleAction() : ?string;

    /**
     * Create a copy of this toast with an url, which is called asynchronous when the item vanishes.
     * This action will not trigger if the vanishing is provoked by the user by interacting with the toast.
     */
    public function withVanishAction(string|Signal|Closure $url) : Toast;

    /**
     * Get the url, which is called when the item vanishes without user interaction.
     */
    public function getVanishAction() : ?string;

    /**
     * Get icon.
     */
    public function getIcon() : Icon;
}
