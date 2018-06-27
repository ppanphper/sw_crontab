<?php

/*
 * This file is part of the CI_RPC_Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Libs;

/**
 * Implements a lightweight PSR-0 compliant autoloader for CI_RPC_Framework.
 */
class Loader
{
    /**
     * 命名空间的路径
     */
    protected static $namespaces;

    /**
     * 自动载入类
     *
     * @param $class
     */
    public static function autoload($class)
    {
        $root = explode('\\', trim($class, '\\'), 2);
        if (count($root) > 1 and isset(self::$namespaces[$root[0]])) {
            $filePath = self::$namespaces[$root[0]] . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $root[1]) . '.php';
            if (is_file($filePath)) {
                require $filePath;
            }
        }
    }

    /**
     * Registers the autoloader class with the PHP SPL autoloader.
     *
     * @param bool $prepend Prepend the autoloader on the stack instead of appending it.
     */
    public static function register($prepend = false)
    {
        spl_autoload_register([__CLASS__, 'autoload'], true, $prepend);
    }

    /**
     * 设置根命名空间
     *
     * @param $root
     * @param $path
     */
    public static function addNameSpace($root, $path)
    {
        self::$namespaces[$root] = $path;
    }
}
