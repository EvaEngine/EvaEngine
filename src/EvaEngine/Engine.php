<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine;

use Eva\EvaEngine\CLI\Output\ConsoleOutput;
//use Eva\EvaEngine\Events\DispatchCacheListener;
use Eva\EvaEngine\Interceptor\Dispatch as DispatchInterceptor;
use Eva\EvaEngine\SDK\SendCloudMailer;
use Eva\EvaSms\Sender;
use Phalcon\CLI\Console;
use Phalcon\Mvc\Router;
use Eva\EvaEngine\Mvc\Url as UrlResolver;
use Phalcon\DI\FactoryDefault;
use Phalcon\Config;
use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Debug;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Mvc\Dispatcher;
use Eva\EvaEngine\Mvc\View;
use Eva\EvaEngine\Module\Manager as ModuleManager;
use Eva\EvaEngine\Mvc\Model\Manager as ModelManager;
use Phalcon\CLI\Router as CLIRouter;
use Phalcon\CLI\Dispatcher as CLIDispatcher;
use Phalcon\DI\FactoryDefault\CLI;
use Eva\EvaEngine\Service\TokenStorage;
use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\View\Engine\Php;

/**
 * Core application configuration / bootstrap
 *
 * The most common workflow is:
 * <code>
 * $engine = new Engine(__DIR__ . '/..');
 * $engine->loadModules(include __DIR__ . '/../config/modules.php')
 *        ->bootstrap()
 *        ->run();
 * </code>
 *
 * Class Engine
 * @package Eva\EvaEngine
 */
class Engine
{
    /**
     * @var float
     */
    public static $appStartTime;

    /**
     * @var null|string
     */
    protected $appRoot;

    /**
     * @var string
     */
    protected $appName; //for cache prefix

    /**
     * @var string
     */
    protected $modulesPath;

    /**
     * @var DiInterface
     */
    protected $di;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var bool
     */
    protected $cacheEnable = false;

    /**
     * @var string
     */
    protected $environment; //development | test | production

    /**
     * @var Debug
     */
    protected $debugger;

    /**
     * @var string
     */
    protected $appMode = 'web';

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @param $appRoot
     * @return $this
     */
    public function setAppRoot($appRoot)
    {
        $this->appRoot = $appRoot;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getAppRoot()
    {
        return $this->appRoot;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setAppName($name)
    {
        $this->appName = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setConfigPath($path)
    {
        $this->configPath = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        if ($this->configPath) {
            return $this->configPath;
        }
        return $this->configPath = $this->appRoot . '/config';
    }


    /**
     * @param $modulesPath
     * @return $this
     */
    public function setModulesPath($modulesPath)
    {
        $this->modulesPath = $modulesPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getModulesPath()
    {
        if ($this->modulesPath) {
            return $this->modulesPath;
        }
        return $this->modulesPath = $this->appRoot . '/modules';
    }


    /**
     *
     * @param $cacheFile cache file path
     * @param bool $serialize
     * @return mixed|null
     */
    public function readCache($cacheFile, $serialize = false)
    {
        if (file_exists($cacheFile) && $cache = include($cacheFile)) {
            return true === $serialize ? unserialize($cache) : $cache;
        }
        return null;
    }

    /**
     * @param $cacheFile
     * @param $content
     * @param bool $serialize
     * @return bool
     */
    public function writeCache($cacheFile, $content, $serialize = false)
    {
        if ($cacheFile && $fh = fopen($cacheFile, 'w')) {
            if (true === $serialize) {
                fwrite($fh, "<?php return '" . serialize($content) . "';");
            } else {
                fwrite($fh, '<?php return ' . var_export($content, true) . ';');
            }
            fclose($fh);
            return true;
        }
        return false;
    }

    /**
     * @return Debug
     */
    public function getDebugger()
    {
        if ($this->debugger) {
            return $this->debugger;
        }

        $debugger = new Debug();
        $debugger->setShowFileFragment(true);
        $debugger->listen(true, true);
        return $this->debugger = $debugger;
    }

    /**
     * @return string
     */
    public function getAppMode()
    {
        return $this->appMode;
    }

    /**
     * @return Console|Application
     */
    public function getApplication()
    {
        if (!$this->application) {
            if ($this->appMode == 'cli') {
                $this->application = new Console();
            } else {
                $this->application = new Application();
            }
        }

        return $this->application;
    }


    /**
     * Load modules from input settings, and call phalcon application->registerModules() for register
     *
     * below events will be trigger
     * - module:beforeLoadModule
     * - module:afterLoadModule
     *
     * @param array $moduleSettings
     * @return $this
     */
    public function loadModules(array $moduleSettings)
    {
        $moduleManager = $this->getDI()->getModuleManager();

        if ($this->getEnvironment() == 'production') {
            $cachePrefix = $this->getAppName();
            $cacheFile = $this->getConfigPath() . "/_cache.$cachePrefix.modules.php";
            $moduleManager->setCacheFile($cacheFile);
        }


        $moduleManager
            ->setDefaultPath($this->getModulesPath())
            ->loadModules($moduleSettings, $this->getAppName());

        if ($this->getDI()->getConfig()->debug) {
            $cachePrefix = $this->getAppName();
            $this->writeCache(
                $this->getConfigPath() . "/_debug.$cachePrefix.modules.php",
                $moduleManager->getModules()
            );
        }

        $this->getApplication()->registerModules($moduleManager->getModules());
        //Overwirte default modulemanager
        $this->getDI()->set('moduleManager', $moduleManager);
        return $this;
    }

    /**
     * @return $this
     */
    public function attachModuleEvents()
    {
        $di = $this->getDI();
        $cachePrefix = $this->getAppName();
        $cacheFile = $this->getConfigPath() . "/_cache.$cachePrefix.events.php";
        $listeners = $this->readCache($cacheFile);
        $cacheLoaded = false;

        if (!$listeners) {
            $moduleManager = $this->getDI()->getModuleManager();
            $modules = $moduleManager->getModules();
            $listeners = array();
            foreach ($modules as $moduleName => $module) {
                $moduleListeners = $moduleManager->getModuleListeners($moduleName);
                if ($moduleListeners) {
                    $listeners[$moduleName] = $moduleListeners;
                }
            }
        } else {
            $cacheLoaded = true;
        }

        if (!is_array($listeners)) {
            return $this;
        }

        $eventsManager = $this->getDI()->getEventsManager();
        foreach ($listeners as $moduleName => $moduleListeners) {
            if (!$moduleListeners) {
                continue;
            }
            foreach ($moduleListeners as $eventType => $listener) {
                $eventsManager->attach($eventType, new $listener);
            }
        }

        if ($di->getConfig()->debug) {
            $debugger = $this->getDebugger();
            $debugger->debugVar($listeners, 'events');
        }

        if (!$di->getConfig()->debug && false === $cacheLoaded && $listeners) {
            $this->writeCache($cacheFile, $listeners);
        }
        return $this;
    }


    /**
     * @return $this
     */
    public function registerViewHelpers()
    {
        $di = $this->getDI();
        $cachePrefix = $this->getAppName();
        $cacheFile = $this->getConfigPath() . "/_cache.$cachePrefix.helpers.php";
        $helpers = $this->readCache($cacheFile);
        if ($helpers) {
            Tag::registerHelpers($helpers);
            return $this;
        }

        $helpers = array();
        $moduleManager = $di->getModuleManager();
        $modules = $moduleManager->getModules();
        foreach ($modules as $moduleName => $module) {
            $moduleHelpers = $moduleManager->getModuleViewHelpers($moduleName);
            if (is_array($moduleHelpers)) {
                $helpers += $moduleHelpers;
            }
        }
        Tag::registerHelpers($helpers);

        if (!$di->getConfig()->debug && $helpers) {
            $this->writeCache($cacheFile, $helpers);
        }
        return $this;
    }


    /**
     * @param DiInterface $di
     * @return $this
     */
    public function setDI(DiInterface $di)
    {
        $this->di = $di;
        return $this;
    }

    /**
     * Configuration application default DI
     *
     * @return FactoryDefault| CLI
     */
    public function getDI()
    {
        if ($this->di) {
            return $this->di;
        }
        if ($this->appMode == 'cli') {
            $di = new FactoryDefault\CLI();
        } else {
            $di = new FactoryDefault();
        }

        //PHP5.3 not support $this in closure
        $self = $this;

        /**********************************
         * DI initialize for MVC core
         ***********************************/
        //$di->set('application', $this);

        //call loadmodules will overwrite this
        $di->set(
            'moduleManager',
            function () use ($di) {
                $moduleManager = new ModuleManager();
                $moduleManager->setEventsManager($di->getEventsManager());
                return $moduleManager;
            },
            true
        );

        //System global events manager
        $di->set(
            'eventsManager',
            function () use ($di) {
                $eventsManager = new EventsManager();
                $eventsManager->enablePriorities(true);
                // dispatch caching event handler
                $eventsManager->attach(
                    "dispatch",
                    new DispatchInterceptor(),
                    -1
                );
                $eventsManager->enablePriorities(true);
                return $eventsManager;
            },
            true
        );

        $di->set(
            'config',
            function () use ($self) {
                return $self->diConfig();
            },
            true
        );

        $di->set(
            'router',
            function () use ($self) {
                return $self->diRouter();
            },
            true
        );

        $di->set(
            'dispatcher',
            function () use ($di) {
                $dispatcher = new Dispatcher();
                $dispatcher->setEventsManager($di->getEventsManager());
                return $dispatcher;
            },
            true
        );

        $di->set(
            'modelsMetadata',
            function () use ($self) {
                return $self->diModelsMetadata();
            },
            true
        );

        $di->set(
            'modelsManager',
            function () use ($di) {
                $config = $di->getConfig();
                ModelManager::setDefaultPrefix($config->dbAdapter->prefix);
                //for solving db master/slave under static find method
                $modelsManager = new ModelManager();

                return $modelsManager;
            }
        );

        $di->set(
            'view',
            function () use ($di) {
                $view = new View();
                $view->setViewsDir(__DIR__ . '/views/');
                $view->setEventsManager($di->getEventsManager());
                $view->registerEngines(
                    array(
                        ".volt" => 'volt',
                        ".phtml" => 'Phalcon\Mvc\View\Engine\Php'
                    )
                );
                return $view;
            },
            true
        );

        $di->set(
            'session',
            function () use ($self) {
                return $self->diSession();
            }
        );

        $di->set(
            'tokenStorage',
            function () use ($self) {
                return $self->diTokenStorage();
            },
            true //token Storage MUST set as shared
        );

        /**********************************
         * DI initialize for database
         ***********************************/
        $di->set(
            'dbMaster',
            function () use ($self) {
                return $self->diDbMaster();
            },
            true
        );

        $di->set(
            'dbSlave',
            function () use ($self) {
                return $self->diDbSlave();
            },
            true
        );

        $di->set(
            'transactions',
            function () use ($di) {
                $transactions = new \Phalcon\Mvc\Model\Transaction\Manager();
                $transactions->setDbService('dbMaster');
                return $transactions;
            },
            true
        );

        /**********************************
         * DI initialize for cache
         ***********************************/
        $di->set(
            'globalCache',
            function () use ($self) {
                return $self->diGlobalCache();
            },
            true
        );

        $di->set(
            'viewCache',
            function () use ($self) {
                return $self->diViewCache();
            },
            true
        );

        $di->set(
            'modelsCache',
            function () use ($self) {
                return $self->diModelsCache();
            },
            true
        );

        $di->set(
            'apiCache',
            function () use ($self) {
                return $self->diApiCache();
            },
            true
        );

        $di->set(
            'fastCache',
            function () use ($self) {
                return $self->diFastCache();
            },
            true
        );

        /**********************************
         * DI initialize for queue
         ***********************************/
        $di->set(
            'queue',
            function () use ($di) {
                $config = $di->getConfig();
                $client = new \GearmanClient();
                $client->setTimeout(1000);
                foreach ($config->queue->servers as $key => $server) {
                    $client->addServer($server->host, $server->port);
                }
                return $client;
            },
            true
        );

        $di->set(
            'worker',
            function () use ($di) {
                $config = $di->getConfig();
                $worker = new \GearmanWorker();
                foreach ($config->queue->servers as $key => $server) {
                    $worker->addServer($server->host, $server->port);
                }
                return $worker;
            },
            true
        );


        /**********************************
         * DI initialize for email
         ***********************************/
        $di->set(
            'mailer',
            function () use ($self) {
                return $self->diMailer();
            },
            true
        );

        $di->set('mailMessage', 'Eva\EvaEngine\MailMessage');

        $di->set(
            'smsSender',
            function () use ($self) {
                return $self->diSmsSender();
            },
            true
        );
        /**********************************
         * DI initialize for helpers
         ***********************************/
        $di->set(
            'url',
            function () use ($di) {
                $config = $di->getConfig();
                $url = new UrlResolver();
                $url->setVersionFile($config->staticBaseUriVersionFile);
                $url->setBaseUri($config->baseUri);
                $url->setStaticBaseUri($config->staticBaseUri);
                return $url;
            },
            true
        );

        $di->set('escaper', 'Phalcon\Escaper');

        $di->set(
            'tag',
            function () use ($di, $self) {
                Tag::setDi($di);
                $self->registerViewHelpers();
                return new Tag();
            }
        );

        $di->set('flash', 'Phalcon\Flash\Session');

        $di->set('placeholder', 'Eva\EvaEngine\View\Helper\Placeholder');

        $di->set(
            'cookies',
            function () {
                $cookies = new \Phalcon\Http\Response\Cookies();
                $cookies->useEncryption(false);
                return $cookies;
            }
        );

        $di->set(
            'translate',
            function () use ($self) {
                return $self->diTranslate();
            }
        );

        $di->set(
            'fileSystem',
            function () use ($self) {
                return $self->diFileSystem();
            }
        );

        $di->set(
            'volt',
            function () use ($self) {
                return $self->diVolt();
            },
            true
        );

        $di->set(
            'logException',
            function () use ($di) {
                $config = $di->getConfig();
                return new FileLogger($config->logger->path . 'error.log');
            }
        );
        if ($this->appMode == 'cli') {
            $this->cliDI($di);
        }

        IoC::setDI($di);

        return $this->di = $di;
    }

    /**
     * CLI 模式下的 DI 配置
     *
     * @param CLI $di
     */
    protected function cliDI(CLI $di)
    {
        global $argv;

        $di->set(
            'router',
            function () use ($di, $argv) {
                $router = new CLIRouter();
                $router->setDI($di);
                return $router;
            }
        );

        $di->set(
            'output',
            function () {
                return new ConsoleOutput();
            }
        );
        $di->set(
            'dispatcher',
            function () use ($di, $argv) {
                $dispatcher = new CLIDispatcher();
                $dispatcher->setDI($di);

                $moduleName = array_shift($argv);
                $taskName = array_shift($argv);
                $actionName = 'main';
                if (strpos($taskName, ':') > 0) {
                    @list($taskName, $actionName) = preg_split("/:/", $taskName);
                }
                if ($moduleName) {
                    $dispatcher->setTaskName(ucwords($taskName));
                    $dispatcher->setActionName($actionName);
                    $dispatcher->setParams($argv);
                    if ($moduleName == '_current') {
                        $_appName = ucwords($this->getAppName());
                        $dispatcher->setNamespaceName("{$_appName}\\Tasks");
                    } else {
                        $dispatcher->setModuleName($moduleName);
                        // Use module's Tasks namespace
                        $module = $di->getModuleManager()->getModule($moduleName);
                        $className = $module['className'];
                        $taskNameSpace = str_replace('Module', 'Tasks', $className);
                        $dispatcher->setNamespaceName($taskNameSpace);
                        //$dispatcher->setNamespaceName("Eva\\{$moduleName}\\Tasks");
                    }
                } else {
                    $dispatcher->setTaskName('Main');
                    $dispatcher->setParams($argv);
                    $dispatcher->setNamespaceName("Eva\\EvaEngine\\Tasks");
                }
                return $dispatcher;
            }
        );
    }

    public function diConfig()
    {
        $di = $this->getDI();
        $cachePrefix = $this->getAppName();
        $cacheFile = $this->getConfigPath() . "/_cache.$cachePrefix.config.php";
        if ($cache = $this->readCache($cacheFile)) {
            return new Config($cache);
        }

        $config = new Config();
        //merge all loaded module configs
        $moduleManager = $di->getModuleManager();
        if (!$moduleManager || !$modules = $moduleManager->getModules()) {
            throw new Exception\RuntimeException(sprintf('Config need at least one module loaded'));
        }

        foreach ($modules as $moduleName => $module) {
            $moduleConfig = $moduleManager->getModuleConfig($moduleName);
            if ($moduleConfig instanceof Config) {
                $config->merge($moduleConfig);
            } else {
                $config->merge(new Config($moduleConfig));
            }
        }

        //merge config default
        $config->merge(new Config(include $this->getConfigPath() . "/config.default.php"));

        //merge config local
        if (false === file_exists($this->getConfigPath() . "/config.local.php")) {
            return $config;
        }
        $config->merge(new Config(include $this->getConfigPath() . "/config.local.php"));

        if (!$config->debug) {
            $this->writeCache($cacheFile, $config->toArray());
        }
        return $config;
    }

    public function diRouter()
    {
        $di = $this->getDI();
        $cachePrefix = $this->getAppName();
        $cacheFile = $this->getConfigPath() . "/_cache.$cachePrefix.router.php";
        if ($router = $this->readCache($cacheFile, true)) {
            return $router;
        }

        $moduleManager = $di->getModuleManager();
        $config = new Config();
        $moduleName = '';
        if ($moduleManager && $modulesArray = $moduleManager->getModules()) {
            foreach ($modulesArray as $moduleName => $module) {
                //NOTICE: EvaEngine Load front-end router at last
                $config->merge(new Config($moduleManager->getModuleRoutesFrontend($moduleName)));
                $config->merge(new Config($moduleManager->getModuleRoutesBackend($moduleName)));
            }
        }

        //Disable default router
        $router = new Router(false);
        //Last extra slash
        $router->removeExtraSlashes(true);
        //Set last module as default module
        $router->setDefaultModule($moduleName);
        //NOTICE: Set a strange controller here to make router not match default index/index
        $router->setDefaultController('EvaEngineDefaultController');

        $config = $config->toArray();
        foreach ($config as $url => $route) {
            if (count($route) !== count($route, COUNT_RECURSIVE)) {
                if (isset($route['pattern']) && isset($route['paths'])) {
                    $method = isset($route['httpMethods']) ? $route['httpMethods'] : null;
                    $router->add($route['pattern'], $route['paths'], $method);
                } else {
                    throw new Exception\RuntimeException(sprintf('No route pattern and paths found by route %s', $url));
                }
            } else {
                $router->add($url, $route);
            }
        }

        if (!$di->getConfig()->debug) {
            $this->writeCache($cacheFile, $router, true);
        } else {
            //Dump merged routers for debug
            $this->writeCache($this->getConfigPath() . "/_debug.$cachePrefix.router.php", $router, true);
        }
        return $router;
    }

    public function diModelsMetadata()
    {
        $adapterMapping = array(
            'apc' => 'Phalcon\Mvc\Model\MetaData\Apc',
            'files' => 'Phalcon\Mvc\Model\MetaData\Files',
            'memory' => 'Phalcon\Mvc\Model\MetaData\Memory',
            'xcache' => 'Phalcon\Mvc\Model\MetaData\Xcache',
            'memcache' => 'Phalcon\Mvc\Model\MetaData\Memcache',
            'redis' => 'Phalcon\Mvc\Model\MetaData\Redis',
            'wincache' => 'Phalcon\Mvc\Model\MetaData\Wincache',
        );

        $config = $this->getDI()->getConfig();
        if (!$config->modelsMetadata->enable) {
            return new \Phalcon\Mvc\Model\MetaData\Memory();
        }

        $adapterKey = $config->modelsMetadata->adapter;
        $adapterKey = false === strpos($adapterKey, '\\') ? strtolower($adapterKey) : $adapterKey;
        //Allow full class name as adapter name
        $adapterClass = empty($adapterMapping[$adapterKey]) ? $adapterKey : $adapterMapping[$adapterKey];
        if (!class_exists($adapterClass)) {
            throw new Exception\RuntimeException(sprintf('No metadata adapter found by %s', $adapterClass));
        }
        return new $adapterClass($config->modelsMetadata->options->toArray());
    }

    public function diDbMaster()
    {
        $config = $this->getDI()->getConfig();
        if (!isset($config->dbAdapter->master->adapter) || !$config->dbAdapter->master) {
            throw new Exception\RuntimeException(sprintf('No DB Master options found'));
        }
        return $this->diDbAdapter($config->dbAdapter->master->adapter, $config->dbAdapter->master->toArray());
    }

    public function diDbSlave()
    {
        $config = $this->getDI()->getConfig();
        $slaves = $config->dbAdapter->slave;
        $slaveKey = array_rand($slaves->toArray());
        if (!isset($slaves->$slaveKey) || count($slaves) < 1) {
            throw new Exception\RuntimeException(sprintf('No DB slave options found'));
        }
        return $this->diDbAdapter($slaves->$slaveKey->adapter, $slaves->$slaveKey->toArray());
    }


    protected function diDbAdapter($adapterKey, array $options)
    {
        $adapterKey = false === strpos($adapterKey, '\\') ? strtolower($adapterKey) : $adapterKey;
        $adapterMapping = array(
            'mysql' => 'Phalcon\Db\Adapter\Pdo\Mysql',
            'oracle' => 'Phalcon\Db\Adapter\Pdo\Oracle',
            'postgresql' => 'Phalcon\Db\Adapter\Pdo\Postgresql',
            'sqlite' => 'Phalcon\Db\Adapter\Pdo\Sqlite',
        );

        $adapterClass = empty($adapterMapping[$adapterKey]) ? $adapterKey : $adapterMapping[$adapterKey];

        if (false === class_exists($adapterClass)) {
            throw new Exception\RuntimeException(sprintf('No matched DB adapter found by %s', $adapterClass));
        }

        $options['charset'] = isset($options['charset']) && $options['charset'] ? $options['charset'] : 'utf8';
        $dbAdapter = new $adapterClass($options);


        $config = $this->getDI()->getConfig();

        if ($config->debug) {
            $di = $this->getDI();
            $eventsManager = $di->getEventsManager();
            $logger = new FileLogger($config->logger->path . 'db_query.log');

            //database service name hardcore as db
            $eventsManager->attach(
                'db',
                function ($event, $dbAdapter) use ($logger) {
                    if ($event->getType() == 'beforeQuery') {
                        $sqlVariables = $dbAdapter->getSQLVariables();
                        if (count($sqlVariables)) {
                            $query = str_replace(array('%', '?'), array('%%', "'%s'"), $dbAdapter->getSQLStatement());
                            $query = vsprintf($query, $sqlVariables);
                            //
                            $logger->log($query, \Phalcon\Logger::INFO);
                        } else {
                            $logger->log($dbAdapter->getSQLStatement(), \Phalcon\Logger::INFO);
                        }
                    }
                }
            );
            $dbAdapter->setEventsManager($eventsManager);
        }
        return $dbAdapter;
    }

    public function diGlobalCache()
    {
        return $this->diCache('globalCache', $this->getAppName() . '_global_');
    }

    public function diViewCache()
    {
        return $this->diCache('viewCache', $this->getAppName() . '_view_');
    }

    public function diModelsCache()
    {
        return $this->diCache('modelsCache', $this->getAppName() . '_models_');
    }

    public function diApiCache()
    {
        return $this->diCache('apiCache', $this->getAppName() . '_api_');
    }

    public function diFastCache()
    {
        $config = $this->getDI()->getConfig();
        if (!($config->cache->fastCache->enable)) {
            return false;
        }

        $redis = new \Redis();
        $redis->connect(
            $config->cache->fastCache->host,
            $config->cache->fastCache->port,
            $config->cache->fastCache->timeout
        );
        return $redis;
    }

    protected function diCache($configKey, $prefix = 'eva_')
    {
        $config = $this->getDI()->getConfig();
        $adapterMapping = array(
            'apc' => 'Phalcon\Cache\Backend\Apc',
            'file' => 'Phalcon\Cache\Backend\File',
            'libmemcached' => 'Phalcon\Cache\Backend\Libmemcached',
            'memcache' => 'Phalcon\Cache\Backend\Memcache',
            'memory' => 'Phalcon\Cache\Backend\Memory',
            'mongo' => 'Phalcon\Cache\Backend\Mongo',
            'xcache' => 'Phalcon\Cache\Backend\Xcache',
            'redis' => 'Phalcon\Cache\Backend\Redis',
            'wincache' => 'Phalcon\Cache\Backend\Wincache',
            'base64' => 'Phalcon\Cache\Frontend\Base64',
            'data' => 'Phalcon\Cache\Frontend\Data',
            'igbinary' => 'Phalcon\Cache\Frontend\Igbinary',
            'json' => 'Phalcon\Cache\Frontend\Json',
            'none' => 'Phalcon\Cache\Frontend\None',
            'output' => 'Phalcon\Cache\Frontend\Output',
        );

        $frontCacheClassName = $config->cache->$configKey->frontend->adapter;
        $frontCacheClassName = false === strpos($frontCacheClassName, '\\') ? strtolower($frontCacheClassName) : $frontCacheClassName;
        $frontCacheClass = empty($adapterMapping[$frontCacheClassName]) ? $frontCacheClassName : $adapterMapping[$frontCacheClassName];
        if (false === class_exists($frontCacheClass)) {
            throw new Exception\RuntimeException(sprintf('No cache adapter found by %s', $frontCacheClass));
        }
        $frontCache = new $frontCacheClass(
            $config->cache->$configKey->frontend->options->toArray()
        );

        if (!$config->cache->enable || !$config->cache->$configKey->enable) {
            $cache = new \Eva\EvaEngine\Cache\Backend\Disable($frontCache);
        } else {
            $backendCacheClassName = $config->cache->$configKey->backend->adapter;
            $backendCacheClassName = false === strpos($backendCacheClassName, '\\') ? strtolower($backendCacheClassName) : $backendCacheClassName;
            $backendCacheClass =
                !empty($adapterMapping[$backendCacheClassName])
                    ? $adapterMapping[$backendCacheClassName]
                    : $backendCacheClassName;

            if (!class_exists($backendCacheClass)) {
                throw new Exception\RuntimeException(sprintf('No cache adapter found by %s', $backendCacheClassName));
            }
            $cache = new $backendCacheClass($frontCache, array_merge(
                array(
                    'prefix' => $prefix,
                ),
                $config->cache->$configKey->backend->options->toArray()
            ));
        }
        return $cache;
    }

    public function diSmsSender()
    {
        $config = $this->getDI()->getConfig();
        $adapterMapping = array(
            'submail' => 'Eva\EvaSms\Providers\Submail',
        );
        $adapterKey = $config->smsSender->provider;
        $adapterKey = false === strpos($adapterKey, '\\') ? strtolower($adapterKey) : $adapterKey;
        $adapterClass = empty($adapterMapping[$adapterKey]) ? $adapterKey : $adapterMapping[$adapterKey];
        if (false === class_exists($adapterClass)) {
            throw new Exception\RuntimeException(sprintf('No sms provider found by %s', $adapterClass));
        }

        $sender = new Sender();
        $sender->setProvider(new $adapterClass($config->smsSender->appid, $config->smsSender->appkey));
        if ($config->smsSender->timeout) {
            $sender::setDefaultTimeout($config->smsSender->timeout);
        }

        return $sender;
    }

    public function diMailer()
    {
        $config = $this->getDI()->getConfig();
        if ($config->mailer->transport == 'smtp') {
            $transport = \Swift_SmtpTransport::newInstance()
                ->setHost($config->mailer->host)
                ->setPort($config->mailer->port)
                ->setEncryption($config->mailer->encryption)
                ->setUsername($config->mailer->username)
                ->setPassword($config->mailer->password);
        } else {
            $transport = \Swift_SendmailTransport::newInstance($config->mailer->sendmailCommand);
        }
        if ($config->mailer->transport == 'sendcloud') {
            $mailer = SendCloudMailer::newInstance()
                ->setHost($config->mailer->host)
                ->setUsername($config->mailer->username)
                ->setPassword($config->mailer->password);
        } else {
            $mailer = \Swift_Mailer::newInstance($transport);
        }
        return $mailer;
    }

    public function diSession()
    {
        $adapterMapping = array(
            'files' => 'Phalcon\Session\Adapter\Files',
            'database' => 'Phalcon\Session\Adapter\Database',
            'memcache' => 'Phalcon\Session\Adapter\Memcache',
            'libmemcached' => 'Eva\EvaEngine\Session\Adapter\Libmemcached',
            'mongo' => 'Phalcon\Session\Adapter\Mongo',
            'redis' => 'Phalcon\Session\Adapter\Redis',
            'handlersocket' => 'Phalcon\Session\Adapter\HandlerSocket',
        );

        $config = $this->getDI()->getConfig();
        $adapterKey = $config->session->adapter;
        $adapterKey = false === strpos($adapterKey, '\\') ? strtolower($adapterKey) : $adapterKey;
        $sessionClass = empty($adapterMapping[$adapterKey]) ? $adapterKey : $adapterMapping[$adapterKey];
        if (false === class_exists($sessionClass)) {
            throw new Exception\RuntimeException(sprintf('No session adapter found by %s', $sessionClass));
        }

        $session = new $sessionClass(array_merge(
            array(
                'uniqueId' => $this->getAppName(),
            ),
            $config->session->options->toArray()
        ));
        if (!$session->isStarted()) {
            //NOTICE: Get php warning here, not found reason
            @$session->start();
        }
        return $session;
    }

    public function diTokenStorage()
    {
        $config = $this->getDI()->getConfig();
        return new TokenStorage(array_merge(
            array(
                'uniqueId' => $this->getAppName(),
            ),
            $config->tokenStorage->toArray()
        ));
    }

    public function diTranslate()
    {
        $config = $this->getDI()->getConfig();
        $file = $config->translate->path . $config->translate->forceLang . '.csv';
        if (false === file_exists($file)) {
            //empty translator
            return new \Phalcon\Translate\Adapter\NativeArray(array(
                'content' => array()
            ));
        }
        $translate = new \Phalcon\Translate\Adapter\Csv(array(
            'file' => $file,
            'delimiter' => ',',
        ));
        return $translate;
    }

    public function diFileSystem()
    {
        $config = $this->getDI()->getConfig();
        $adapterClass = $config->filesystem->default->adapter;

        $adapter = new $adapterClass($config->filesystem->default->uploadPath);
        $filesystem = new \Gaufrette\Filesystem($adapter);
        return $filesystem;
    }

    public function diVolt()
    {
        $di = $this->getDI();
        $config = $di->getConfig();
        $volt = new Volt($di->getView(), $di);
        $volt->setOptions(
            array(
                "compiledPath" => $config->templateEngine->volt->compiledPath,
                "compiledSeparator" => $config->templateEngine->volt->compiledSeparator,
                "compileAlways" => $config->debug
            )
        );
        $compiler = $volt->getCompiler();
        $compiler->addFunction('number_format', function ($resolvedArgs) {
            return 'number_format(' . $resolvedArgs . ')';
        });
        $compiler->addFunction('_', function ($resolvedArgs) {
            return 'Eva\EvaEngine\Tag::translate(' . $resolvedArgs . ')';
        });
        return $volt;
    }

    /**
     * Application Bootstrap, init DI, register Modules, init events, init ErrorHandler
     * @return $this
     */
    public function bootstrap()
    {
        if ($this->getDI()->getConfig()->debug) {
            $debugger = $this->getDebugger();
            $debugger->debugVar($this->getDI()->getModuleManager()->getModules(), 'modules');
        }
        $this->getApplication()
            ->setDI($this->getDI());
        $this->getApplication()
            ->setEventsManager($this->getDI()->getEventsManager());
        $this->attachModuleEvents();
        //Error Handler must run before router start
        if ($this->appMode == 'cli') {
            $this->initErrorHandler(new Error\CLIErrorHandler());
        } else {
            $this->initErrorHandler(new Error\ErrorHandler);
        }
        return $this;
    }

    /**
     * Run application
     */
    public function run()
    {
        $response = $this->getApplication()->handle();
        echo $response->getContent();
    }

    /**
     * Register default error handler
     * @param Error\ErrorHandlerInterface $errorHandler
     * @return $this
     */
    public function initErrorHandler(Error\ErrorHandlerInterface $errorHandler)
    {
        $this->getDI()->getEventsManager()->attach(
            'dispatch:beforeException',
            function ($event, $dispatcher, $exception) {
                //For fixing phalcon weird behavior https://github.com/phalcon/cphalcon/issues/2558
                throw $exception;
            }
        );

        if ($this->getDI()->getConfig()->debug && $this->appMode != 'cli') {
            return $this;
        }

        $errorClass = get_class($errorHandler);
        set_error_handler("$errorClass::errorHandler");
        set_exception_handler("$errorClass::exceptionHandler");
        register_shutdown_function("$errorClass::shutdownHandler");
        return $this;
    }


    /**
     * A custum version for Application->run()
     * WARNING: This method not able to replace phalcon default run()
     */
    public function runCustom()
    {
        if ($this->appMode == 'cli') {
            return;
        }
        $di = $this->getDI();

        //$debug = $di->getConfig()->debug;
        /*
        if ($debug) {
            $debugger = $this->getDebugger();
        }
        */

        //Roter
        $router = $di['router'];
        $router->handle();

        //Module handle
        $modules = $this->getApplication()->getModules();
        $routeModule = $router->getModuleName();
        if (isset($modules[$routeModule])) {
            $moduleClass = new $modules[$routeModule]['className']();
            $moduleClass->registerAutoloaders();
            $moduleClass->registerServices($di);
        }

        //dispatch
        $dispatcher = $di['dispatcher'];
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($router->getParams());

        //view
        $view = $di['view'];
        $view->start();
        $dispatcher->dispatch();
        //Not able to call render in controller or else will repeat output
        $view->render(
            $dispatcher->getControllerName(),
            $dispatcher->getActionName(),
            $dispatcher->getParams()
        );
        $view->finish();

        //NOTICE: not able to output flash session content
        $response = $di['response'];
        $response->setContent($view->getContent());
        $response->sendHeaders();
        echo $response->getContent();
    }


    /**
     * Constructor
     *
     * @param string Application root path
     * @param string Application name for some cache prefix
     * @param string Application mode, 'cli' or 'web' , 'cli' for CLI mode
     */
    public function __construct($appRoot = null, $appName = 'evaengine', $appMode = 'web')
    {
        self::$appStartTime = microtime(true);
        $this->appRoot = $appRoot ? $appRoot : __DIR__;
        $this->appName = empty($_SERVER['APPLICATION_NAME']) ? $appName : $_SERVER['APPLICATION_NAME'];
        $this->environment = empty($_SERVER['APPLICATION_ENV']) ? 'development' : $_SERVER['APPLICATION_ENV'];

        $appMode = strtolower($appMode);
        $this->appMode = in_array($appMode, array('web', 'cli')) ? $appMode : 'web';
    }
}
