<?php namespace ILIAS\GlobalScreen\Scope\MetaBar\Provider;

use ILIAS\GlobalScreen\Provider\PluginProvider;
use ILIAS\GlobalScreen\Provider\PluginProviderHelper;

/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
/**
 * Interface AbstractStaticMetaBarPluginProvider
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
abstract class AbstractStaticMetaBarPluginProvider extends AbstractStaticMetaBarProvider implements StaticMetaBarProvider, PluginProvider
{
    use PluginProviderHelper;
}
