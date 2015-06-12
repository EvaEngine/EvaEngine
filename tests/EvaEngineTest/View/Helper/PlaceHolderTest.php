<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Eva\EvaEngine\EvaEngineTest\View\Helper;

use Eva\EvaEngine\View\Helper\Placeholder;

class PlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Placeholder
     */
    public $placeholder;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $this->placeholder = new Placeholder();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->placeholder);
    }

    /**
     * @expectedException \Eva\EvaEngine\Exception\InvalidArgumentException
     */
    public function testBlockException()
    {
        $this->placeholder->block();
    }

    public function testBlock()
    {
        $this->placeholder->block('test');
        $this->assertTrue($this->placeholder->containerExists('test'));
        $this->assertInstanceOf('Eva\EvaEngine\View\Helper\Placeholder\Container', $this->placeholder->getContainer('test'));
    }

}
