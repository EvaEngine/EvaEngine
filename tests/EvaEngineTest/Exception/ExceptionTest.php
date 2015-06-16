<?php
namespace Eva\EvaEngine\EvaEngineTest\Exception;

use Eva\EvaEngine\Exception;
use Phalcon\Text;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{

    /*
     * Test random class name hash to a int whether conflict
     */
    public function testStringToInt()
    {
        $strArray = [];
        $numberArray = [];
        for ($i = 0; $i < 9999; $i++) {
            /*
            $className = Text::random(Text::RANDOM_ALNUM, rand(3, 10)) . '\\' .
                Text::random(Text::RANDOM_ALNUM, rand(3, 10)) . '\\' .
                Text::random(Text::RANDOM_ALNUM, rand(3, 10)) . '\\' .
            */
            $className = '\\' . Text::random(Text::RANDOM_ALNUM, rand(3, 10)) . 'Exception';
            $strArray[] = $className;
            $numberArray[] = Exception\StandardException::classNameToCode($className);
        }

        $total = count(array_unique($strArray));
        $real =  count(array_unique($numberArray));
        p(sprintf("Total: %d, Real: %d, Conflict percent:%d", $total, $real, ($total - $real) / $total * 100));
    }

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

    public function testCodeGroup()
    {
        $exception = \Mockery::mock('Eva\EvaEngine\Exception\StandardException[getCode]', ['test']);
        $code = (string)$exception->getCode();
        $this->assertEquals(9, strlen($code));
        $this->assertEquals('00', $code[3] . $code[4]);
    }
}
