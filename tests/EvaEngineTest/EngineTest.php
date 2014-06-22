<?php
namespace EvaEngineTest;

use Eva\EvaEngine\Engine;
use Eva\EvaEngine\Exception;
use Phalcon\Config;

class EngineTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    /**
    * @expectedException Eva\EvaEngine\Exception\RuntimeException
    */
    public function testDiSession()
    {
        $engine = new Engine();
        $engine->getDI()->setConfig(new Config(array(
            'session' => array(
                'adapter' => 'files',
                'options' => array()
            )
        )));
        $this->assertEquals(get_class($engine->getDI()->get('session')), 'Phalcon\Session\Adapter\Files');

        $engine->getDI()->setConfig(new Config(array(
            'session' => array(
                'adapter' => 'foo',
                'options' => array()
            )
        )));
        $engine->getDI()->get('session');
    }

    /**
    * @expectedException Eva\EvaEngine\Exception\RuntimeException
    */
    public function testDiEmptyConfig()
    {
        $engine = new Engine();
        $engine->getDI()->getConfig();
    }

    public function testApplication()
    {
        $engine = new Engine();
        $this->assertTrue($engine->getApplication() instanceof \Phalcon\Mvc\Application);
    }

    public function testPath()
    {
        $engine = new Engine('foo');
        $this->assertEquals('foo', $engine->getAppRoot());
        $this->assertEquals('foo/config', $engine->getConfigPath());
        $this->assertEquals('foo/modules', $engine->getModulesPath());

        $engine->setConfigPath('bar');
        $engine->setModulesPath('bar');
        $this->assertEquals('bar', $engine->getConfigPath());
        $this->assertEquals('bar', $engine->getModulesPath());

    }
}
