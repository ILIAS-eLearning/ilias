<?php
/* Copyright (c) 2020 Daniel Weise <daniel.weise@concepts-and-training.de> Extended GPL, see docs/LICENSE */

declare(strict_types=1);

use ILIAS\Setup;
use ILIAS\DI;

class ilComponentActivatePluginsObjective implements Setup\Objective
{
    /**
     * @var string
     */
    protected $plugin_name;

    public function __construct(string $plugin_name)
    {
        $this->plugin_name = $plugin_name;
    }

    /**
     * @inheritdoc
     */
    public function getHash() : string
    {
        return hash("sha256", self::class . $this->plugin_name);
    }

    /**
     * @inheritdoc
     */
    public function getLabel() : string
    {
        return "Activate plugin $this->plugin_name.";
    }

    /**
     * @inheritdoc
     */
    public function isNotable() : bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getPreconditions(Setup\Environment $environment) : array
    {
        return [
            new \ilIniFilesLoadedObjective(),
            new \ilDatabaseInitializedObjective(),
            new \ilComponentPluginAdminInitObjective(),
            new \ilComponentDatabaseExistsObjective(),
            new \ilComponentFactoryExistsObjective()
        ];
    }

    /**
     * @inheritdoc
     */
    public function achieve(Setup\Environment $environment) : Setup\Environment
    {
        $component_repository = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $component_factory = $environment->getResource(Setup\Environment::RESOURCE_COMPONENT_FACTORY);
        $info = $component_repository->getPluginByName($this->plugin_name);

        if (!$info->supportsCLISetup()) {
            throw new \RuntimeException(
                "Plugin $this->plugin_name does not support command line setup."
            );
        }

        if (!$info->isActivationPossible()) {
            throw new \RuntimeException(
                "Plugin $this->plugin_name can not be activated."
            );
        }

        $ORIG_DIC = $this->initEnvironment($environment);
        $plugin = $component_factory->getPlugin($info->getId());
        $plugin->activate();
        $GLOBALS["DIC"] = $ORIG_DIC;

        return $environment;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Setup\Environment $environment) : bool
    {
        $component_repository = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $plugin = $component_repository->getPluginByName($this->plugin_name);

        return $plugin->isActivationPossible($environment);
    }

    protected function initEnvironment(Setup\Environment $environment) : ?ILIAS\DI\Container
    {
        $db = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $plugin_admin = $environment->getResource(Setup\Environment::RESOURCE_PLUGIN_ADMIN);
        $ini = $environment->getResource(Setup\Environment::RESOURCE_ILIAS_INI);
        $client_ini = $environment->getResource(Setup\Environment::RESOURCE_CLIENT_INI);


        // ATTENTION: This is a total abomination. It only exists to allow various
        // sub components of the various readers to run. This is a memento to the
        // fact, that dependency injection is something we want. Currently, every
        // component could just service locate the whole world via the global $DIC.
        $DIC = $GLOBALS["DIC"];
        $GLOBALS["DIC"] = new DI\Container();
        $GLOBALS["DIC"]["ilDB"] = $db;
        $GLOBALS["DIC"]["ilIliasIniFile"] = $ini;
        $GLOBALS["DIC"]["ilClientIniFile"] = $client_ini;
        $GLOBALS["DIC"]["ilLog"] = new class() implements ilLoggerInterface {
            public function isHandling($a_level){}
    
            public function log($a_message, $a_level = ilLogLevel::INFO){}
    
            public function dump($a_variable, $a_level = ilLogLevel::INFO){}
    
            public function debug($a_message, $a_context = []){}
    
            public function info($a_message){}
    
            public function notice($a_message){}
    
            public function warning($a_message){}
    
            public function error($a_message){}
    
            public function critical($a_message){}
    
            public function alert($a_message){}
    
            public function emergency($a_message){}
    
            public function getLogger(){}
    
            public function write($a_message, $a_level = ilLogLevel::INFO){}
    
            public function writeLanguageLog($a_topic, $a_lang_key){}
    
            public function logStack($a_level = null, $a_message = ''){}
    
            public function writeMemoryPeakUsage($a_level) {}
    
        };
        $GLOBALS["DIC"]["ilBench"] = null;
        $GLOBALS["DIC"]["lng"] = new ilLanguage('en');
        $GLOBALS["DIC"]["ilPluginAdmin"] = $plugin_admin;
        $GLOBALS["DIC"]["ilias"] = null;
        $GLOBALS["ilLog"] = $GLOBALS["DIC"]["ilLog"];
        $GLOBALS["DIC"]["ilErr"] = null;
        $GLOBALS["DIC"]["tree"] = new class() extends ilTree {
            public function __construct()
            {
            }
        };
        $GLOBALS["DIC"]["ilAppEventHandler"] = null;
        $GLOBALS["DIC"]["ilSetting"] = new ilSetting();
        $GLOBALS["DIC"]["objDefinition"] = new ilObjectDefinition();
        $GLOBALS["DIC"]["ilUser"] = new class() extends ilObjUser {
            public $prefs = [];

            public function __construct()
            {
                $this->prefs["language"] = "en";
            }
        };

        if (!defined('DEBUG')) {
            define('DEBUG', false);
        }

        if (!defined('SYSTEM_ROLE_ID')) {
            define('SYSTEM_ROLE_ID', '2');
        }

        if (!defined("CLIENT_ID")) {
            define('CLIENT_ID', $client_ini->readVariable('client', 'name'));
        }

        if (!defined("ILIAS_WEB_DIR")) {
            define('ILIAS_WEB_DIR', dirname(__DIR__, 4) . "/data/");
        }

        return $DIC;
    }
}
