<?php

namespace Eva\EvaEngine\Module;

use Phalcon\Loader;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Events\Manager as EventsManager;

class Manager implements EventsAwareInterface
{
    protected $modules = array();

    protected $defaultPath;

    protected $loader;

    protected $cacheFile;

    protected $eventsManager;

    public function getLoader()
    {
        if($this->loader) {
            return $this->loader;
        }
        return $this->loader = new Loader();
    }

    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;
        return $this;
    }

    public function getDefaultPath()
    {
        return $this->defaultPath;
    }

    public function setDefaultPath($defaultPath)
    {
        $this->defaultPath = $defaultPath;
        return $this;
    }

    public function setCacheFile($cacheFile)
    {
        $this->cacheFile = $cacheFile;
        return $this;
    }

    public function getCacheFile()
    {
        return $this->cacheFile;
    }

    public function getEventsManager()
    {
        if($this->eventsManager) {
            return $this->eventsManager;
        }
        return new EventsManager();
    }

    public function setEventsManager($eventsManager)
    {
        return $this->eventsManager = $eventsManager;
    }

    public function readCache($cacheFile)
    {
        if(file_exists($cacheFile) && $cache = include($cacheFile)) {
            return $cache;
        }
        return null;
    }

    public function writeCache($cacheFile, array $content)
    {
        if($cacheFile && $fh = fopen($cacheFile, 'w')) {
            fwrite($fh, '<?php return ' . var_export($content, true) . ';');
            fclose($fh);
            return true;
        }
        return false;
    }

    public function getModules()
    {
        return $this->modules;
    }

    public function loadModules(array $moduleSettings)
    {
        //Trigger Event
        $this->getEventsManager()->fire('module:beforeLoadModule', $this);

        $cacheFile = $this->getCacheFile();
        $loader = $this->getLoader();

        if($cacheFile && $cache = $this->readCache($cacheFile)) {
            $loader->registerNamespaces($cache['namespaces'])->register();
            $loader->registerClasses($cache['classes'])->register();
            $this->modules = $cache['modules'];

            //Trigger Event
            $this->getEventsManager()->fire('module:afterLoadModule', $this);
            return $this;
        }

        $defaultModuleSetting = array(
            'className' => '',
            'path' => '',  //Module bootstrap file path
            'dir' => '', //Module source codes dir
            'moduleConfig' => '', //module config file path
            'routesFrontend' => '', //module router frontend path
            'routesBackend' => '', //module router backend path
            'listeners' => '', //module listeners list array
            'viewHelpers' => '', //module view helpers
            'adminMenu' => '', //admin menu
            'translatePath' => false,
        );

        $modules = array();
        $classes = array();
        $modulesPath = $this->getDefaultPath();
        foreach ($moduleSettings as $key => $module) {
            if (is_array($module)) {
                $moduleKey = ucfirst($key);
                $module = array_merge($defaultModuleSetting, $module);
            } elseif (is_string($module)) {
                //TODO: if module already registered in composer
                if(class_exists($module)) {
                    continue;
                } else {
                    $moduleKey = ucfirst($module);
                    //Only Module Name means its a Eva Standard module
                    $module = array_merge($defaultModuleSetting, array(
                        'className' => "Eva\\$moduleKey\\Module",
                        'path' => "$modulesPath/$moduleKey/Module.php",
                    ));
                }
            } else {
                throw new \Exception(sprintf('Module %s load failed by incorrect format', $key));
            }

            $module['className'] = $module['className'] ? $module['className'] : "Eva\\$key\\Module";
            $module['path'] = $module['path'] ? $module['path'] : "$modulesPath/$key/Module.php";
            $module['dir'] = dirname($module['path']);

            //Disabled when value is false
            $module['moduleConfig'] = false === $module['moduleConfig'] || $module['moduleConfig'] ? $module['moduleConfig'] : $module['dir'] . '/config/config.php';
            $module['routesBackend'] = false === $module['routesBackend'] || $module['routesBackend'] ? $module['routesBackend'] : $module['dir'] . '/config/routes.backend.php';
            $module['routesFrontend'] = false === $module['routesFrontend'] || $module['routesFrontend'] ? $module['routesFrontend'] : $module['dir'] . '/config/routes.frontend.php';
            $module['translatePath'] = false === $module['translatePath'] || $module['translatePath'] ? $module['translatePath'] : $module['dir'] . '/languages';
            $module['adminMenu'] = false === $module['adminMenu'] || $module['adminMenu'] ? $module['adminMenu'] : $module['dir'] . '/config/admin.menu.php';

            $classes[$module['className']] = $module['path'];
            $modules[$moduleKey] = $module;
        }

        $namespaces = array();
        $listeners = array();
        $loader->registerClasses($classes)->register();
        foreach($modules as $key => $module) {
            if(!class_exists($module['className']) || !(new $module['className'] instanceof StandardInterface)) {
                continue;
            }
            $namespace = $module['className']::registerGlobalAutoloaders();
            if(is_array($namespace)) {
                $namespaces += $namespace;
            }
            $modules[$key]['listeners'] = $module['className']::registerGlobalEventListeners();
            $modules[$key]['viewHelpers'] = $module['className']::registerGlobalViewHelpers();
        }
        $loader->registerNamespaces($namespaces)->register();

        $this->modules = $modules;

        if($cacheFile) {
            $this->writeCache($cacheFile, array(
                'classes' => $classes,
                'namespaces' => $namespaces,
                'modules' => $modules,
            ));
        }
        //Trigger Event
        $this->getEventsManager()->fire('module:afterLoadModule', $this);
        return $this;
    }

    public function getModulePath($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['dir']) && file_exists($modules[$moduleName]['dir'])) {
            return $modules[$moduleName]['dir'];
        }
        return '';
    }

    public function getModuleConfig($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['moduleConfig']) && file_exists($modules[$moduleName]['moduleConfig'])) {
            return include $modules[$moduleName]['moduleConfig'];
        }
        return array();
    }

    public function getModuleRoutesFrontend($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['routesFrontend']) && file_exists($modules[$moduleName]['routesFrontend'])) {
            return include $modules[$moduleName]['routesFrontend'];
        }
        return array();
    }

    public function getModuleRoutesBackend($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['routesBackend']) && file_exists($modules[$moduleName]['routesBackend'])) {
            return include $modules[$moduleName]['routesBackend'];
        }
        return array();
    }

    public function getModuleListeners($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['listeners'])) {
            return $modules[$moduleName]['listeners'];
        }
        return array();
    }

    public function getModuleAdminMenu($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['adminMenu']) && file_exists($modules[$moduleName]['adminMenu'])) {
            return include $modules[$moduleName]['adminMenu'];
        }
        return '';
    }

    public function getModuleViewHelpers($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['viewHelpers'])) {
            return $modules[$moduleName]['viewHelpers'];
        }
        return array();
    }

    public function __construct($defaultPath = null)
    {
        if($defaultPath) {
            $this->defaultPath = $defaultPath;
        }
    }
}
