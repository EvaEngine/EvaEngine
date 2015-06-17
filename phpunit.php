<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

require __DIR__ . '/vendor/autoload.php';

$engine = new Eva\EvaEngine\Engine();
$engine->bootstrap();
View::setViewsDir(__DIR__ . '/views/');
$methods = [];
$method = [
    'name' => 'eva_get'
];
$namespaces = [];
foreach (\Eva\EvaEngine\IoC::getDI()->getServices() as $name => $service) {
    $method['bindings'][$name] = get_class(\Eva\EvaEngine\IoC::get($name));
};
$methods[] = $method;
$method['name'] = '\Eva\EvaEngine\IoC::get';
$methods[] = $method;
foreach (Config::get('alias') as $aliasName => $facade) {
    /** @var \Eva\EvaEngine\Facades\FacadeBase $facade */
    $name = $facade::getFacadeAccessor();
    $binding = $facade::getFacadeRoot();
    // 生成 facade 帮助文件
    $alias = new \Eva\EvaEngine\DevTool\IDE\Facade\Alias($aliasName, $facade);
    $namespace = $alias->getNamespace();

    if (!isset($namespaces[$namespace])) {
        $namespaces[$namespace] = array();
    }

    $namespaces[$namespace][] = $alias;
}

file_put_contents(__DIR__ . '/.phpstorm.meta.php', View::getRender('ide', 'meta', ['methods' => $methods]));
file_put_contents(
    __DIR__ . '/.eve-engine-ide-helper.php',
    View::getRender('ide', 'helper', [
        'version' => '1.2',
        'namespaces' => $namespaces,
        'helpers' => '',
    ])
);
