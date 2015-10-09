\<\?php

namespace Eva\<?=$moduleName?>Tests;


class SampleTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    public function testSample()
    {
        $this->assertEquals('foo', 'foo');
    }

}