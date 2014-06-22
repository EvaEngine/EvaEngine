<?php
namespace ModuleManagerTest;

use Eva\EvaEngine\Module\Manager as ModuleManager;

class ModuleManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

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
}
