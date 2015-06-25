<?php
// +----------------------------------------------------------------------
// | wallstreetcn
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/17 下午6:54
// +----------------------------------------------------------------------
// + WebApplication.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Mvc;


use Eva\EvaEngine\Exception\RuntimeException;
use Eva\EvaEngine\Foundation\ApplicationInterface;
use Eva\EvaEngine\Module\Manager;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Router;
use Whoops\Provider\Phalcon\WhoopsServiceProvider;

class WebApplication extends Application implements ApplicationInterface
{
    protected $name = 'evaengine';

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->name = empty($_SERVER['APPLICATION_NAME']) ? $name : $_SERVER['APPLICATION_NAME'];
        $this->environment = empty($_SERVER['APPLICATION_ENV']) ? 'development' : $_SERVER['APPLICATION_ENV'];
    }

    public function initializeErrorHandler()
    {
        $whoops = new WhoopsServiceProvider($this->di);
        eva_get('whoops.pretty_page_handler')->setEditor(function ($file, $line) {
            return "phpstorm://open?file=$file&line=$line";
        });
        return $this;
    }

    public function fire()
    {
        $response = $this->handle();
        echo $response->getContent();
    }

    public function getName()
    {
        return $this->name;
    }

    public static function initializeRouter(Manager $moduleManager, Router &$router)
    {

        //NOTICE: EvaEngine Load front-end router at last

        $routes = $moduleManager->getAllWebRoutes();
        foreach ($routes->toArray() as $url => $route) {
            if (count($route) !== count($route, COUNT_RECURSIVE)) {
                if (isset($route['pattern']) && isset($route['paths'])) {
                    $method = isset($route['httpMethods']) ? $route['httpMethods'] : null;
                    $router->add($route['pattern'], $route['paths'], $method);
                } else {
                    throw new RuntimeException(
                        sprintf('No route pattern and paths found by route %s', $url)
                    );
                }
            } else {
                $router->add($url, $route);
            }
        }
    }

    public function initialize()
    {
        $moduleManager = eva_get('moduleManager');
        $router = eva_get('router');
        static::initializeRouter($moduleManager, $router);

        $this->setEventsManager(eva_get('eventsManager'));
        $this->registerModules($moduleManager->getModulesForPhalcon());
        $this->setDefaultModule($moduleManager->getDefaultModule()->getName());

        return $this;
    }

}