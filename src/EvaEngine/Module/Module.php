<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/15 下午4:50
// +----------------------------------------------------------------------
// + Module.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Module;


class Module
{
    /**
     * @var string name of module
     */
    protected $name;
    /**
     * @var string root path of module
     */
    protected $dir;
    /**
     * @var string file path of module definition
     */
    protected $path;
    /**
     * @var string class name of module definition
     */
    protected $className;

    /**
     * @var array routes of frontend
     */
    protected $routesFrontend = array();
    /**
     * @var array routes of backend
     */
    protected $routesBackend = array();
    /**
     * @var array routes in console mode
     */
    protected $routesConsole = array();
    /**
     * @var array 监听器
     */
    protected $listeners = array();
    /**
     * @var string 管理后台菜单文件
     */
    protected $adminMenusFile = '';
    /**
     * @var array 模块配置
     */
    protected $config = array();
    /**
     * @var array 模块注册的全局
     */
    protected $relations = array();
    /**
     * @var array 视图助手
     */
    protected $viewHelpers = array();
    /**
     * @var array 模块注册的 autoLoaders
     */
    protected $autoLoaders = array();
    /**
     * @var array
     */
    protected $errorHandlers = array();
    /**
     * @var array
     */
    protected $diDefinition = array();
    /**
     * @var array 模块选项
     */
    protected $options = array();


    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @param string $dir
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return array
     */
    public function getRoutesFrontend()
    {
        return $this->routesFrontend;
    }

    /**
     * @param array $routesFrontend
     */
    public function setRoutesFrontend($routesFrontend)
    {
        $this->routesFrontend = $routesFrontend;
    }

    /**
     * @return array
     */
    public function getRoutesBackend()
    {
        return $this->routesBackend;
    }

    /**
     * @param array $routesBackend
     */
    public function setRoutesBackend($routesBackend)
    {
        $this->routesBackend = $routesBackend;
    }

    /**
     * @return array
     */
    public function getRoutesConsole()
    {
        return $this->routesConsole;
    }

    /**
     * @param array $routesConsole
     */
    public function setRoutesConsole($routesConsole)
    {
        $this->routesConsole = $routesConsole;
    }

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param array $listeners
     */
    public function setListeners($listeners)
    {
        $this->listeners = $listeners;
    }

    /**
     * @return string
     */
    public function getAdminMenusFile()
    {
        return $this->adminMenusFile;
    }

    /**
     * @param string $adminMenusFile
     */
    public function setAdminMenusFile($adminMenusFile)
    {
        $this->adminMenusFile = $adminMenusFile;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param array $relations
     */
    public function setRelations($relations)
    {
        $this->relations = $relations;
    }

    /**
     * @return array
     */
    public function getViewHelpers()
    {
        return $this->viewHelpers;
    }

    /**
     * @param array $viewHelpers
     */
    public function setViewHelpers($viewHelpers)
    {
        $this->viewHelpers = $viewHelpers;
    }

    /**
     * @return array
     */
    public function getAutoLoaders()
    {
        return $this->autoLoaders;
    }

    /**
     * @param array $autoLoaders
     */
    public function setAutoLoaders($autoLoaders)
    {
        $this->autoLoaders = $autoLoaders;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getErrorHandlers()
    {
        return $this->errorHandlers;
    }

    /**
     * @param array $errorHandlers
     */
    public function setErrorHandlers($errorHandlers)
    {
        $this->errorHandlers = $errorHandlers;
    }

    /**
     * @return array
     */
    public function getDiDefinition()
    {
        return $this->diDefinition;
    }

    /**
     * @param array $diDefinition
     */
    public function setDiDefinition($diDefinition)
    {
        $this->diDefinition = $diDefinition;
    }

}