<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/12 下午4:44
// +----------------------------------------------------------------------
// + Engine.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine;


use Eva\EvaEngine\Exception\RuntimeException;
use Eva\EvaEngine\Foundation\ApplicationInterface;
use Eva\EvaEngine\Module\Manager;
use Phalcon\Cache\Backend\File;
use Phalcon\Cache\Frontend\None;
use Phalcon\Config;
use Phalcon\DI;
use Phalcon\DI\FactoryDefault;
use Phalcon\Loader;
use SuperClosure\Analyzer\AstAnalyzer;
use SuperClosure\Serializer;
use Whoops\Provider\Phalcon\WhoopsServiceProvider;

class Engine
{
    const MODE_WEB = 'web';
    const MODE_CLI = 'cli';

    protected $project_root = __DIR__;
    protected $mode = Engine::MODE_WEB;
    protected $di;
    protected static $instance = null;
    /**
     * @var ApplicationInterface
     */
    protected $application;

    public function __construct($project_root, ApplicationInterface $application, $systemCache = null)
    {
        $this->project_root = $project_root;
        $this->application = $application;
        $this->di = new FactoryDefault();
        $this->di->setShared('evaengine', $this);
        $this->di->setShared('loader', new Loader());
        if ($systemCache == null) {
            $systemCache = function () {
                return new File(new None(), array(
                    'cacheDir' => $this->getProjectRoot() . '/cache/system/',
                    'prefix' => $this->getAppName()
                ));
            };
        }
        $this->di->setShared('systemCache', $systemCache);
    }

    public function loadModules(array $modulesConfig)
    {
        $moduleManager = new Manager($this->getProjectRoot() . '/modules', $modulesConfig);
        $moduleManager->registerModules(array(
            'EvEngine' => array(
                'path' => __DIR__ . '/../../Module.php',
                'className' => 'Eva\EvaEngine\Module'
            )
        ));
        $moduleManager->registerModules($modulesConfig);
        $this->di->set('moduleManager', $moduleManager);

        return $this;
    }

    public function bootstrap()
    {
        $this->initializeConfig()
            ->initializeAutoLoaders()
            ->registerServices();

        $this->application->initializeErrorHandler()->initialize()->setDI($this->di);
        $this->initializeEvents();

        return $this;
    }

    protected function initializeConfig()
    {
        $moduleManager = eva_get('moduleManager');

        $config = $moduleManager->getAllConfig();
        $config->merge(
            new Config(include $this->getProjectRoot() . '/config/config.default.php')
        );

        $config->merge(
            new Config(include $this->getProjectRoot() . '/config/config.local.php')
        );

        $this->di->set('config', $config);

        return $this;
    }


    protected function initializeAutoLoaders()
    {
        $loader = new Loader();
        $moduleManager = eva_get('moduleManager');
        $loader->registerNamespaces($moduleManager->getAllAutoLoaders());
        $loader->register();

        return $this;
    }


    public function getMode()
    {
        return $this->mode;
    }

    public function run()
    {
        $this->application->fire();
    }

    protected function registerServices()
    {
        /** @var \Eva\EvaEngine\Module\Manager $moduleManager */
        $moduleManager = $this->di->get('moduleManager');
        foreach ($moduleManager->getAllDIDefinition() as $name => $definition) {
            $this->di->set($name, $definition['definition'], $definition['share']);
        }

        return $this;
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

        return $this;
    }

    public function getProjectRoot()
    {
        return $this->project_root;
    }

    public function getAppName()
    {
        return $this->application->getName();
    }
}