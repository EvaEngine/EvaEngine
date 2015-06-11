<?php
namespace Eva\EvaEngine\EvaEngineTest\Mvc;

use Phalcon\Loader;
use Eva\EvaEngine\Mvc\View;

class ViewTest extends \PHPUnit_Framework_TestCase
{
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
    }

    public function testLayoutDir()
    {
        $view = new View();
        $view->setViewsDir('/test/view');
        $view->setLayoutsAbsoluteDir('/foo/bar');
        $this->assertEquals('../../foo/bar/', $view->getLayoutsDir());
    }
}
