<?php namespace ILIAS\ArtifactBuilder\Generators;

use Iterator;
use Throwable;

/**
 * Class InterfaceFinder
 *
 * @package ILIAS\ArtifactBuilder\Generators
 */
class InterfaceFinder
{

    /**
     * @var string
     */
    private $interface = "";


    /**
     * InterfaceFinder constructor.
     *
     * @param string $interface
     */
    public function __construct(string $interface)
    {
        $this->interface = $interface;
        $this->getAllClassNames();
    }


    /**
     * @return Iterator
     */
    private function getAllClassNames() : Iterator
    {
        // We use the composer classmap ATM
        $composer_classmap = include "./libs/composer/vendor/composer/autoload_classmap.php";
        $root = substr(__FILE__, 0, strpos(__FILE__, "/src"));

        if (!is_array($composer_classmap)) {
            throw new \LogicException("Composer ClassMap not loaded");
        }

        foreach ($composer_classmap as $class_name => $file_path) {
            $path = str_replace($root, "", realpath($file_path));
            if (strpos($path, "/libs/") !== 0) {
                yield $class_name;
            };
        }
    }


    /**
     * @return Iterator
     */
    public function getMatchingClassNames() : Iterator
    {
        foreach ($this->getAllClassNames() as $class_name) {
            try {
                $r = new \ReflectionClass($class_name);
                if ($r->isInstantiable() && $r->implementsInterface($this->interface)) {
                    yield $class_name;
                }
            } catch (Throwable $e) {
                // noting to do here
            }
        }
    }
}