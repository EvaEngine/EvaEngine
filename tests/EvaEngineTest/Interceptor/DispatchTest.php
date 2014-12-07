<?php
namespace Eva\EvaEngine\EvaEngineTest\Interceptor;

use Eva\EvaEngine\Interceptor\Dispatch as DispatchInterceptor;
use Phalcon\Events\Event;
use Phalcon\Http\Request;
use Eva\EvaEngine\Engine;
use Phalcon\Cache\BackendInterface as CacheInterface;

class DispatchTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/path?foo=aaa&bar=bbb';
        $_GET = array(
            '_url' => '/path',
            'foo'  => 'aaa',
            'bar' => 'bbb'
        );
        $this->request = new Request();
    }

    public function testBasicRequest()
    {
        $this->assertEquals($this->request->getHttpHost(), 'example.com');
        $this->assertEquals($this->request->getQuery(), array(
            '_url' => '/path',
            'foo'  => 'aaa',
            'bar' => 'bbb'
        ));
        $this->assertEquals($this->request->getMethod(), 'GET');
    }

    public function testKeyGenerate()
    {
        $interceptor = new DispatchInterceptor();
        $cacheKeys = $interceptor->generateCacheKeys($this->request, array());
        $expectedKey = md5('example.com' . '/path' . json_encode(array(
            'foo'  => 'aaa',
            'bar' => 'bbb'
        )));
        $this->assertEquals($cacheKeys, array($expectedKey . '_h', $expectedKey . '_b'));
        $this->assertEquals($interceptor->getCacheHeadersKey(), $expectedKey . '_h');
        $this->assertEquals($interceptor->getCacheBodyKey(), $expectedKey . '_b');

        //Test igore
        $cacheKeys = $interceptor->generateCacheKeys($this->request, array('foo'));
        $expectedKey = md5('example.com' . '/path' . json_encode(array('bar' => 'bbb')));
        $this->assertEquals($cacheKeys, array($expectedKey . '_h', $expectedKey . '_b'));
        $this->assertEquals($interceptor->getCacheHeadersKey(), $expectedKey . '_h');
        $this->assertEquals($interceptor->getCacheBodyKey(), $expectedKey . '_b');
    }

    public function testTextCache()
    {
    }

    public function testJsonpCache()
    {
    }
}
