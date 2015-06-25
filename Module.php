<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/5/22 下午5:19
// +----------------------------------------------------------------------
// + Module.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine;

use Eva\EvaEngine\Module\AbstractModule;
use Eva\EvaEngine\Module\Manager as ModuleManager;
use Eva\EvaEngine\Module\StandardInterface;
use Phalcon\Events\Manager as EventsManager;

class Module extends AbstractModule
{
    /**
     * Registers an autoloader related to the module
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function registerAutoloaders()
    {
        // TODO: Implement registerAutoloaders() method.
    }

    /**
     * Registers services related to the module
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function registerServices($dependencyInjector)
    {
        // TODO: Implement registerServices() method.
    }

    /**
     * @return void
     */
    public static function registerGlobalAutoloaders()
    {
        // TODO: Implement registerGlobalAutoloaders() method.
    }

    /**
     * @return void
     */
    public static function registerGlobalEventListeners()
    {
        // TODO: Implement registerGlobalEventListeners() method.
    }

    /**
     * @return void
     */
    public static function registerGlobalViewHelpers()
    {
        // TODO: Implement registerGlobalViewHelpers() method.
    }

    /**
     * @return array
     */
    public static function registerGlobalRelations()
    {
        return array();
    }
}