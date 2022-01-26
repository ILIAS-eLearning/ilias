<?php declare(strict_types=1);

/* Copyright (c) 2019 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

use ILIAS\Setup;
use ILIAS\DI;

class ilDatabaseUpdatedObjective implements Setup\Objective
{
    public function getHash() : string
    {
        return hash("sha256", self::class);
    }

    public function getLabel() : string
    {
        return "The database is updated.";
    }

    public function isNotable() : bool
    {
        return true;
    }

    /**
     * @return \ilDatabaseInitializedObjective[]|\ILIAS\Setup\Objective\ClientIdReadObjective[]|\ilIniFilesPopulatedObjective[]
     */
    public function getPreconditions(Setup\Environment $environment) : array
    {
        return [
            new Setup\Objective\ClientIdReadObjective(),
            new ilIniFilesPopulatedObjective(),
            new \ilDatabaseInitializedObjective()
        ];
    }

    public function achieve(Setup\Environment $environment) : Setup\Environment
    {
        $db = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $io = $environment->getResource(Setup\Environment::RESOURCE_ADMIN_INTERACTION);
        $ini = $environment->getResource(Setup\Environment::RESOURCE_ILIAS_INI);
        $client_ini = $environment->getResource(Setup\Environment::RESOURCE_CLIENT_INI);
        $client_id = $environment->getResource(Setup\Environment::RESOURCE_CLIENT_ID);

        // ATTENTION: This is a total abomination. It only exists to allow the db-
        // update to run. This is a memento to the fact, that dependency injection
        // is something we want. Currently, every component could just service
        // locate the whole world via the global $DIC.
        /** @noRector  */
        $DIC = $GLOBALS["DIC"] ?? [];
        $GLOBALS["DIC"] = new DI\Container();
        $GLOBALS["DIC"]["ilDB"] = $db;
        $GLOBALS["ilDB"] = $db;
        $GLOBALS["DIC"]["ilBench"] = null;
        $GLOBALS["DIC"]["ilLog"] = new class($io) extends ilLog {
            public function __construct($io)
            {
                $this->io = $io;
            }
            public function write($a_msg, $a_log_level = null): void
            {
            }
            public function info(): void
            {
            }
            public function warning($msg): void
            {
                $this->io->inform($msg);
            }
            public function error($msg): void
            {
                throw new Setup\UnachievableException(
                    "Problem in DB-Update: $msg"
                );
            }
        };
        $GLOBALS["ilLog"] = $GLOBALS["DIC"]["ilLog"];
        $GLOBALS["ilCtrlStructureReader"] = new class() {
            public function getStructure(): void
            {
            }
            public function setIniFile(): void
            {
            }
        };
        if (!defined("CLIENT_DATA_DIR")) {
            define("CLIENT_DATA_DIR", $ini->readVariable("clients", "datadir") . "/" . $client_id);
        }
        if (!defined("CLIENT_WEB_DIR")) {
            define("CLIENT_WEB_DIR", dirname(__DIR__, 4) . "/data/" . $client_id);
        }
        if (!defined("ILIAS_ABSOLUTE_PATH")) {
            define("ILIAS_ABSOLUTE_PATH", dirname(__FILE__, 5));
        }
        if (!defined("ILIAS_LOG_ENABLED")) {
            define("ILIAS_LOG_ENABLED", false);
        }
        if (!defined("ROOT_FOLDER_ID")) {
            define("ROOT_FOLDER_ID", (int) $client_ini->readVariable("system", "ROOT_FOLDER_ID"));
        }
        if (!defined("ROLE_FOLDER_ID")) {
            define("ROLE_FOLDER_ID", (int) $client_ini->readVariable("system", "ROLE_FOLDER_ID"));
        }
        if (!defined("SYSTEM_FOLDER_ID")) {
            define("SYSTEM_FOLDER_ID", (int) $client_ini->readVariable("system", "SYSTEM_FOLDER_ID"));
        }

        $db_update = new ilDBUpdate($db);

        $db_update->applyUpdate();
        $db_update->applyHotfix();
        $db_update->applyCustomUpdates();

        return $environment;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Setup\Environment $environment) : bool
    {
        return true;
    }
}
