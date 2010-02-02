<?php

require dirname(dirname(__FILE__)) . '/classes/Bootstrap.php';
Bootstrap::console();

PHPUnit_PerDirTextUI_Command::addTestDir(dirname(__FILE__) . '/SDebugger');
PHPUnit_PerDirTextUI_Command::addTestDir(dirname(__FILE__) . '/unit');

if (!defined('PHPUnit_PerDirTextUI_Command_NO_RUN')) {
    PHPUnit_PerDirTextUI_Command::main();
}
