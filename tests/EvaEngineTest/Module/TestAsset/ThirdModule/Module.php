<?php

namespace Eva\ThirdModule;

use Eva\EvaEngine\Module\AbstractModule;

class Module extends AbstractModule
{
    public static function registerGlobalAutoloaders()
    {
        return array(
            'BarModuleAutoloadersKey' => 'ThirdModuleAutoloadersValue',
        );
    }

    public static function registerGlobalEventListeners()
    {
        return array(
            'ThirdModuleEventLisnersKey' => 'ThirdModuleEventLisnersValue',
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
            'ThirdModuleRelationsKey' => 'ThirdModuleRelationsValue',
        );
    }

    /**
     * Registers the module auto-loader
     */
    public function registerAutoloaders()
    {
    }
}
