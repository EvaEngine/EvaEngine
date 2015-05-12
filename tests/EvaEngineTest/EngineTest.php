<?php
namespace Eva\EvaEngine\EvaEngineTest;

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
        $engine->getDI()->setConfig(
            new Config(
                array(
                'session' => array(
                'adapter' => 'files',
                'options' => array()
                )
                )
            )
        );
        $this->assertEquals(get_class($engine->getDI()->get('session')), 'Phalcon\Session\Adapter\Files');

        //Custom Name
        $engine->getDI()->setConfig(
            new Config(
                array(
                'session' => array(
                'adapter' => 'Phalcon\Session\Adapter\Files',
                'options' => array()
                )
                )
            )
        );
        $this->assertEquals(get_class($engine->getDI()->get('session')), 'Phalcon\Session\Adapter\Files');

        $engine->getDI()->setConfig(
            new Config(
                array(
                'session' => array(
                'adapter' => 'foo',
                'options' => array()
                )
                )
            )
        );
        $engine->getDI()->get('session');
    }

    public function testDiCache()
    {
        $engine = new Engine();
        $engine->getDI()->setConfig(
            new Config(
                array(
                'cache' => array(
                'enable' => false,
                'globalCache' => array(
                    'frontend' => array(
                        'adapter' => 'Data',
                        'options' => array(),
                    )
                ),
                ),
                )
            )
        );
        $this->assertEquals(get_class($engine->getDI()->get('globalCache')), 'Eva\EvaEngine\Cache\Backend\Disable');
        $this->assertEquals(get_class($engine->getDI()->get('globalCache')->getFrontend()), 'Phalcon\Cache\Frontend\Data');


        $engine = new Engine();
        $engine->getDI()->setConfig(
            new Config(
                array(
                'cache' => array(
                'enable' => true,
                'globalCache' => array(
                    'enable' => false,
                    'frontend' => array(
                        'adapter' => 'Json',
                        'options' => array(),
                    )
                ),
                ),
                )
            )
        );
        $this->assertEquals(get_class($engine->getDI()->get('globalCache')), 'Eva\EvaEngine\Cache\Backend\Disable');
        $this->assertEquals(get_class($engine->getDI()->get('globalCache')->getFrontend()), 'Phalcon\Cache\Frontend\Json');


        $engine = new Engine();
        $engine->getDI()->setConfig(
            new Config(
                array(
                'cache' => array(
                'enable' => true,
                'globalCache' => array(
                    'enable' => true,
                    'frontend' => array(
                        'adapter' => 'Data',
                        'options' => array(),
                    ),
                    'backend' => array(
                        'adapter' => 'File',
                        'options' => array(
                            'cacheDir' => __DIR__ ,
                        ),
                    ),
                ),
                ),
                )
            )
        );
        $this->assertEquals(get_class($engine->getDI()->get('globalCache')), 'Phalcon\Cache\Backend\File');
        $this->assertEquals(get_class($engine->getDI()->get('globalCache')->getFrontend()), 'Phalcon\Cache\Frontend\Data');


        $engine = new Engine();
        $engine->getDI()->setConfig(
            new Config(
                array(
                'cache' => array(
                'enable' => true,
                'modelsCache' => array(
                    'enable' => true,
                    'frontend' => array(
                        'adapter' => 'None',
                        'options' => array(),
                    ),
                    'backend' => array(
                        'adapter' => 'File',
                        'options' => array(
                            'cacheDir' => __DIR__ ,
                        ),
                    ),
                ),
                ),
                )
            )
        );
        $this->assertEquals(get_class($engine->getDI()->get('modelsCache')), 'Phalcon\Cache\Backend\File');
        $this->assertEquals(get_class($engine->getDI()->get('modelsCache')->getFrontend()), 'Phalcon\Cache\Frontend\None');


        $engine = new Engine();
        $engine->getDI()->setConfig(
            new Config(
                array(
                'cache' => array(
                'enable' => true,
                'modelsCache' => array(
                    'enable' => true,
                    'frontend' => array(
                        'adapter' => 'Phalcon\Cache\Frontend\Base64',
                        'options' => array(),
                    ),
                    'backend' => array(
                        'adapter' => 'Phalcon\Cache\Backend\File',
                        'options' => array(
                            'cacheDir' => __DIR__ ,
                        ),
                    ),
                ),
                ),
                )
            )
        );
        $this->assertEquals(get_class($engine->getDI()->get('modelsCache')), 'Phalcon\Cache\Backend\File');
        $this->assertEquals(get_class($engine->getDI()->get('modelsCache')->getFrontend()), 'Phalcon\Cache\Frontend\Base64');


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
