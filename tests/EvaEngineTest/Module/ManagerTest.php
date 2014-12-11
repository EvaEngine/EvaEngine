<?php
namespace Eva\EvaEngine\EvaEngineTest\Module;

use Eva\EvaEngine\Module\Manager as ModuleManager;
use Phalcon\Loader;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        /*
        $loader = new Loader();
        $loader->registerNamespaces(array(
            'Eva\FooModule'
        ));
        */

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Module \w+ load failed by not exist class/
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

    public function testSingleEvaOfficialModule()
    {
        $ds = DIRECTORY_SEPARATOR;
        $moduleManager = new ModuleManager();
        $moduleManager->setDefaultPath(__DIR__ . "{$ds}TestAsset");
        $path = __DIR__ . "{$ds}TestAsset{$ds}FooModule";
        $expectModule = array(
            'className' => 'Eva\\FooModule\\Module',
            'path' => "{$path}{$ds}Module.php",
            'dir' => "{$path}",
            'moduleConfig' => "{$path}{$ds}config{$ds}config.php",
            'routesFrontend' => "{$path}{$ds}config{$ds}routes.frontend.php",
            'routesBackend' => "{$path}{$ds}config{$ds}routes.backend.php",
            'routesCommand' => "{$path}{$ds}config{$ds}routes.command.php",
            'adminMenu' => "{$path}{$ds}config{$ds}admin.menu.php",
            'relations' => array(),
            'listeners' => array(),
            'viewHelpers' => array(),
            //'translators' =>  array(),
        );
        $this->assertEquals($expectModule, $moduleManager->getModuleInfo('FooModule'));
        $this->assertEquals($expectModule, $moduleManager->getModuleInfo('FooModule', 'Eva\\FooModule\\Module'));
        $this->assertEquals($expectModule, $moduleManager->getModuleInfo('FooModule', $expectModule));
    }

    public function testSingleModuleDefault()
    {

    }

    /*
    public function testLoad()
    {
        $moduleManager = new ModuleManager();
        $moduleManager->setDefaultPath('/bar');
        $moduleManager->loadModules(array('foo'));
        $modules = $moduleManager->getModules();
        $this->assertTrue(isset($modules['Foo']['className']));
        $this->assertEquals('Eva\Foo\Module', $modules['Foo']['className']);
        $this->assertTrue(isset($modules['Foo']['path']));
        $this->assertEquals('/bar/Foo/Module.php', $modules['Foo']['path']);
        $this->assertTrue(isset($modules['Foo']['dir']));
        $this->assertEquals('/bar/Foo', $modules['Foo']['dir']);
        $this->assertEquals('/bar/Foo/config/config.php', $modules['Foo']['moduleConfig']);
        $this->assertEquals('/bar/Foo/config/routes.backend.php', $modules['Foo']['routesBackend']);
        $this->assertEquals('/bar/Foo/config/routes.frontend.php', $modules['Foo']['routesFrontend']);

        $moduleManager->loadModules(array(
            'Blog' => array(
                'className' => 'BlogModule',
                'path' => '/test',
                'moduleConfig' => '/testconfig',
                'routesBackend' => '/testbackend',
                'routesFrontend' => '/testfrontend',
            ),
            'User' => array(
            ),
        ));
        $modules = $moduleManager->getModules();
        $this->assertTrue(isset($modules['Blog']['className']));
        $this->assertEquals('BlogModule', $modules['Blog']['className']);
        $this->assertTrue(isset($modules['Blog']['path']));
        $this->assertEquals('/test', $modules['Blog']['path']);
        $this->assertEquals('/testconfig', $modules['Blog']['moduleConfig']);
        $this->assertEquals('/testbackend', $modules['Blog']['routesBackend']);
        $this->assertEquals('/testfrontend', $modules['Blog']['routesFrontend']);
        $this->assertTrue(isset($modules['User']['className']));
        $this->assertEquals('Eva\User\Module', $modules['User']['className']);
    }

    public function testPath()
    {
        $moduleManager = new ModuleManager('foo');
        $this->assertEquals('foo', $moduleManager->getDefaultPath());
    }
    */
}
