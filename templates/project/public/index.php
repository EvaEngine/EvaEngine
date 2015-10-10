\<\?php
require __DIR__ . '/../vendor/autoload.php';

$engine = new \Eva\EvaEngine\Engine(__DIR__ . '/..', 'api');
$engine
    ->loadModules(include __DIR__ . '/../config/modules.' . $engine->getAppName() . '.php')
    ->bootstrap()
    ->run();
