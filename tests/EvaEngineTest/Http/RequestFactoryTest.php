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

    public function testBuildPost()
    {
        $request = RequestFactory::build('POST', 'http://example.com/', [], [
            'key' => 'value',
            'post_key' => 'post_value'
        ]);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('post_value', $request->getPost('post_key'));
        $this->assertEquals('key=value&post_key=post_value', $request->getRawBody());
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeader('Content-Type'));
        $this->assertEmpty($request->getPut());

        $request = RequestFactory::build('POST', 'http://evaengine.com/', [], 'some string');
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('some string', $request->getRawBody());
        $this->assertEmpty($request->getPost());
    }

    public function testBuildPut()
    {
        $request = RequestFactory::build('PUT', 'http://example.com/', [], [
            'key' => 'value',
            'put_key' => 'put_value'
        ]);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('put_value', $request->getPut('put_key'));
        $this->assertEquals('key=value&put_key=put_value', $request->getRawBody());
        $this->assertEmpty($request->getPost());

        $request = RequestFactory::build('PUT', 'http://evaengine.com/', [], 'some string');
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('some string', $request->getRawBody());
        $this->assertEmpty($request->getPost());
    }
}
