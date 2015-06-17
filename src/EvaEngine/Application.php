<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/12 下午4:44
// +----------------------------------------------------------------------
// + Application.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine;


use Eva\EvaEngine\Exception\RuntimeException;
use Eva\EvaEngine\Module\Manager;
use Phalcon\Cache\Backend\File;
use Phalcon\Cache\Frontend\Data;
use Phalcon\DI\FactoryDefault;

class Application
{
    protected $project_root = __DIR__;
    protected $mode = 'web';
    protected $di;
    protected static $instance = null;
    protected $appName;
    /**
     * @var \Phalcon\Mvc\Application
     */
    protected $application;

    protected function __construct($project_root = __DIR__, $mode = 'web', $appName = '', $systemCache = null)
    {
        $this->project_root = $project_root;
        $this->appName = empty($_SERVER['APPLICATION_NAME']) ? $appName : $_SERVER['APPLICATION_NAME'];
        $this->mode = $mode;
        $this->di = new FactoryDefault();
        $this->di->setShared('evaengine', $this);
        if ($systemCache == null) {
            $systemCache = function () {
                return new File(new Data(), array(
                    'cacheDir' => $this->getProjectRoot() . '/cache/system/',
                    'prefix' => $this->getAppName() . '/'
                ));
            };
        }
        $this->di->setShared('systemCache', $systemCache);
    }

    public function loadModules(array $modulesConfig)
    {
        $moduleManager = new Manager($this->getProjectRoot() . '/modules', $modulesConfig);
        $moduleManager->registerModules($modulesConfig);
        $this->di->set('moduleManager', $moduleManager);
        $this->application->registerModules($moduleManager->getModulesForPhalcon());
    }

    public function initialize()
    {
        // 注册 DI
        $config = eva_get('config');
        foreach ($config->di as $name => $diDefinition) {
            $this->di->set($name, $diDefinition);
        }
        $this->registerServices();
        $this->initializeEvents();
        $this->application->setDI($this->di);
        $this->application->setEventsManager(eva_get('eventsManager'));
    }

    protected function registerServices()
    {
        /** @var \Eva\EvaEngine\Module\Manager $moduleManager */
        $moduleManager = eva_get('moduleManager');
        foreach ($moduleManager->getAllDIDefinition() as $name => $definition) {
            $this->di->set($name, $definition['definition'], $definition['share']);
        }
    }

    protected function initializeEvents()
    {
        $eventsManager = eva_get('eventsManager');
        /** @var \Eva\EvaEngine\Module\Manager $moduleManager */
        $moduleManager = eva_get('moduleManager');
        foreach ($moduleManager->getAllListeners() as $moduleListeners) {
            foreach ($moduleListeners as $eventType => $listener) {
                $priority = 0;
                if (is_string($listener)) {
                    $listenerClass = $listener;
                } elseif (true === is_array($listener) && count($listener) > 1) {
                    $listenerClass = $listener[0];
                    $priority = (int)$listener[1];
                } else {
                    throw new \Exception(sprintf(
                        "Module listener format not correct: %s",
                        var_export($listener, true)
                    ));
                }
                if (false === class_exists($listenerClass)) {
                    throw new \Exception(sprintf("Module listener %s not exist", $listenerClass));
                }
                $eventsManager->attach($eventType, new $listenerClass, $priority);
            }
        }
    }

    public function getProjectRoot()
    {
        return $this->project_root;
    }

    public function getAppName()
    {
        return $this->appName;
    }

    public static function getInstance($app_root = __DIR__, $mode = 'web')
    {
        if (static::$instance == null) {
            static::$instance = new static($app_root, $mode);
        }

        return static::$instance;
    }


    public function getDI()
    {
        return $this->di;
    }

}