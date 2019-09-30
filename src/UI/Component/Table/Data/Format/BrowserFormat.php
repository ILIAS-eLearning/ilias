<?php

declare(strict_types=1);

namespace ILIAS\UI\Component\Table\Data\Format;

use ILIAS\UI\Component\Table\Data\Table;
use ILIAS\UI\Component\Table\Data\Settings\Settings;

/**
 * Interface BrowserFormat
 *
 * @package ILIAS\UI\Component\Table\Data\Format
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
interface BrowserFormat extends Format
{

    /**
     * @param Table $component
     *
     * @return string|null
     */
    public function getInputFormatId(Table $component) : ?string;


    /**
     * @param Table    $component
     * @param Settings $settings
     *
     * @return Settings
     */
    public function handleSettingsInput(Table $component, Settings $settings) : Settings;
}
