<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Test;

use Eva\EvaEngine\Engine;

/**
 * Class TestCase
 * @package Eva\EvaEngine\Test
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Engine
     */
    protected $engine;

    public function setUp()
    {
        $engine = new Engine(getenv('APPLICATION_ROOT'), getenv('APPLICATION_NAME'));
        $engine
            ->loadModules(include getenv('APPLICATION_ROOT') . '/config/modules.' . getenv('APPLICATION_NAME') . '.php')
            ->bootstrap();
        $this->engine = $engine;
    }

    protected function tearDown()
    {
        $di = $this->engine->getDI();
        $di::reset();
        parent::tearDown();
    }
}
