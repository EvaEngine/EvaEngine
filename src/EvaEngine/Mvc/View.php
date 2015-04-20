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

/**
 * EvaEngine view class
 * Allow load view template cross modules
 * @package Eva\EvaEngine\Mvc
 */
class View extends PhalconView
{
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
    protected static $components = array();

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

        $moduleLayout = $moduleManager->getModulePath($moduleName) . $layoutPath;
        $this->moduleLayout = realpath(dirname($moduleLayout));
        $this->moduleLayoutName = basename($moduleLayout);
        if ($this->moduleViewsDir) {
            $this->caculateLayoutRelatedPath();
        }

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

        $modulePath = $moduleManager->getModulePath($moduleName);
        $this->moduleViewsDir = $moduleViewsDir = realpath($modulePath . $viewsDir);
        $this->setViewsDir($moduleViewsDir);
        if ($this->moduleLayout) {
            $this->caculateLayoutRelatedPath();
        }
        if ($this->modulePartialsDir) {
            $this->caculatePartialsRelatedPath();
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

        $modulePath = $moduleManager->getModulePath($moduleName);
        $this->modulePartialsDir = $modulePartialsDir = realpath($modulePath . $partialsDir);
        if ($this->moduleViewsDir) {
            $this->caculatePartialsRelatedPath();
        }

        return $this;
    }

    /**
     * @param $renderName
     * @return $this
     */
    public function changeRender($renderName)
    {
        if (!$this->moduleLayoutName) {
            return $this;
        }
        $this->setTemplateAfter($this->moduleLayoutName);
        $this->pick($renderName);

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
        //fixed render view name not match under linux
        if ($controllerName && false !== strpos($controllerName, '\\')) {
            $controllerName = strtolower(str_replace('\\', '/', $controllerName));
        }

        return parent::render($controllerName, $actionName, $params);
    }

    /**
     * @return $this
     */
    protected function caculatePartialsRelatedPath()
    {
        $moduleViewsDir = $this->moduleViewsDir;
        $partialsDir = $this->modulePartialsDir;
        // fix: always add a trailing slash or backslash in 2.0.x
        $this->setPartialsDir(DIRECTORY_SEPARATOR . $this->relativePath($moduleViewsDir, $partialsDir) . DIRECTORY_SEPARATOR);

        return $this;
    }

    /**
     * @return $this
     */
    protected function caculateLayoutRelatedPath()
    {
        $moduleViewsDir = $this->moduleViewsDir;
        $moduleLayout = $this->moduleLayout;
        $layoutName = $this->moduleLayoutName;
        // FIX: in 2.0.0, always add a trailing slash or backslash
        $this->setLayoutsDir(DIRECTORY_SEPARATOR . $this->relativePath($moduleViewsDir, $moduleLayout) . DIRECTORY_SEPARATOR);
        $this->setLayout($layoutName);

        return $this;
    }

    /**
     * @param $from
     * @param $to
     * @param string $ps
     * @return string
     */
    protected function relativePath($from, $to, $ps = DIRECTORY_SEPARATOR)
    {
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));
        while (count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
            array_shift($arFrom);
            array_shift($arTo);
        }

        return str_pad("", count($arFrom) * 3, '..' . $ps) . implode($ps, $arTo);
    }
}
