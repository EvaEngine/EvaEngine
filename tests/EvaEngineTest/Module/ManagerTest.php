<?php
namespace Eva\EvaEngine\EvaEngineTest\Module;

use Eva\EvaEngine\Module\Manager as ModuleManager;
use Phalcon\Loader;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $fooModule;
    protected $barModule;
    public function setUp()
    {
        $ds = DIRECTORY_SEPARATOR;
        $path = __DIR__ . "{$ds}TestAsset{$ds}FooModule";
        $this->fooModule = array(
            'className' => 'Eva\\FooModule\\Module',
            'path' => "{$path}{$ds}Module.php",
            'dir' => "{$path}",
            'moduleConfig' => "{$path}{$ds}config{$ds}config.php",
            'routesFrontend' => "{$path}{$ds}config{$ds}routes.frontend.php",
            'routesBackend' => "{$path}{$ds}config{$ds}routes.backend.php",
            'routesCommand' => "{$path}{$ds}config{$ds}routes.command.php",
            'adminMenu' => "{$path}{$ds}config{$ds}admin.menu.php",
            'autoloaders' => array(),
            'relations' => array(),
            'listeners' => array(),
            'viewHelpers' => array(),
            //'translators' =>  array(),
        );

        $path = __DIR__ . "{$ds}TestAsset{$ds}BarModule";
        $this->barModule = array(
            'className' => 'Eva\\BarModule\\Module',
            'path' => "{$path}{$ds}Module.php",
            'dir' => "{$path}",
            'moduleConfig' => "{$path}{$ds}config{$ds}config.php",
            'routesFrontend' => "{$path}{$ds}config{$ds}routes.frontend.php",
            'routesBackend' => "{$path}{$ds}config{$ds}routes.backend.php",
            'routesCommand' => "{$path}{$ds}config{$ds}routes.command.php",
            'adminMenu' => "{$path}{$ds}config{$ds}admin.menu.php",
            'autoloaders' => array(
                'BarModuleAutoloadersKey' => 'BarModuleAutoloadersValue',
            ),
            'relations' => array(
                'BarModuleRelationsKey' => 'BarModuleRelationsValue',
            ),
            'listeners' => array(
                'BarModuleEventLisnersKey' => 'BarModuleEventLisnersValue',
            ),
            'viewHelpers' => array(
                'BarModuleViewHelerKey' => 'BarModuleEventLisnersValue',
            ),
            //'translators' =>  array(),
        );

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Module [\w\\]+ load failed by not exist class/
     */
    public function testUnknowEvaModule()
    {
        $moduleManager = new ModuleManager();
        $moduleManager->getModuleInfo('Some_Unknow_Module');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Module [\w\\]+ load failed by not exist class/
     */
    public function testUnknowModule()
    {
        $moduleManager = new ModuleManager();
        $moduleManager->getModuleInfo('Some_Unknow_Module', 'Some\\Unknow\\Module');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Module [\w\\]+ load failed by incorrect format/
     */
    public function testIncorrectFormatModule()
    {
        $moduleManager = new ModuleManager();
        $moduleManager->getModuleInfo('Some_Unknow_Module', 1);
    }

    public function testOneModuleLoad()
    {
        $ds = DIRECTORY_SEPARATOR;
        $moduleManager = new ModuleManager();
        $moduleManager->setDefaultPath(__DIR__ . "{$ds}TestAsset");
        $expectModule = $this->fooModule;
        $this->assertEquals($expectModule, $moduleManager->getModuleInfo('FooModule'));
        $this->assertEquals($expectModule, $moduleManager->getModuleInfo('FooModule', 'Eva\\FooModule\\Module'));
        $this->assertEquals($expectModule, $moduleManager->getModuleInfo('FooModule', array()));
        $this->assertEquals($expectModule, $moduleManager->getModuleInfo('FooModule', $expectModule));
    }

    public function testTwoModuleLoadAndMerge()
    {
        $ds = DIRECTORY_SEPARATOR;
        $moduleManager = new ModuleManager(__DIR__ . "{$ds}TestAsset");
        $moduleManager->loadModules(
            array(
            'FooModule',
            'BarModule',
            )
        );
        $this->assertEquals(true, $moduleManager->hasModule('FooModule'));
        $this->assertEquals(true, $moduleManager->hasModule('BarModule'));
        $this->assertEquals($this->barModule, $moduleManager->getModule('BarModule'));
    }

    public function testModuleLoadOrder()
    {
        $ds = DIRECTORY_SEPARATOR;
        $moduleManager = new ModuleManager();
        $moduleManager->setDefaultPath(__DIR__ . "{$ds}TestAsset");
        $moduleManager->loadModules(
            array(
            'FooModule',
            'BarModule',
            'ThirdModule',
            )
        );
        $this->assertEquals(true, $moduleManager->hasModule('FooModule'));
        $this->assertEquals(true, $moduleManager->hasModule('BarModule'));

        $this->assertEquals(
            array(
            'BarModuleViewHelerKey' => 'BarModuleEventLisnersValue',
            'ThirdModuleViewHelerKey' => 'ThirdModuleEventLisnersValue',
            ),
            $moduleManager->getMergedViewHelpers()
        );

        $this->assertEquals(
            array(
            'BarModuleRelationsKey' => 'BarModuleRelationsValue',
            'ThirdModuleRelationsKey' => 'ThirdModuleRelationsValue',
            ),
            $moduleManager->getMergedRelations()
        );

        $this->assertEquals(
            array(
            'BarModuleAutoloadersKey' => 'ThirdModuleAutoloadersValue',
            ),
            $moduleManager->getMergedAutoloaders()
        );
    }


    public function testModuleCacheAndEvents()
    {

    }

    public function testModuleKeyValue()
    {
        $moduleManager = new ModuleManager();
        $this->assertEquals('', $moduleManager->getModulePath('test'));
        $this->assertEquals(array(), $moduleManager->getModuleConfig('test'));
        $this->assertEquals(array(), $moduleManager->getModuleRoutesFrontend('test'));
        $this->assertEquals(array(), $moduleManager->getModuleRoutesBackend('test'));
        $this->assertEquals(array(), $moduleManager->getModuleRoutesCommand('test'));
        $this->assertEquals(array(), $moduleManager->getModuleListeners('test'));
        $this->assertEquals('', $moduleManager->getModuleAdminMenu('test'));
        $this->assertEquals(array(), $moduleManager->getModuleViewHelpers('test'));
        $this->assertEquals(array(), $moduleManager->getMergedAutoloaders());

        $ds = DIRECTORY_SEPARATOR;
        $moduleManager = new ModuleManager();
        $moduleManager->setDefaultPath(__DIR__ . "{$ds}TestAsset");
        $moduleManager->loadModules(
            array(
            'BarModule',
            'ThirdModule',
            )
        );
        $this->assertEquals($this->barModule['dir'], $moduleManager->getModulePath('BarModule'));
        $this->assertEquals(array('barModuleConfig' => 1), $moduleManager->getModuleConfig('BarModule'));
        $this->assertEquals(array('barModuleRouterFront' => 1), $moduleManager->getModuleRoutesFrontend('BarModule'));
        $this->assertEquals(array('barModuleRouterBackend' => 1), $moduleManager->getModuleRoutesBackend('BarModule'));
        $this->assertEquals(array('barModuleRouterCommand' => 1), $moduleManager->getModuleRoutesCommand('BarModule'));
        $this->assertEquals(array('BarModuleEventLisnersKey' => 'BarModuleEventLisnersValue'), $moduleManager->getModuleListeners('BarModule'));

        ob_start();
        $moduleManager->getModuleAdminMenu('BarModule');
        $adminMenu = ob_get_contents();
        ob_end_clean();
        $this->assertEquals("barModuleAdminMenu", $adminMenu);
        $this->assertEquals(array('BarModuleViewHelerKey' => 'BarModuleEventLisnersValue'), $moduleManager->getModuleViewHelpers('BarModule'));
    }

    public function testInjectRelations()
    {
        /*
        $ds = DIRECTORY_SEPARATOR;
        $moduleManager = new ModuleManager(__DIR__ . "{$ds}TestAsset");
        $moduleManager->loadModules(array('RealModule'));
        $moduleManager->getInjectRelations(new \Eva\RealModule\Models\User());
        */
    }

    public function testListeners()
    {
        $ds = DIRECTORY_SEPARATOR;
        $moduleManager = new ModuleManager(__DIR__ . "{$ds}TestAsset");
        $moduleManager->loadModules(array('RealModule'));
        $moduleManager->attachEvents();
        $this->assertTrue($moduleManager->getEventsManager()->hasListeners('module'));
        $this->assertTrue($moduleManager->getEventsManager()->hasListeners('dispatch'));
    }

    public function testListenerPriorities()
    {

    }
}
