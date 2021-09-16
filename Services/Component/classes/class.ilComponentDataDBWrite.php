<?php declare(strict_types=1);

use ILIAS\Data\Version;

/**
 * Writeable part of repository interface to ilComponenDataDB.
 */
interface ilComponentDataDBWrite extends ilComponentDataDB
{
    public function setCurrentPluginVersion(string $plugin_id, Version $version, int $db_version);

    public function setActivation(string $plugin_id, bool $activated);
    public function removeStateInformationOf(string $plugin_id);
}
