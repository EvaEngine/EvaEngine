<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Module;

use Phalcon\Loader;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Events\Manager as EventsManager;
use Eva\EvaEngine\Mvc\Model;

/**
 * Module Manager for module register / load
 *
 * @package Eva\EvaEngine\Module
 */
class Manager implements EventsAwareInterface
{

    /**
     * Loaded modules
     * @var array
     */
    protected $modules = array();

    /**
     * @var string
     */
    protected $defaultPath;

    /**
     * @var Loader
     */
    protected $loader;

    /**
     * @var string
     */
    protected $cacheFile;

    /**
     * @var EventsManager
     */
    protected $eventsManager;

    /**
     * @var bool
     */
    protected $injectRelations = false;

    /**
     * @return Loader
     */
    public function getLoader()
    {
        if ($this->loader) {
            return $this->loader;
        }
        return $this->loader = new Loader();
    }

    /**
     * @param Loader $loader
     * @return $this
     */
    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getDefaultPath()
    {
        return $this->defaultPath;
    }

    /**
     * @param $defaultPath
     * @return $this
     */
    public function setDefaultPath($defaultPath)
    {
        $this->defaultPath = $defaultPath;
        return $this;
    }

    /**
     * @param $cacheFile
     * @return $this
     */
    public function setCacheFile($cacheFile)
    {
        $this->cacheFile = $cacheFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getCacheFile()
    {
        return $this->cacheFile;
    }

    /**
     * @return EventsManager
     */
    public function getEventsManager()
    {
        if ($this->eventsManager) {
            return $this->eventsManager;
        }
        return new EventsManager();
    }

    /**
     * @param ManagerInterface $eventsManager
     * @return ManagerInterface
     */
    public function setEventsManager($eventsManager)
    {
        return $this->eventsManager = $eventsManager;
    }

    /**
     * @param $cacheFile
     * @return mixed|null
     */
    public function readCache($cacheFile)
    {
        if (file_exists($cacheFile) && $cache = include($cacheFile)) {
            return $cache;
        }
        return null;
    }

    /**
     * @param $cacheFile
     * @param array $content
     * @return bool
     */
    public function writeCache($cacheFile, array $content)
    {
        if ($cacheFile && $fh = fopen($cacheFile, 'w')) {
            fwrite($fh, '<?php return ' . var_export($content, true) . ';');
            fclose($fh);
            return true;
        }
        return false;
    }

    /**
     * @param $moduleName
     * @return bool
     */
    public function hasModule($moduleName)
    {
        return isset($this->modules[$moduleName]) ? true : false;
    }

    /**
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param array $moduleSettings
     * @return $this
     * @throws \Exception
     */
    public function loadModules(array $moduleSettings)
    {
        //Trigger Event
        $this->getEventsManager()->fire('module:beforeLoadModule', $this);

        $cacheFile = $this->getCacheFile();
        $loader = $this->getLoader();

        if ($cacheFile && $cache = $this->readCache($cacheFile)) {
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
            'routesCommand' => '', // module router in CLI mode
            'relations' => '', //entity relations for injection
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
                $moduleKey = ucfirst($module);
                $moduleClass = "Eva\\$moduleKey\\Module";
                //Class already registered by composer
                if (true === class_exists($moduleClass)) {
                    $ref = new \ReflectionClass($moduleClass);
                    $path = dirname($ref->getFileName());
                    //Only Module Name means its a Eva Standard module
                    $module = array_merge($defaultModuleSetting, array(
                        'className' => $moduleClass,
                        'path' => "$path/Module.php",
                    ));

                } else {
                    //Only Module Name means its a Eva Standard module
                    $module = array_merge($defaultModuleSetting, array(
                        'className' => $moduleClass,
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
            $module['routesCommand'] = false === $module['routesCommand'] || $module['routesCommand'] ? $module['routesCommand'] : $module['dir'] . '/config/routes.command.php';

            $module['translatePath'] = false === $module['translatePath'] || $module['translatePath'] ? $module['translatePath'] : $module['dir'] . '/languages';
            $module['adminMenu'] = false === $module['adminMenu'] || $module['adminMenu'] ? $module['adminMenu'] : $module['dir'] . '/config/admin.menu.php';

            $classes[$module['className']] = $module['path'];
            $modules[$moduleKey] = $module;
        }

        $namespaces = array();
        $listeners = array();
        $loader->registerClasses($classes)->register();
        foreach ($modules as $key => $module) {
            if (!class_exists($module['className'])) {
                continue;
            }
            $moduleInstance = new $module['className'];
            if (!($moduleInstance instanceof StandardInterface)) {
                continue;
            }

            $namespace = $module['className']::registerGlobalAutoloaders();
            if (is_array($namespace)) {
                $namespaces += $namespace;
            }
            $modules[$key]['listeners'] = $module['className']::registerGlobalEventListeners();
            $modules[$key]['viewHelpers'] = $module['className']::registerGlobalViewHelpers();
            $modules[$key]['relations'] = $module['className']::registerGlobalRelations();
        }
        $loader->registerNamespaces($namespaces)->register();

        $this->modules = $modules;

        if ($cacheFile) {
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

    /**
     * @param $moduleName
     * @return string
     */
    public function getModulePath($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['dir']) && file_exists($modules[$moduleName]['dir'])) {
            return $modules[$moduleName]['dir'];
        }
        return '';
    }

    /**
     * @param $moduleName
     * @return array|mixed
     */
    public function getModuleConfig($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['moduleConfig']) && file_exists($modules[$moduleName]['moduleConfig'])) {
            return include $modules[$moduleName]['moduleConfig'];
        }
        return array();
    }

    /**
     * @param $moduleName
     * @return array|mixed
     */
    public function getModuleRoutesFrontend($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['routesFrontend']) && file_exists($modules[$moduleName]['routesFrontend'])) {
            return include $modules[$moduleName]['routesFrontend'];
        }
        return array();
    }

    /**
     * @param $moduleName
     * @return array|mixed
     */
    public function getModuleRoutesCommand($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['routesCommand']) && file_exists($modules[$moduleName]['routesCommand'])) {
            return include $modules[$moduleName]['routesCommand'];
        }
        return array();
    }

    /**
     * @param $moduleName
     * @return array|mixed
     */
    public function getModuleRoutesBackend($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['routesBackend']) && file_exists($modules[$moduleName]['routesBackend'])) {
            return include $modules[$moduleName]['routesBackend'];
        }
        return array();
    }

    /**
     * @param $moduleName
     * @return array
     */
    public function getModuleListeners($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['listeners'])) {
            return $modules[$moduleName]['listeners'];
        }
        return array();
    }

    /**
     * @param $moduleName
     * @return mixed|string
     */
    public function getModuleAdminMenu($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['adminMenu']) && file_exists($modules[$moduleName]['adminMenu'])) {
            return include $modules[$moduleName]['adminMenu'];
        }
        return '';
    }

    /**
     * @param $moduleName
     * @return array
     */
    public function getModuleViewHelpers($moduleName)
    {
        $modules = $this->getModules();
        if (!empty($modules[$moduleName]['viewHelpers'])) {
            return $modules[$moduleName]['viewHelpers'];
        }
        return array();
    }

    /**
     * @param Model $entity
     * @return array
     */
    public function getInjectRelations(Model $entity)
    {
        $relations = $this->injectRelations;

        if ($relations === false) {
            $relations = array();
            $modules = $this->getModules();
            foreach ($modules as $moduleName => $module) {
                if (empty($module['relations'])) {
                    continue;
                }
                foreach ($module['relations'] as $relation) {
                    $relations[$relation['entity']] = $relation;
                }
            }
            $this->injectRelations = $relations;
        }

        if (!$relations) {
            return array();
        }

        $entityRalations = array();
        foreach ($relations as $relation) {
            if ($entity instanceof $relation['entity']) {
                $entityRalations[] = $relation;
            }
        }
        return $entityRalations;
    }

    /**
     * @param null $defaultPath
     */
    public function __construct($defaultPath = null)
    {
        if ($defaultPath) {
            $this->defaultPath = $defaultPath;
        }
    }
}
