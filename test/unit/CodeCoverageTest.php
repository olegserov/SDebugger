<?php
class CodeCoverageTest extends PHPUnit_Framework_TestCase
{
    private $_paths = array(
        '/classes',
        '/lib/other/'
    );

    public function testLoadAllFiles()
    {
        $go = false;

        foreach ($_SERVER['argv'] as $args) {
            if (strpos($args, '--coverage-html') !== false) {
                $go = true;
            }
        }

        if ($go == false) {
            return;
        }

        foreach ($this->_paths as $path) {
            if (!file_exists(dirname(dirname(dirname(__FILE__))) . $path)) {
                continue;
            }
            $itr = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    dirname(dirname(dirname(__FILE__))) . $path
                )
            );

            foreach ($itr as $file) {
                 if (preg_match('/\.svn\b/', $file)) continue;
                 if (preg_match('/\.git\b/', $file)) continue;
                 if (!preg_match('/\.php$/i', $file)) continue;
                 if (!is_readable($file)) continue;
                 include_once $file;
            }
        }
    }
}
