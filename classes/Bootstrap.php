<?php
class Bootstrap
{

    private static $_exceptionizer;

    /**
     * Common initialization.
     */
    public static function common()
    {
        if (!defined('BOOTSTRAP_MODE')) {
            define('BOOTSTRAP_MODE', 'common');
        }
        // Initialize library directories.
        $root = dirname(dirname(__FILE__));
        set_include_path(implode(PATH_SEPARATOR, array(
            dirname(__FILE__),
            $root . '/lib/other',
            $root . '/lib/FTemplate',
            $root . '/lib/PHPUnit',
            get_include_path(), // default path - at the end!
        )));

        // Turn on class autoloading.
        require_once 'PHP/Autoload.php';
        PHP_Autoload::initialize();
    }


    /**
     * Initialization for console.
     */
    public static function console()
    {
        define('BOOTSTRAP_MODE', 'console');

        self::common();

        self::$_exceptionizer = new PHP_Exceptionizer();
    }

    public static function isConsoleMode()
    {
        return !isset($_SERVER['GATEWAY_INTERFACE']);
    }
}
