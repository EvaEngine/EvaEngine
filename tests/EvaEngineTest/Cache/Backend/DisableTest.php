<?php
namespace Eva\EvaEngine\EvaEngineTest\Cache\Backend;

use Eva\EvaEngine\Cache\Backend\Disable;
use Phalcon\Cache\Frontend\Data;
use Phalcon\Di;

class DisableTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSet()
    {
        $memory = new Disable(new Data());
        $memory->save('testkey', 'testvalue');
        $this->assertFalse($memory->exists('testkey'));
        $this->assertEquals(null, $memory->get('testkey'));
    }
}
