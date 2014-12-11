<?php

namespace Eva\BarModule;

use Eva\EvaEngine\Module\AbstractModule;

class Module extends AbstractModule
{
    public static function registerGlobalAutoloaders()
    {
        return array(
            'BarModuleAutoloadersKey' => 'BarModuleAutoloadersValue',
        );
    }

    public static function registerGlobalEventListeners()
    {
        return array(
            'BarModuleEventLisnersKey' => 'BarModuleEventLisnersValue',
        );
    }

    public static function registerGlobalViewHelpers()
    {
        return array(
            'BarModuleViewHelerKey' => 'BarModuleEventLisnersValue',
        );
    }

    public static function registerGlobalRelations()
    {
        return array(
            'BarModuleRelationsKey' => 'BarModuleRelationsValue',
        );
    }

    /**
     * Registers the module auto-loader
     */
    public function registerAutoloaders()
    {
    }
}
