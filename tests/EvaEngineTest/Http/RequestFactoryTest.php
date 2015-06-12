<?php
namespace Eva\EvaEngine\EvaEngineTest\Exception;

use Eva\EvaEngine\Http\RequestFactory;

class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildGet()
    {
        $request = RequestFactory::build('GET', 'http://example.com/', ['foo', 'bar']);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('example.com', $request->getHttpHost());
        $this->assertEquals('example.com', $request->getServerName());
        $this->assertEquals('http', $request->getScheme());
        $this->assertContains('foo', $request->getHeaders());


        $request = RequestFactory::build(
            'GET',
            'https://evaengine.com:8080/foo/bar?test_foo=test_bar',
            ['foo_header', 'bar_header']
        );
        $this->assertEquals('evaengine.com', $request->getServerName());
        $this->assertEquals('evaengine.com:8080', $request->getHttpHost());
        $this->assertEquals('https', $request->getScheme());
        $this->assertEquals('/foo/bar?test_foo=test_bar', $request->getURI());
        $this->assertEquals('test_bar', $request->getQuery('test_foo'));
    }
}
