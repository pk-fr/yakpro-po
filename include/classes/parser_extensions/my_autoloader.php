<?php declare(strict_types=1);

//========================================================================
// Author:                  Nikita Popov
// GitHub:                  https://github.com/nikic
//
// Modified 2018-02-09 by:  Pascal KISSIAN
// Resume:                  http://pascal.kissian.net
//
// Freely adapted from legacy PHP-Parser Autoloader ...
// that has been deprecated in favour of composer autoloader ...
// yakpro-po do not use any 3rd party components but PHP-Parser
// so this standalone autoloader has been included into yakpro-po!
//========================================================================

namespace PhpParser;

/**
 * @codeCoverageIgnore
 */
class Autoloader
{
    /** @var bool Whether the autoloader has been registered. */
    private static $registered = false;

    /**
     * Registers PhpParser\Autoloader as an SPL autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader instead of appending
     */
    public static function register(bool $prepend = false) {
        if (self::$registered === true) {
            return;
        }

        spl_autoload_register([__CLASS__, 'autoload'], true, $prepend);
        self::$registered = true;
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    public static function autoload(string $class) {
        if (0 === strpos($class, 'PhpParser\\')) {
            global $yakpro_po_dirname;
            $fileName = $yakpro_po_dirname.'/'.PHP_PARSER_DIRECTORY.'/lib/'.strtr($class,'\\','/').'.php';
            if (file_exists($fileName)) {
                require $fileName;
            }
        }
    }
}

Autoloader::register();