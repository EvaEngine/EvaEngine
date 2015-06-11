<?php
namespace Eva\EvaEngine\EvaEngineTest\Mvc;

use Eva\EvaEngine\Module\Manager as ModuleManager;
use Phalcon\Config;
use Phalcon\Di;
use Phalcon\Events\Manager;
use Phalcon\Loader;
use Eva\EvaEngine\Mvc\View;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    protected $modulePath;

    protected $di;

    public function setUp()
    {
        $ds = DIRECTORY_SEPARATOR;
        $this->modulePath = __DIR__ . "{$ds}..{$ds}Module{$ds}TestAsset";

        //Clear di if other test cases created one
        if (Di::getDefault()) {
            Di::getDefault()->reset();
        }
        $this->di = $di = new Di();
        $di->set('moduleManager', function () {
            $moduleManager = new ModuleManager($this->modulePath);
            $moduleManager->loadModules(
                array(
                    'FooModule',
                    'BarModule',
                    'ThirdModule',
                )
            );
            return $moduleManager;
        });

        $di->set('config', function () {
            return new Config(['debug' => 0]);
        });
    }

    public function testNormalizePath()
    {
        $this->assertEquals('', View::normalizePath('', '/'));
        $this->assertEquals('/', View::normalizePath('/', '/'));
        $this->assertEquals('/', View::normalizePath('//', '/'));
        $this->assertEquals('/', View::normalizePath('\\', '/'));
        $this->assertEquals('/', View::normalizePath('\\', '/'));

        $this->assertEquals('/foo/', View::normalizePath('foo', '/'));
        $this->assertEquals('/this/a/test/is/', View::normalizePath('this/is/../a/./test/.///is', '/'));
    }

    public function testRelativePath()
    {
        $this->assertEquals('', View::relativePath('foo', 'foo', '/'));
        $this->assertEquals('', View::relativePath('foo/', 'foo', '/'));
        $this->assertEquals('', View::relativePath('foo', 'foo/', '/'));
        $this->assertEquals('', View::relativePath('/foo/', 'foo', '/'));

        $this->assertEquals('', View::relativePath('/', '//', '/'));

        $this->assertEquals('../', View::relativePath('/var/www/foo', '/var/www', '/'));
        $this->assertEquals('../../', View::relativePath('/var/www/foo/bar', '/var/www', '/'));

        $this->assertEquals('foo/', View::relativePath('/var/www', '/var/www/foo', '/'));
        $this->assertEquals('foo/bar/', View::relativePath('/var/www', '/var/www/foo/bar', '/'));

        $this->assertEquals('../bar/', View::relativePath('/var/www/foo', '/var/www/bar', '/'));
        $this->assertEquals('../bar/bar2/', View::relativePath('/var/www/foo', '/var/www/bar/bar2', '/'));

        $this->assertEquals('../../../d/e/f/', View::relativePath('/a/b/c', '/d/e/f', '/'));

        $this->assertEquals('foo/bar/', View::relativePath('/', '/foo/bar', '/'));
    }

    public function testLayoutDir()
    {
        //No views dir will use root path as default
        $view = new View();
        $view->setLayoutsAbsoluteDir('/foo/bar');
        $this->assertEquals('foo/bar/', $view->getLayoutsDir());

        $view = new View();
        $view->setViewsDir('/test/view');
        $view->setLayoutsAbsoluteDir('/foo/bar');
        $this->assertEquals('../../foo/bar/', $view->getLayoutsDir());
    }


    public function testPartialDir()
    {
        //No views dir will use root path as default
        $view = new View();
        $view->setPartialsAbsoluteDir('/foo/bar');
        $this->assertEquals('foo/bar/', $view->getPartialsDir());

        $view = new View();
        $view->setViewsDir('/test/view');
        $view->setPartialsAbsoluteDir('/foo/bar');
        $this->assertEquals('../../foo/bar/', $view->getPartialsDir());
    }

    public function testModuleViewsDir()
    {
        $view = new View();
        $view->setModuleViewsDir('FooModule', DIRECTORY_SEPARATOR . 'views');
        $this->assertEquals(
            View::normalizePath($this->modulePath . DIRECTORY_SEPARATOR . 'FooModule' . DIRECTORY_SEPARATOR . 'views'),
            $view->getViewsDir()
        );
    }

    public function testModuleLayout()
    {
        $ds = DIRECTORY_SEPARATOR;
        $view = new View();
        $view->setModuleViewsDir('FooModule', "{$ds}views");
        $view->setModuleLayout('FooModule', "{$ds}views{$ds}layout_dir{$ds}layout_name");
        $this->assertEquals(
            "layout_dir{$ds}",
            $view->getLayoutsDir()
        );
        $this->assertEquals("layout_name", $view->getLayout());

        $view = new View();
        $view->setModuleViewsDir('FooModule', "{$ds}views");
        $view->setModuleLayout('BarModule', "{$ds}views{$ds}layout_dir{$ds}layout_name");
        $this->assertEquals(
            "..{$ds}..{$ds}BarModule{$ds}views{$ds}layout_dir{$ds}",
            $view->getLayoutsDir()
        );
        $this->assertEquals("layout_name", $view->getLayout());
    }

    public function testModulePartial()
    {
        $ds = DIRECTORY_SEPARATOR;
        $view = new View();
        $view->setModuleViewsDir('FooModule', "{$ds}views");
        $view->setModulePartialsDir('FooModule', "{$ds}views{$ds}partial_dir");
        $this->assertEquals(
            "partial_dir{$ds}",
            $view->getPartialsDir()
        );

        $view = new View();
        $view->setModuleViewsDir('FooModule', "{$ds}views");
        $view->setModulePartialsDir('BarModule', "{$ds}views{$ds}partial_dir");
        $this->assertEquals(
            "..{$ds}..{$ds}BarModule{$ds}views{$ds}partial_dir{$ds}",
            $view->getPartialsDir()
        );
    }

    public function testModuleViewDirReset()
    {
        $ds = DIRECTORY_SEPARATOR;
        $view = new View();
        $view->setModuleViewsDir('FooModule', "{$ds}views");
        $view->setModulePartialsDir('FooModule', "{$ds}views{$ds}partial_dir");
        $view->setModuleLayout('FooModule', "{$ds}views{$ds}layout_dir{$ds}layout_name");
        $view->setModuleViewsDir('BarModule', "{$ds}views");

        $this->assertEquals(
            View::normalizePath($this->modulePath . DIRECTORY_SEPARATOR . 'BarModule' . DIRECTORY_SEPARATOR . 'views'),
            $view->getViewsDir()
        );
        $this->assertEquals(
            "..{$ds}..{$ds}FooModule{$ds}views{$ds}layout_dir{$ds}",
            $view->getLayoutsDir()
        );
        $this->assertEquals(
            "..{$ds}..{$ds}FooModule{$ds}views{$ds}partial_dir{$ds}",
            $view->getPartialsDir()
        );
    }

    public function testSilentRender()
    {
        $view = new View();
        $view->setEventsManager(new Manager());
        $this->assertInstanceOf('Phalcon\Mvc\View', $view->render('foo', 'bar'));
    }

    /**
     * @expectedException Eva\EvaEngine\Exception\IOException
     */
    public function testRenderException()
    {
        View::enableRenderException();
        $view = new View();
        $view->setEventsManager(new Manager());
        $view->render('foo', 'bar');
    }
}
