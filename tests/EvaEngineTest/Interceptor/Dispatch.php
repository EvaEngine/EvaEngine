<?php
namespace Eva\EvaEngine\EvaEngineTest\Interceptor;

use Eva\EvaEngine\Interceptor\Dispatch as DispatchInterceptor;
use Phalcon\Events\Event;
use Phalcon\Http\Request;
use Eva\EvaEngine\Engine;
use Phalcon\Cache\BackendInterface as CacheInterface;

class Dispatch extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    public function testKeyGenerate()
    {
        //$_SERVER[''];
        //$request = new Request();
        $this->assertEquals('foo', 'bar');
    }

    public function testTextCache()
    {
    }

    public function testJsonpCache()
    {
    }
}
