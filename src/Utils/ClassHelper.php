<?php


namespace App\Utils;


class ClassHelper
{
    private static mixed $composer = null;
    private static array $classes = [];

    public function __construct()
    {
        self::$composer = null;
        self::$classes  = [];

        self::$composer = require __DIR__ . '/../../vendor/autoload.php';

        if (false === empty(self::$composer)) {
            self::$classes  = array_keys(self::$composer->getClassMap());
        }
    }

    public function getClasses(): array
    {
        $allClasses = [];

        if (false === empty(self::$classes)) {
            foreach (self::$classes as $class) {
                $allClasses[] = '\\' . $class;
            }
        }

        return $allClasses;
    }

    public function getClassesByNamespace($namespace): array
    {
        if (0 !== strpos($namespace, '\\')) {
            $namespace = '\\' . $namespace;
        }

        $termUpper = strtoupper($namespace);
        return array_filter($this->getClasses(), function($class) use ($termUpper) {
            $className = strtoupper($class);
            if (
                0 === strpos($className, $termUpper) and
                false === strpos($className, strtoupper('Abstract')) and
                false === strpos($className, strtoupper('Interface'))
            ){
                return $class;
            }
            return false;
        });
    }

}
