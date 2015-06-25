<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Module;

use Phalcon\Config;
use Phalcon\DiInterface;
use Phalcon\Loader;

use Eva\EvaEngine\Mvc\Model;
use Phalcon\Mvc\Application;
use SuperClosure\Analyzer\AstAnalyzer;
use SuperClosure\Analyzer\TokenAnalyzer;
use SuperClosure\Serializer;

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
class Manager
{
    /**
     * @var string 模块的默认所在目录
     */
    protected $defaultModulesDir = '';
    /**
     * @var array<Module>
     */
    protected $modules = array();
    /**
     * @var array<array>
     */
    protected $phalconModules = array();
    /**
     * @var DIInterface
     */
    protected $di;


    protected $allAutoLoaders = array();
    /**
     * @var array
     */
    protected $allRoutesFrontend;
    /**
     * @var Config
     */
    protected $allRoutesBackend;
    /**
     * @var Config
     */
    protected $allRoutesConsole;

    protected $allListeners = array();
    protected $allRelations = array();

    /**
     * @var array()
     */
    protected $allAdminMenuFiles = array();
    /**
     * @var array 所有的 viewHelper
     */
    protected $allViewHelpers = array();
    /**
     * @var Config
     */
    protected $allConfig;
    /**
     * @var array
     */
    protected $allDIDefinition = array();
    /**
     * @var array
     */
    protected $allErrorHandlers = array();

    /**
     * @param string $defaultModulesDir 模块默认路径，当没有指定模块的路径时，从这里加载模块
     */
    public function __construct($defaultModulesDir)
    {
        $this->defaultModulesDir = $defaultModulesDir;
        $this->moduleParser = new ModuleParser($this->defaultModulesDir);
        $this->allConfig = new Config();
        $this->allRoutesBackend = new Config();
        $this->allRoutesFrontend = new Config();
        $this->allRoutesConsole = new Config();
    }

    public function hasModule($name)
    {
        return isset($this->modules[$name]);
    }

    /**
     * 通过模块配置数组来注册多个模块
     *
     * @param array $modulesConfig
     */
    public function registerModules(array $modulesConfig)
    {
        foreach ($modulesConfig as $name => $moduleOptions) {
            // 当数组键为数字时，说明没有设置模块的 options，则模块名称取数组的「值」
            if (is_numeric($name)) {
                $name = $moduleOptions;
                $moduleOptions = array();
            }
            $module = $this->moduleParser->parse($name, $moduleOptions);
            $this->register($module);
        }
    }

    /**
     * 获取默认的模块
     *
     * @return Module
     */
    public function getDefaultModule()
    {
        // 最后注册的模块作为默认模块
        return end($this->modules);
    }

    /**
     * 通过模块名来获取模块
     *
     * @param $name
     * @return Module
     */
    public function getModule($name)
    {
        return $this->modules[$name];
    }

    /**
     * 获取所有的模块
     *
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * 获取注册到给定的 Entity 上的 ORM 关系
     *
     * @param \Phalcon\Mvc\Model $entity
     * @return array
     */
    public function getInjectedRelations(\Phalcon\Mvc\Model $entity)
    {
        $relations = array();
        foreach ($this->allRelations as $relationDefinition) {
            if ($entity instanceof $relationDefinition['entity']) {
                $relations[] = $relationDefinition;
            }
        }

        return $relations;
    }

    /**
     * 注册一个模块到模块管理器
     *
     * @param Module $module
     */
    public function register(Module $module)
    {
        $this->modules[$module->getName()] = $module;
        $this->phalconModules[$module->getName()] = array(
            'path' => $module->getPath(),
            'className' => $module->getClassName()
        );
        $this->allAdminMenuFiles[] = $module->getAdminMenusFile();
        $this->allConfig->merge(new Config($module->getConfig()));
        $this->allRoutesFrontend->merge(new Config($module->getRoutesFrontend()));
        $this->allRoutesBackend->merge(new Config($module->getRoutesBackend()));
        $this->allRoutesConsole->merge(new Config($module->getRoutesConsole()));
        if (!empty($module->getListeners())) {
            $this->allListeners[] = $module->getListeners();
        }
        $this->allViewHelpers = array_merge($this->allViewHelpers, $module->getViewHelpers());
        $this->allErrorHandlers = array_merge($this->allErrorHandlers, $module->getErrorHandlers());
//        if (is_array($module->getRelations()) && !empty($module->getRelations())) {
//            foreach ($module->getRelations() as $entity => $relationDefinition) {
//                $this->allRelations[$entity][] = $relationDefinition;
//            }
//        }
        $this->allRelations = array_merge($this->allRelations, $module->getRelations());
        $this->allDIDefinition = array_merge($this->allDIDefinition, $module->getDiDefinition());
        $this->allAutoLoaders = array_merge($this->allAutoLoaders, $module->getAutoLoaders());
    }

    /**
     * 获取用于注册到 Phalcon Application 的 module 数组
     *
     * @return array
     */
    public function getModulesForPhalcon()
    {
        return $this->phalconModules;
    }

    /**
     * @return mixed
     */
    public function getAllAutoLoaders()
    {
        return $this->allAutoLoaders;
    }

    /**
     * @param mixed $allAutoLoaders
     */
    public function setAllAutoLoaders($allAutoLoaders)
    {
        $this->allAutoLoaders = $allAutoLoaders;
    }

    /**
     * @return Config
     */
    public function getAllRoutesFrontend()
    {
        return $this->allRoutesFrontend;
    }

    /**
     * @param Config $allRoutesFrontend
     */
    public function setAllRoutesFrontend($allRoutesFrontend)
    {
        $this->allRoutesFrontend = $allRoutesFrontend;
    }

    /**
     * @return Config
     */
    public function getAllRoutesBackend()
    {
        return $this->allRoutesBackend;
    }

    /**
     * @param Config $allRoutesBackend
     */
    public function setAllRoutesBackend($allRoutesBackend)
    {
        $this->allRoutesBackend = $allRoutesBackend;
    }

    /**
     * @return Config
     */
    public function getAllRoutesConsole()
    {
        return $this->allRoutesConsole;
    }

    /**
     * @param Config $allRoutesConsole
     */
    public function setAllRoutesConsole($allRoutesConsole)
    {
        $this->allRoutesConsole = $allRoutesConsole;
    }

    /**
     * @return array
     */
    public function getAllListeners()
    {
        return $this->allListeners;
    }

    /**
     * @param array $allListeners
     */
    public function setAllListeners($allListeners)
    {
        $this->allListeners = $allListeners;
    }

    /**
     * @return array
     */
    public function getAllRelations()
    {
        return $this->allRelations;
    }

    /**
     * @param array $allRelations
     */
    public function setAllRelations($allRelations)
    {
        $this->allRelations = $allRelations;
    }

    /**
     * @return array
     */
    public function getAllAdminMenuFiles()
    {
        return $this->allAdminMenuFiles;
    }

    /**
     * @param array $allAdminMenuFiles
     */
    public function setAllAdminMenuFiles($allAdminMenuFiles)
    {
        $this->allAdminMenuFiles = $allAdminMenuFiles;
    }

    /**
     * @return array
     */
    public function getAllViewHelpers()
    {
        return $this->allViewHelpers;
    }

    /**
     * @param array $allViewHelpers
     */
    public function setAllViewHelpers($allViewHelpers)
    {
        $this->allViewHelpers = $allViewHelpers;
    }

    /**
     * @return Config
     */
    public function getAllConfig()
    {
        return $this->allConfig;
    }

    /**
     * @param Config $allConfig
     */
    public function setAllConfig($allConfig)
    {
        $this->allConfig = $allConfig;
    }

    /**
     * @return array
     */
    public function getAllDIDefinition()
    {
        return $this->allDIDefinition;
    }

    /**
     * @param array $allDIDefinition
     */
    public function setAllDIDefinition($allDIDefinition)
    {
        $this->allDIDefinition = $allDIDefinition;
    }

    public function getAllWebRoutes()
    {
        $routes = $this->getAllRoutesBackend();
        $routes->merge($this->getAllRoutesFrontend());

        return $routes;
    }

    public function serialize()
    {
//        dd(get_object_vars($this));
//        dd($this->getAllDIDefinition());
//        $files = array();
        foreach ($this->getAllDIDefinition() as $name => $definition) {
//            p(gettype($definition['definition'] ));
            if (!$definition['definition'] instanceof \Closure) {
                continue;
            }
            $serializer = new Serializer(new TokenAnalyzer());
            p($name);
            p($serializer->getData($definition['definition'], true));
            /**
             * 创建一个反射：
             */
//            $reflection = new \ReflectionFunction($definition['definition']);
//            p($reflection->getParameters());
//
//            p($reflection->getFileName());
//            p($reflection->getStartLine());
//            p($reflection->getEndLine());
//            p($reflection->getClosure());
//            /**
//             * 参数可以直接得到了：
//             */
//            $params = $reflection->getParameters();
//
//            /**
//             * 获得Closure的函数体和use变量，形如：
//             * function($arg1, $arg2, ...) use ($val1, $val2, ...) {
//             *     // 要获得这个部分的代码！
//             * }
//             * 办法很多，你可以直接用正则、字符串查找或者Tokenizer，等等等等。
//             * 比如可以先从reflection里得到函数的开始行和结束行：
//             */
//            $startLine = $reflection->getStartLine();
//            $endLine = $reflection->getEndLine();
        }

    }
}
