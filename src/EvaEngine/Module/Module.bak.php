<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/5/22 下午5:54
// +----------------------------------------------------------------------
// + Module.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Module;


class ModuleBak
{
    protected $name;
    protected $settings = array();

    public function getInfo()
    {
        $moduleName = ucfirst($this->name);
        $modulesPath = $this->getDefaultPath();
        $ds = DIRECTORY_SEPARATOR;

        //Get basic module info only contains className and path
        if (true === is_null($this->settings)) {
            $module = array(
                'className' => "Eva\\$moduleName\\Module",
                'path' => "$modulesPath{$ds}$moduleName{$ds}Module.php",
            );
        } elseif (true === is_string($this->settings) && strpos($this->settings, '\\') !== false) {
            //Composer module
            $moduleClass = $this->settings;
            if (false === class_exists($moduleClass)) {
                throw new \Exception(sprintf('Module %s load failed by not exist class', $moduleClass));
            }

            $ref = new \ReflectionClass($moduleClass);
            $module = array(
                'className' => $moduleClass,
                'path' => $ref->getFileName(),
            );
        } elseif (true === is_array($this->settings)) {
            $module = array_merge(
                array(
                    'className' => '',
                    'path' => '',
                ),
                $this->settings
            );
            $module['className'] = $module['className'] ?: "Eva\\$moduleName\\Module";
            $module['path'] = $module['path'] ?: "$modulesPath{$ds}$moduleName{$ds}Module.php";
        } else {
            throw new \Exception(sprintf('Module %s load failed by incorrect format', $moduleName));
        }

        /**
         * @var StandardInterface $moduleClass
         */
        $moduleClass = $module['className'];
        $this->getLoader()->registerClasses(
            array(
                $moduleClass => $module['path']
            )
        )->register();

        if (false === class_exists($moduleClass)) {
            throw new \Exception(sprintf('Module %s load failed by not exist class', $moduleClass));
        }

        if (count(
                array_intersect(
                    array(
                        \Phalcon\Mvc\ModuleDefinitionInterface::class,
                        \Eva\EvaEngine\Module\StandardInterface::class
                    ),
                    class_implements($moduleClass)
                )
            ) !== 2
        ) {
            throw new \Exception(sprintf('Module %s interfaces not correct', $moduleClass));
        }

        $module['dir'] = $moduleDir = dirname($module['path']);
        $module = array_merge(
            array(
                'moduleConfig' => "$moduleDir{$ds}config{$ds}config.php", //module config file path
                'routesFrontend' => "$moduleDir{$ds}config{$ds}routes.frontend.php", //module router frontend path
                'routesBackend' => "$moduleDir{$ds}config{$ds}routes.backend.php", //module router backend path
                'routesCommand' => "$moduleDir{$ds}config{$ds}routes.command.php", // module router in CLI mode
                'adminMenu' => "$moduleDir{$ds}config{$ds}admin.menu.php", //admin menu
                'autoloaders' => $moduleClass::registerGlobalAutoloaders(), //autoloaders
                'relations' => $moduleClass::registerGlobalRelations(), //entity relations for injection
                'listeners' => $moduleClass::registerGlobalEventListeners(), //module listeners list array
                'viewHelpers' => $moduleClass::registerGlobalViewHelpers(), //module view helpers
                //'translatePath' => false,
            ),
            $module
        );

        return $module;
    }
}