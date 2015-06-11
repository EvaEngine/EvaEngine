<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Mvc;

use Eva\EvaEngine\Exception;
use Phalcon\Mvc\View as PhalconView;
use Phalcon\Http\Response;
use Phalcon\Events\Event;

/**
 * EvaEngine view class
 * Allow load view template cross modules
 * @package Eva\EvaEngine\Mvc
 */
class View extends PhalconView
{
    /**
     * Whether throw exception when view not exists
     * @var bool
     */
    protected static $renderException = false;

    /**
     * @var string
     */
    protected $layoutsAbsoluteDir;

    /**
     * @var string
     */
    protected $moduleLayout;

    /**
     * @var string
     */
    protected $moduleViewsDir;

    /**
     * @var string
     */
    protected $moduleLayoutName;

    /**
     * @var string
     */
    protected $modulePartialsDir;

    /**
     * @var array
     */
    protected static $components = [];

    /**
     * After enabled, an exception will be throw if view is missing
     */
    public static function enableRenderException()
    {
        self::$renderException = true;
    }

    /**
     * @param $componentName
     * @param $componentClass
     */
    public static function registerComponent($componentName, $componentClass)
    {
        self::$components[$componentName] = $componentClass;
    }

    /**
     * @param $componentName
     * @param $params
     * @return mixed
     * @throws Exception\BadMethodCallException
     */
    public static function getComponent($componentName, $params)
    {
        if (!isset(self::$components[$componentName])) {
            throw new Exception\BadMethodCallException(sprintf('Component %s not registered', $componentName));
        }

        $component = new self::$components[$componentName]();

        return $component($params);
    }

    /**
     * @return string
     */
    public function getModuleLayout()
    {
        return $this->moduleLayout;
    }

    /**
     * @param $moduleName
     * @param $layoutPath
     * @return $this
     */
    public function setModuleLayout($moduleName, $layoutPath)
    {
        $moduleManager = $this->getDI()->getModuleManager();
        if (!$moduleManager) {
            return $this;
        }

        $this->moduleLayout = [$moduleName, $layoutPath];
        $moduleLayout = $moduleManager->getModulePath($moduleName) . $layoutPath;

        $this->setLayout(basename($moduleLayout));
        $this->setLayoutsAbsoluteDir(dirname($moduleLayout));
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleViewsDir()
    {
        return $this->moduleViewsDir;
    }

    /**
     * @param $moduleName
     * @param $viewsDir
     * @return $this
     */
    public function setModuleViewsDir($moduleName, $viewsDir)
    {
        $moduleManager = $this->getDI()->getModuleManager();
        if (!$moduleManager) {
            return $this;
        }

        $viewsDir = self::normalizePath($moduleManager->getModulePath($moduleName) . $viewsDir);
        $this->setViewsDir($viewsDir);

        //In Phalcon, layouts dir & partials dir are related to views dir.
        //If here reset views, others need to reset either.
        if ($this->moduleLayout) {
            $this->setModuleLayout($this->moduleLayout[0], $this->moduleLayout[1]);
        }

        if ($this->modulePartialsDir) {
            $this->setModulePartialsDir($this->modulePartialsDir[0], $this->modulePartialsDir[1]);
        }

        return $this;
    }

    /**
     * @param $moduleName
     * @param $partialsDir
     * @return $this
     */
    public function setModulePartialsDir($moduleName, $partialsDir)
    {
        $moduleManager = $this->getDI()->getModuleManager();
        if (!$moduleManager) {
            return $this;
        }

        $this->modulePartialsDir = [$moduleName, $partialsDir];

        $partialsDir = self::normalizePath($moduleManager->getModulePath($moduleName) . $partialsDir);
        $this->setPartialsAbsoluteDir($partialsDir);
        return $this;
    }

    /**
     * @param $renderName
     * @return $this
     */
    public function changeRender($renderName)
    {
        //NOTE:after pick, View will append layout to _pickView automatic
        $this->pick($renderName);
        array_pop($this->_pickView);
        return $this;
    }

    /**
     * @param null $controllerName
     * @param null $actionName
     * @param null $params
     * @return PhalconView
     */
    public function render($controllerName = null, $actionName = null, $params = null)
    {
        //TODO: contribute to phalcon
        //fixed render view name not match under linux
        if ($controllerName && false !== strpos($controllerName, '\\')) {
            $controllerName = strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $controllerName));
        }

        if (self::$renderException) {
            $this->getEventsManager()->attach('view:notFoundView', function ($event, $view, $path) {
                throw new Exception\IOException(sprintf('View not found in path %s', $path));
            });
        }

        if ($this->getDI()->getConfig()->debug) {
            $i = 0;
            $debugCallback = function ($event, $view, $path) use (&$i) {
                /** @var Event $event */
                /** @var View $view */
                /** @var Response $response */
                $response = $view->getDI()->getResponse();
                $response->setHeader("X-DEBUG-VIEW$i", json_encode([
                    'type' => $event->getType(),
                    'activeRenderPath' => $view->getActiveRenderPath(),
                    'layoutsDir' => $view->getLayoutsDir(),
                    'layout' => $view->getLayout(),
                    'viewDir' => $view->getViewsDir(),
                    'mainView' => $view->getMainView(),
                    'controllerName' => $view->getControllerName(),
                    'actionName' => $view->getActionName(),
                    'pickView' => $this->_pickView,
                ]));
                $i++;
            };
            $this->getEventsManager()->attach('view:notFoundView', $debugCallback);
            //$this->getEventsManager()->attach('view:beforeRenderView', $debugCallback);
            $this->getEventsManager()->attach('view:afterRenderView', $debugCallback);
        }
        return parent::render($controllerName, $actionName, $params);
    }

    /**
     * Set the current layouts directory, input dir NOT related to view path
     * @param $layoutsDir
     * @return $this
     */
    public function setLayoutsAbsoluteDir($layoutsDir)
    {
        $this->layoutsAbsoluteDir = $layoutsDir;
        $this->setLayoutsDir(self::relativePath($this->getViewsDir(), $layoutsDir));
        return $this;
    }

    /**
     * @return string
     */
    public function getLayoutAbsoluteDir()
    {
        return $this->layoutsAbsoluteDir;
    }

    /**
     * @param $partialsDir
     * @return $this
     */
    public function setPartialsAbsoluteDir($partialsDir)
    {
        $this->setPartialsDir(self::relativePath($this->getViewsDir(), $partialsDir));
        return $this;
    }

    /**
     * @param $path
     * @param string $ds
     * @return string
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        if (!$path) {
            return '';
        }

        $path = str_replace(array('/', '\\'), $ds, $path);
        $parts = array_filter(explode($ds, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return $absolutes ? $ds . implode($ds, $absolutes) . $ds : $ds;
    }

    /**
     * Get relative path from pathSrc to pathTarget
     * Notice:
     * - Same paths will return empty string
     * - Result ALWAYS end with /
     * - Require input paths are absolute paths, if not will be convert to absolute paths by force
     *
     * @param string $pathSrc
     * @param string $pathTarget
     * @param string $ds
     * @return string
     */
    public static function relativePath($pathSrc, $pathTarget, $ds = DIRECTORY_SEPARATOR)
    {
        //Convert relative paths to absolute paths
        $pathSrc = $ds . trim(self::normalizePath($pathSrc, $ds), $ds);
        $pathTarget = $ds . trim(self::normalizePath($pathTarget, $ds), $ds);
        if ($pathSrc == $pathTarget) {
            return '';
        }

        if ($pathSrc == '/') {
            return ltrim($pathTarget, $ds) . $ds;
        }

        $pathSrcArr = explode($ds, $pathSrc);
        $pathTargetArr = explode($ds, $pathTarget);

        while (count($pathSrcArr) && count($pathTargetArr) && ($pathSrcArr[0] == $pathTargetArr[0])) {
            array_shift($pathSrcArr);
            array_shift($pathTargetArr);
        }

        if ($pathTargetArr) {
            return str_pad("", count($pathSrcArr) * 3, '..' . $ds) . implode($ds, $pathTargetArr) . $ds;
        }
        return str_pad("", count($pathSrcArr) * 3, '..' . $ds);
    }
}
