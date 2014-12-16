<?php

namespace Eva\RealModule;

use Eva\EvaEngine\Module\AbstractModule;

class Module extends AbstractModule
{
    public static function registerGlobalAutoloaders()
    {
        return array(
            'Eva\RealModule' => __DIR__ . '/src/RealModule',
        );
    }

    public static function registerGlobalEventListeners()
    {
        return array(
            'module' => 'Eva\RealModule\Events\ModuleListener',
            'dispatch' => array('Eva\RealModule\Events\DispatchListener', 100),
        );
    }

    public static function registerGlobalViewHelpers()
    {
        return array(
            'ThirdModuleViewHelerKey' => 'ThirdModuleEventLisnersValue',
        );
    }

    public static function registerGlobalRelations()
    {
        return array(
            'injectRelationTest1' => array(
                'module' => 'RealModule',
                'entity' => 'Eva\RealModule\Models\User',
                'relationType' => 'hasManyToMany',
                'parameters' => array(
                )
            ),
        );
    }

    /**
     * Registers the module auto-loader
     */
    public function registerAutoloaders()
    {
    }
}
