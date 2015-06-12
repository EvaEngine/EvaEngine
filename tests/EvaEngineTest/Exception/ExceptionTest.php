<?php
namespace Eva\EvaEngine\EvaEngineTest\Exception;

use Eva\EvaEngine\Exception;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCode()
    {
        $exception = new Exception\StandardException('test');
        $this->assertEquals(139002846, $exception->getCode());

        $exception = new Exception\LogicException('test');
        $this->assertEquals(139003568, $exception->getCode());
    }

    public function testStatusCode()
    {
        $exception = new Exception\StandardException('test', 123, null, 502);
        $this->assertEquals(123, $exception->getCode());
        $this->assertEquals(502, $exception->getStatusCode());

        $exception = new Exception\StandardException('test', 123, 500);
        $this->assertEquals(500, $exception->getStatusCode());
    }

    /*
    public function testCodeGroup()
    {
        $exception = \Mockery::mock('Eva\EvaEngine\Exception\StandardException');
    }
    */
}
