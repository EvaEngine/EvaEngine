<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/12 下午4:51
// +----------------------------------------------------------------------
// + ModuleParser.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Module;


use Eva\EvaEngine\Exception\RuntimeException;
use Phalcon\Config;
use Phalcon\Loader;
use Phalcon\Mvc\ModuleDefinitionInterface;

class ModuleParser
{
    protected $defaultModulesPath;

    public function __construct($defaultModulesPath)
    {
        $this->defaultModulesPath = $defaultModulesPath;
    }

    protected function parseOptions(Module $module, $options)
    {
        // 模块定义所在的文件名
        $options['path'] = isset($options['path']) ?
            $options['path'] :
            rtrim($this->defaultModulesPath, '/') . '/Module.php';
        $options['path'] = realpath($options['path']);
        // 模块根目录
        $options['dir'] =
            isset($options['dir']) ?
                $options['dir'] :
                dirname($options['path']);
        $options['dir'] = realpath(rtrim($options['dir'], '/'));
        // 模块定义的类名
        $options['className'] = isset($options['className']) ?
            $options['className'] : $module->getName() . '\\Module';

        // 是否允许加载前台路由，默认是允许
        $options['routesFrontend'] = isset($options['routesFrontend']) ?
            boolval($options['routesFrontend']) : true;

        // 是否允许加载后台路由，默认是允许
        $options['routesBackend'] = isset($options['routesBackend']) ?
            boolval($options['routesBackend']) : true;

        // 是否允许加载模块的配置文件，默认为允许
        $options['configEnable'] = isset($options['configEnable']) ?
            boolval($options['configEnable']) : true;

        // 是否允许注册模块的事件，默认为允许
        $options['eventsEnable'] = isset($options['eventsEnable']) ?
            boolval($options['eventsEnable']) : true;

        // 是否允许注册模块的服务，默认为允许。
        $options['diEnable'] = isset($options['diEnable']) ?
            boolval($options['diEnable']) : true;
        // 是否允许显示管理后台菜单，默认为允许
        $options['adminMenusEnable'] = isset($options['adminMenusEnable']) ?
            boolval($options['adminMenusEnable']) : true;
        // 是否允许注册全局的 ORM 关联关系
        $options['relationsEnable'] = isset($options['relationsEnable']) ?
            boolval($options['relationsEnable']) : true;
        // 是否允许注册全局的视图助手
        $options['viewHelpersEnable'] = isset($options['viewHelpersEnable']) ?
            boolval($options['viewHelpersEnable']) : true;
        // 是否允许注册 ErrorHandler
        $options['errorHandlerEnable'] = isset($options['errorHandlerEnable']) ?
            boolval($options['errorHandlerEnable']) : true;

        return $options;
    }


    public function parse($name, $options)
    {

        $module = new Module($name);
        $options = $this->parseOptions($module, $options);
        $module->setOptions($options);
        $module->setClassName($options['className']);
        $module->setPath($options['path']);
        $module->setDir($options['dir']);
        $this->registerDefinitionClass($module, $options)
            ->parseConfig($module, $options)
            ->parseRoutesFrontend($module, $options)
            ->parseRoutesBackend($module, $options)
            ->parseAdminMenus($module, $options)
            ->parseListeners($module, $options)
            ->parseRelations($module, $options)
            ->parseViewHelpers($module, $options)
            ->parseGlobalAutoLoaders($module, $options)
            ->parseDI($module, $options);

        return $module;
    }

    protected function parseErrorHandler(Module $module, $options)
    {
        if ($options['errorHandlerEnable']) {
            $moduleClass = $module->getClassName();
            $module->setErrorHandlers($moduleClass::registerErrorHandlers());
        }
    }

    protected function parseGlobalAutoLoaders(Module $module, $options)
    {
        $moduleClass = $module->getClassName();
        $module->setAutoLoaders($moduleClass::registerGlobalAutoloaders());

        return $this;
    }

    protected function parseViewHelpers(Module $module, $options)
    {
        $moduleClass = $module->getClassName();
        if ($options['viewHelpersEnable']) {
            $module->setViewHelpers($moduleClass::registerGlobalViewHelpers());
        }

        return $this;
    }

    protected function parseListeners(Module $module, $options)
    {
        $moduleClass = $module->getClassName();
        if ($options['eventsEnable']) {
            $module->setListeners($moduleClass::registerGlobalEventListeners());
        }

        return $this;
    }

    protected function parseRelations(Module $module, $options)
    {
        $moduleClass = $module->getClassName();
        if ($options['relationsEnable']) {
            $module->setRelations($moduleClass::registerGlobalRelations());
        }

        return $this;
    }

    protected function parseConfig(Module $module, $options)
    {
        $moduleConfig = new Config(array());

        // 加载模块的配置
        if ($options['configEnable']) {
            foreach (glob($module->getDir() . '/config/config.*.php') as $_sub_configFile) {
                $matches = array();
                $fileName = substr($_sub_configFile, strrpos($_sub_configFile, '/') + 1);
                preg_match('/config\.(.+)\.php/i', $fileName, $matches);
                $configKey = $matches[1];
                $moduleConfig->merge(
                    new Config(array(
                        $configKey => include $_sub_configFile
                    ))
                );
            }
            if (is_file($module->getDir() . '/config/config.php')) {
                $moduleConfig->merge(new Config(include $module->getDir() . '/config/config.php'));
            }
        }

        $module->setConfig($moduleConfig->toArray());

        return $this;
    }

    protected function parseDI(Module $module, $options)
    {
        if ($options['diEnable']) {
            if (is_file($module->getDir() . '/config/di.php')) {
                $diDefinitions = include $module->getDir() . '/config/di.php';
                $realDiDefinitions = array();
                foreach ($diDefinitions['disposables'] as $name => $definition) {
                    $realDiDefinitions[$name] = array(
                        'definition' => $definition,
                        'share' => false,
                    );
                }
                // 在同一模块下，同名的共享 DI 会覆盖独享 DI
                foreach ($diDefinitions['shares'] as $name => $definition) {
                    $realDiDefinitions[$name] = array(
                        'definition' => $definition,
                        'share' => true,
                    );
                }
                $module->setDiDefinition($realDiDefinitions);
            }
        }
    }

    protected function parseRoutesFrontend(Module $module, $options)
    {
        // 加载模块的前台路由
        if ($options['routesFrontend'] && is_file($module->getDir() . '/config/routes.frontend.php')) {
            $module->setRoutesFrontend(include $module->getDir() . '/config/routes.frontend.php');
        }

        return $this;
    }

    protected function parseRoutesBackend(Module $module, $options)
    {
        // 加载模块的后台路由
        if ($options['routesBackend'] && is_file($module->getDir() . '/config/routes.backend.php')) {
            $module->setRoutesBackend(include $module->getDir() . '/config/routes.backend.php');
        }

        return $this;
    }

    protected function parseAdminMenus(Module $module, $options)
    {
        // 加载模块的管理后台菜单
        if ($options['adminMenus'] && is_file($module->getDir() . '/config/admin.menu.php')) {
            $module->setAdminMenusFile($module->getDir() . '/config/admin.menu.php');
        }

        return $this;
    }

    /**
     * 注册模块定义类
     *
     * @param Module $module
     * @param $options
     * @return $this
     * @throws RuntimeException
     */
    protected function registerDefinitionClass(Module $module, $options)
    {
        $loader = new Loader();
        $loader->registerClasses(array(
            $module->getClassName() => $module->getPath()
        ))->register();

        if (false === class_exists($module->getClassName())) {
            throw new RuntimeException(
                sprintf('Module %s load failed by not exist class', $module->getClassName())
            );
        }
        $moduleImplements = array_intersect(
            array(
                ModuleDefinitionInterface::class,
                StandardInterface::class
            ),
            class_implements($module->getClassName())
        );
        if (count($moduleImplements) !== 2) {
            throw new RuntimeException(
                sprintf('Module %s interfaces not correct', $module->getClassName())
            );
        }

        return $this;
    }
}