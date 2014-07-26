<?php

namespace Eva\EvaEngine\Mvc;

use Eva\EvaEngine\Exception;

class View extends \Phalcon\Mvc\View
{
    protected $moduleLayout;

    protected $moduleViewsDir;

    protected $moduleLayoutName;

    protected $modulePartialsDir;

    protected static $components;

    public static function registerComponent($componentName, $componentClass)
    {
        self::$components[$componentName] = $componentClass;
    }

    public static function getComponent($componentName, $params)
    {
        if (!isset(self::$components[$componentName])) {
            throw new Exception\BadMethodCallException(sprintf('Component %s not registered', $componentName));
        }

        $component = new self::$components[$componentName]();

        return $component($params);
    }

    public function getModuleLayout()
    {
        return $this->moduleLayout;
    }

    public function setModuleLayout($moduleName, $layoutPath)
    {
        $moduleManager = $this->getDI()->get('moduleManager');
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

    public function getModuleViewsDir()
    {
        return $this->moduleViewsDir;
    }

    public function setModuleViewsDir($moduleName, $viewsDir)
    {
        $moduleManager = $this->getDI()->get('moduleManager');
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

    public function setModulePartialsDir($moduleName, $partialsDir)
    {
        $moduleManager = $this->getDI()->get('moduleManager');
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

    public function changeRender($renderName)
    {
        if (!$this->moduleLayoutName) {
            return $this;
        }
        $this->setTemplateAfter($this->moduleLayoutName);
        $this->pick($renderName);

        return $this;
    }

    public function render($controllerName = null, $actionName = null, $params = null)
    {
        //fixed render view name not match under linux
        if ($controllerName && false !== strpos($controllerName, '\\')) {
            $controllerName = strtolower(str_replace('\\', '/', $controllerName));
        }

        return parent::render($controllerName, $actionName, $params);
    }

    protected function caculatePartialsRelatedPath()
    {
        $moduleViewsDir = $this->moduleViewsDir;
        $partialsDir = $this->modulePartialsDir;
        $this->setPartialsDir(DIRECTORY_SEPARATOR . $this->relativePath($moduleViewsDir, $partialsDir));

        return $this;
    }

    protected function caculateLayoutRelatedPath()
    {
        $moduleViewsDir = $this->moduleViewsDir;
        $moduleLayout = $this->moduleLayout;
        $layoutName = $this->moduleLayoutName;
        $this->setLayoutsDir(DIRECTORY_SEPARATOR . $this->relativePath($moduleViewsDir, $moduleLayout));
        $this->setLayout($layoutName);

        return $this;
    }

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
