<?php

require dirname(dirname(__FILE__)) . '/classes/Bootstrap.php';
Bootstrap::console();

PHPUnit_PerDirTextUI_Command::addTestDir(dirname(__FILE__) . '/../lib/FTemplate/FTemplate/Test');
PHPUnit_PerDirTextUI_Command::addTestDir(dirname(__FILE__) . '/unit');
PHPUnit_PerDirTextUI_Command::addTestDir(dirname(__FILE__) . '/functional');
PHPUnit_PerDirTextUI_Command::addTestDir(dirname(__FILE__) . '/regress');

if (!defined('PHPUnit_PerDirTextUI_Command_NO_RUN')) {
    PHPUnit_PerDirTextUI_Command::main();
}
