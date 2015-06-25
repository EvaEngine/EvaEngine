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
foreach (array(
             __DIR__ . '/../../autoload.php',
             __DIR__ . '/../vendor/autoload.php',
             __DIR__ . '/vendor/autoload.php'
         ) as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}

use Eva\EvaEngine\Engine;

$app = new \Eva\EvaEngine\Console\ConsoleApplication(empty($_SERVER['APPLICATION_NAME']) ? 'evaengine' : $_SERVER['APPLICATION_NAME']);
$engine = new Engine(__DIR__ . '/..', $app);

$engine
    ->loadModules(include __DIR__ . '/../config/modules.' . $app->getName() . '.php')
    ->bootstrap()
    ->run();
