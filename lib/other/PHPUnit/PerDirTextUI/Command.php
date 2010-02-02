<?php
/**
 * A TestRunner for the Command Line Interface (CLI)
 * with support of recurrend directory scan.
 *
 * @version 0.30
 */
class PHPUnit_PerDirTextUI_Command
{
    private static $_testDirs = array();
    private static $_testManualDirs = array();
    private static $_testFilesCache = null;
    private static $_testFiles = array();

    /**
     * Main entry point.
     * This method is called by default when this file is included.
     *
     * @return void
     */
    public static function main()
    {
    	// Very early initialization.
		if (!defined('PHPUnit_MAIN_METHOD')) {
		    define('PHPUnit_MAIN_METHOD', 'PHPUnit_PerDirTextUI_Command::doNothing');
		}
		require_once 'PHPUnit/TextUI/Command.php';
		PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

        // Initialize include_path (default pathes - at  the end!).
        set_include_path(implode(PATH_SEPARATOR, array_merge(array_keys(self::$_testDirs), array(get_include_path()))));
        ob_implicit_flush(1);

        // Hack for Windows: allow to find php.exe correctly.
        if (getenv('COMSPEC')) {
            $bin = str_replace('/', '\\', dirname(ini_get('extension_dir')) . "/php.exe");
            if (@is_file($bin)) putenv("PHP_PEAR_PHP_BIN=$bin");
        } else {
        	require_once "PEAR/Config.php";
	        $conf = &PEAR_Config::singleton();
	        putenv("PHP_PEAR_PHP_BIN=" . $conf->get('php_bin'));
        }

        // Extract arguments.
        $filterMasks = array();
        $singleMode = false;
        while (@$_SERVER['argv'][1]) {
            if ($_SERVER['argv'][1] == "--single") {
            	// Single mode argument?
                $singleMode = true;
                array_splice($_SERVER['argv'], 1, 1);
            } else {
            	if (!preg_match('/^--/', $_SERVER['argv'][1])) {
		            $filterMasks[] = $_SERVER['argv'][1];
		            array_splice($_SERVER['argv'], 1, 1);
            	} else {
            		// Found unknown "--" option. Exit parsing.
            		break;
            	}
            }
        }

        // Process arguments.
        $testFiles = array();
        $list = self::getTestFiles($filterMasks);
        foreach ($list as $path => $info) {
        	$name = $info['name'];
        	$isManual = $info['isManual'];
        	$group = $info['group'];
            if ($filterMasks) {
                foreach ($filterMasks as $mask) {
                    if ($group == $mask || self::matchMask($mask, $name) || self::matchMask($mask, $path)) {
                        $testFiles[] = $path;
                        break;
                    }
                }
            } else if (!$isManual) {
                $testFiles[] = $path;
            }
        }

        // Print message if we run tests not for all files.
        if ($filterMasks) {
            if ($testFiles) {
                echo "Running tests only in:\n" . join("\n", $testFiles) . "\n\n";
            } else {
                die("No files matched specified filter masks: " . join(" ", $filterMasks) . "\n");
            }
        } else {
            echo sprintf("Running tests in %d files...\n", count($testFiles));
        }
        self::$_testFiles = $testFiles;

        // Run tests.
        if (!$singleMode) {
            self::_runTestFiles($testFiles);
        } else {
        	$results = array();
        	$lineLen = 0;
        	foreach ($testFiles as $path) {
        		$result = self::_runSingleTest($path);
        		$graph = $result[0];
        		for ($i = 0; $i < strlen($graph); $i++) {
                    echo $graph[$i];
                    $lineLen++;
                    if ($lineLen >= 60) {
                    	$lineLen = 0;
                    	echo "\n";
                    }
        		}
                flush();
      			if ($result[1]) {
                    $results[] = $result[1];
        		}
        	}
        	echo "\n";
        	if ($results) {
        		echo "\nThere was failures:\n\n";
        	    echo trim(join("", $results));
        	    echo "\n\nFAILURES!\n";
        	}
        }
    }

    /**
     * Run a single test in the separate process and parse its result.
     *
     * @param string    $path
     * @return array    array(dotGraph, failureText)
     */
    private static function _runSingleTest($path)
    {
        $cmd = getenv('PHP_PEAR_PHP_BIN') // do not escapeshellarg() for php_bin - windows bug!
            . ' ' . escapeshellarg($_SERVER['argv'][0])
            . ' ' . escapeshellarg($path);
        $result = shell_exec($cmd);
        $m = $p = null;
        if (!preg_match('/^PHPUnit \d+.*\n\s*(\S+)\s*([\s\S]*)/m', $result, $m)) {
        	return array("?", "");
        }
        if (preg_match('/.*?(^\d+\)[\s\S]*)^FAILURES![\s\S]*/m', $m[2], $p)) {
        	return array($m[1], $p[1]);
        } else {
            return array($m[1], null);
        }
    }

    /**
     * Run multiple tests in current process.
     *
     * @param array $testFiles
     * @return void
     */
    private static function _runTestFiles($testFiles)
    {
        // Create temporary config file.
        $xml = array();
        foreach ($testFiles as $file) {
            $xml[] = "<file>" . htmlspecialchars($file) . "</file>";
        }
        $xml = "<phpunit><testsuite name=\"Overall\">" . join("", $xml) . "</testsuite></phpunit>";
        $tmp = tempnam('non-existent', 'phpunit');
        file_put_contents($tmp, $xml);

        // Run all the tests.
        //$_SERVER['argv'][] = '--no-syntax-check'; // speedup!
        $_SERVER['argv'][] = '--configuration';
        $_SERVER['argv'][] = $tmp;
        PHPUnit_TextUI_Command::main();
    }

    /**
     * Add a new test directory to the list.
     *
     * @param string $dir        Directory to scan
     * @param string $fnSuffix   Required test filename suffix.
     * @return void
     */
    public static function addTestDir($dir, $fnSuffix = "")
    {
        self::$_testDirs[realpath($dir)] = array('suffix' => $fnSuffix, 'isManual' => false);
        self::$_testFilesCache = null;
    }

    /**
     * Add a new test directory which is NOT used while running all the tests.
     *
     * @param string $dir        Directory to scan
     * @param string $fnSuffix   Required test filename suffix.
     * @return void
     */
    public static function addManualTestDir($dir, $fnSuffix = "")
    {
        self::$_testDirs[realpath($dir)] = array('suffix' => $fnSuffix, 'isManual' => true);
        self::$_testFilesCache = null;
    }

    /**
     * Return all test files found in added directories.
     *
     * @return array  Key - full pathname, value - array('name' => <test filename
     *                with path relative to a directory added by addTestDir()>,
     *                'isManual' => <is this file for manual mash filtering>).
     */
    public static function getTestFiles($filterMasks)
    {
        if (self::$_testFilesCache) {
            return self::$_testFilesCache;
        }
        if (count($filterMasks) == 1 && preg_match('{^(\w:)?[/\\\\][^*?]+$}s', $filterMasks[0])) {
        	// Speed optimization to run a single test file specified
        	// by an absolute path.
        	$files[$filterMasks[0]] = $filterMasks[0];
        } else {
        	// Use the full directory scan.
	        $files = array();
	        foreach (self::$_testDirs as $dir => $info) {
	        	$fnSuffix = $info['suffix'];
	        	$isManual = $info['isManual'];
	            $dir = str_replace('\\', '/', $dir);
	            $cwd = getcwd();
	            chdir($dir);
	            $elements = array();
	            self::globRecurrent(".", $elements);
	            foreach ($elements as $e) {
	                if (!is_file($e) || !preg_match('/' . $fnSuffix . '\.phpt?$/i', $e)) continue;
	                $path = preg_replace('{^\.[/\\\\]}si', '', (string)$e);
	                $path = str_replace('\\', '/', $path);
	                $name = preg_replace('{\.phpt?$}s', '', $path);
	                $files[$dir . '/' . $path] = array('name' => $name, 'isManual' => $isManual, 'group' => basename($dir));
	            }
	            chdir($cwd);
	        }
        }
        return self::$_testFilesCache = $files;
    }

    /**
     * Builds recurrent directory listing. RecursiveDirectoryIterator
     * is bad, because it throws exception on "permission denied" error.
     *
     * @param string $dir
     * @param array &$files
     * @return void
     */
    public static function globRecurrent($dir, &$files)
    {
        foreach (glob("$dir/*") as $e) {
            $files[] = $e;
            if (is_dir($e) && !is_link($e) && @file_exists("$e/.")) {
                self::globRecurrent($e, $files);
            }
        }
    }

    /**
     * Returns the list of all filtered test classes
     *
     * @return array
     */
    public static function getFilteredClasses()
    {
    	$classes = array();
    	foreach (self::$_testFiles as $file) {
    		$file = str_replace('\\', '/', preg_replace('/\..*$/s', '', realpath($file)));
    		if (!$file) continue;
    		$bestClass = null;
    		$bestInc = null;
            foreach (explode(PATH_SEPARATOR, get_include_path()) as $inc) {
                $inc = str_replace('\\', '/', realpath($inc));
                if (strlen($inc) < strlen($bestInc)) {
                	// Select most long include_path directory.
                	continue;
                }
                if ("$inc/" == substr($file, 0, strlen($inc) + 1)) {
                	$bestClass = str_replace('/', '_', substr($file, strlen($inc) + 1));
                	$bestInc = $inc;
                }
            }
            if ($bestClass) {
            	$classes[] = $bestClass;
            }
    	}
    	return $classes;
    }

    /**
     * Return the maximum common namespace used by a class list
     * or NULL if no common namespace exists.
     *
     * @param array $classes
     * @return string
     */
	public static function getMaxCommonNamespace($classes)
	{
	    if (!$classes) {
	        return null;
	    }
	    $listOfParts = array();
	    foreach ($classes as $class) {
	        $parts = array();
	        foreach (explode("_", $class) as $part) {
	            if (strtolower($part) == "test") continue;
	            $parts[] = preg_replace('/Test$/s', '', $part);
	        }
	        $listOfParts[] = $parts;
	    }
	    for ($col = 0; $col < count($listOfParts[0]); $col++) {
	        $sameColumn = true;
	        foreach ($listOfParts as $part) {
	            if (@$part[$col] != @$listOfParts[0][$col]) {
	                $sameColumn = false;
	                break;
	            }
	        }
	        if (!$sameColumn) {
	            return $col > 0? join("_", array_slice($listOfParts[0], 0, $col)) : null;
	        }
	    }
	    return null;
	}

    /**
     * Returns true if a given filename matches the specified mask.
     * "*" in the mask may match not only pathname character, but "/" too.
     *
     * @param string $pattern
     * @param string $string
     * @return bool
     */
    public function matchMask($pattern, $string)
    {
        if (false === strpos($pattern, '/') && false === strpos($pattern, '\\') && false === strpos($pattern, '**')) {
            $string = basename($string);
        }
        $re = strtr(
            addcslashes($pattern, '/\\.+^$(){}=!<>|'),
            array('**' => '.*', '*' => '[^/\\\\]*', '?' => '.?')
        );
        return @preg_match('{^(?:' . $re . ')$}i', $string);
    }

    /**
     * A stub function to avoid PHPUnit_TextUI_Command::main()
     * automatic calling.
     *
     * @return void
     */
    public function doNothing()
    {
    }
}
