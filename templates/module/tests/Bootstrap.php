\<\?php
if(!extension_loaded('phalcon')) {
    die('Phalcon extension not loaded');
}
/** @var Composer\Autoload\ClassLoader $loader */
$loader = include __DIR__ . '/../../../vendor/autoload.php';
$loader->addPsr4('<?=$moduleNamespace?>', __DIR__ . '/../src/<?=$moduleName?>');
$loader->addPsr4('<?=$moduleNamespace?>Tests', __DIR__ . '/<?=$moduleNamespace?>Tests');
$loader->addClassMap(['<?=$moduleNamespace?>\Module' => __DIR__ . '/../Module.php']);
