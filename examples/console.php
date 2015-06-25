<?php
// +----------------------------------------------------------------------
// | wallstreetcn
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/19 下午4:28
// +----------------------------------------------------------------------
// + console.php
// +----------------------------------------------------------------------
$projectRoot = __DIR__ . '/../../';
require $projectRoot . 'vendor/autoload.php';

use Eva\EvaEngine\Engine;

$app = new \Eva\EvaEngine\Console\ConsoleApplication('wallstreetcn.com console', '0.1');
$engine = new Engine($projectRoot, $app);

$engine
    ->loadModules(include $engine->getProjectRoot() . '/config/modules.wscn.php')
    ->bootstrap()
    ->run();
