<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Module;

use Phalcon\Events\ManagerInterface;
use Phalcon\Loader;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\Manager as EventsManager;
use Eva\EvaEngine\Mvc\Model;

/**
 * Module Manager for module register / load
 *
 * A standard module file structure is:
 * - config | module config files
 * - src    | module source codes
 * - views  | module view files
 * - tests  | module test files
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
        return $this->eventsManager = new EventsManager();
    }

    /**
     * @param ManagerInterface $eventsManager
     * @return ManagerInterface
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        return $this->eventsManager = $eventsManager;
    }

    /**
     * @param $cacheFile
     * @return mixed|null
     */
    public function readCache($cacheFile)
    {
        if (file_exists($cacheFile) && $cache = include $cacheFile) {
            return $cache;
        }
        return null;
    }

    /**
     * @param $cacheFile
     * @param array     $content
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
     * @param $moduleName
     * @return array
     */
    public function getModule($moduleName)
    {
        return empty($this->modules[$moduleName]) ? array() : $this->modules[$moduleName];
    }

    /**
     * Get full module settings by module name or module setting array
     * Module setting includes:
     * - className:     Required | Module bootstrap class full name, e.g. Eva\EvaCommon\Module
     * - path :         Required | Module.php file path, e.g. /www/eva/modules/EvaCommon/Module.php
     * - moduleConfig:  Optional | Module config file path, e.g. /www/eva/modules/EvaCommon/config/config.php ,
     *                              default is module_dir/config/config.php
     * - routesFrontend Optional | Module front-end router config file path,
     *                              e.g. /www/eva/modules/EvaCommon/config/routes.frontend.php
     * - routesBackend  Optional | Module back-end router config file path,
     *                              e.g. /www/eva/modules/EvaCommon/config/routes.backend.php
     * - routesCommand  Optional | Module cli router config file path,
     *                              e.g. /www/eva/modules/EvaCommon/config/routes.command.php
     * - adminMenu      Optional | Admin sidebar menu
     * - di :           Optional | Module global DI
     * All optional setting could set as false to disable.
     *
     * @param  $moduleName    MUST as same as module folder, keep moduleName unique
     * @param  $moduleSetting mixed Support 3 types:
     *            - null, EvaEngine official module, module namespace MUST start with Eva\\,
     *              and source path MUST under application/modules
     *            - string, Full module class, which is already loaded by composer
     *            - array, Module setting array, require className and path at least, other options will be auto filled
     * @return array Module
     * @throws \Exception
     */
    public function getModuleInfo($moduleName, $moduleSetting = null)
    {
        $moduleName = ucfirst($moduleName);
        $modulesPath = $this->getDefaultPath();
        $ds = DIRECTORY_SEPARATOR;

        //Get basic module info only contains className and path
        if (true === is_null($moduleSetting)) {
            $module = array(
                'className' => "Eva\\$moduleName\\Module",
                'path' => "$modulesPath{$ds}$moduleName{$ds}Module.php",
            );
        } elseif (true === is_string($moduleSetting) && strpos($moduleSetting, '\\') !== false) {
            //Composer module
            $moduleClass = $moduleSetting;
            if (false === class_exists($moduleClass)) {
                throw new \Exception(sprintf('Module %s load failed by not exist class', $moduleClass));
            }

            $ref = new \ReflectionClass($moduleClass);
            $module = array(
                'className' => $moduleClass,
                'path' => $ref->getFileName(),
            );
        } elseif (true === is_array($moduleSetting)) {
            $module = array_merge(
                array(
                'className' => '',
                'path' => '',
                ),
                $moduleSetting
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
                'Phalcon\Mvc\ModuleDefinitionInterface',
                'Eva\EvaEngine\Module\StandardInterface'
                ),
                class_implements($moduleClass)
            )
        ) !== 2) {
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

        $modules = array();
        //All Module.php map array for cache
        $classes = array();
        foreach ($moduleSettings as $moduleName => $moduleSetting) {
            if (true === is_int($moduleName)) {
                $moduleName = $moduleSetting;
                $moduleSetting = null;
            }
            $module = $this->getModuleInfo($moduleName, $moduleSetting);
            $modules[$moduleName] = $module;
            $classes[$module['className']] = $module['path'];
        }
        $this->modules = $modules;

        $namespaces = $this->getMergedAutoloaders();
        if ($namespaces) {
            $loader->registerNamespaces($namespaces)->register();
        }

        if ($cacheFile) {
            $this->writeCache(
                $cacheFile,
                array(
                'classes' => $classes,
                'namespaces' => $namespaces,
                'modules' => $modules,
                )
            );
        }
        //Trigger Event
        $this->getEventsManager()->fire('module:afterLoadModule', $this);
        return $this;
    }

    /**
     * @param $key
     * @return array
     */
    private function getMergedArray($key)
    {
        if (!($modules = $this->modules)) {
            return array();
        }
        $mergedArray = array();
        foreach ($modules as $moduleName => $module) {
            if (false === is_array($module[$key])) {
                continue;
            }
            $mergedArray = array_merge($mergedArray, $module[$key]);
        }
        return $mergedArray;
    }

    /**
     * @return array
     */
    public function getMergedAutoloaders()
    {
        return $this->getMergedArray('autoloaders');
    }

    /**
     * @return array
     */
    public function getMergedViewHelpers()
    {
        return $this->getMergedArray('viewHelpers');
    }

    /**
     * @return array
     */
    public function getMergedRelations()
    {
        return $this->getMergedArray('relations');
    }


    /**
     * Module Events could by key => value pairs like:
     * 'module' => 'Eva\RealModule\Events\ModuleListener',
     * Or could be key => array pairs like
     * 'module' => array('Eva\RealModule\Events\ModuleListener', 100)
     * Array[1] is listener priority
     *
     * @param  array $listeners
     * @return $this
     * @throws \Exception
     */
    public function attachEvents(array $listeners = array())
    {
        if (!$listeners) {
            $modules = $this->getModules();
            if (!$modules) {
                return $this;
            }

            $listeners = array();
            foreach ($modules as $moduleName => $module) {
                $listeners[$moduleName] = $this->getModuleListeners($moduleName);
            }
        }

        if (!$listeners) {
            return $this;
        }

        $eventsManager = $this->getEventsManager();
        foreach ($listeners as $moduleName => $moduleListeners) {
            foreach ($moduleListeners as $eventType => $listener) {
                $priority = 0;
                if (is_string($listener)) {
                    $listenerClass = $listener;
                } elseif (true === is_array($listener) && count($listener) > 1) {
                    $listenerClass = $listener[0];
                    $priority = (int) $listener[1];
                } else {
                    throw new \Exception(sprintf("Module %s listener format not correct", $moduleName));
                }
                if (false === class_exists($listenerClass)) {
                    throw new \Exception(sprintf("Module listener %s not exist", $listenerClass));
                }
                $eventsManager->attach($eventType, new $listenerClass, $priority);
            }
        }
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
            $relations = $this->getMergedRelations();
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
