<?php
namespace Eva\EvaEngine\EvaEngineTest\Cache\Backend;

use Eva\EvaEngine\Cache\Backend\Memory;
use Phalcon\Cache\Frontend\Data;

class MemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSet()
    {
        $memory = new Memory(new Data());
        $memory->save('testkey', 'testvalue');
        $this->assertTrue($memory->exists('testkey'));
        $this->assertEquals('testvalue', $memory->get('testkey'));

        $memory->delete('testkey');
        $this->assertFalse($memory->exists('testkey'));
    }

    public function testFlush()
    {
        $memory = new Memory(new Data());
        $memory->save('testkey1', 'testvalue1');
        $memory->save('testkey2', 'testvalue2');

        $this->assertEquals(['testkey1', 'testkey2'], $memory->queryKeys());
        $memory->flush();
        $this->assertEmpty($memory->queryKeys());
    }
}
