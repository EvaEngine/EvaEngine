<?php

namespace Eva\EvaEngine\EvaEngineTest\Service;

use Eva\EvaEngine\Engine;
use Eva\EvaEngine\Service\Cors;
use Phalcon\Events\Manager;
use Phalcon\Config;
use Phalcon\DI;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Mvc\Application;

class CorsTest extends \PHPUnit_Framework_TestCase
{

    protected $request;

    protected $response;

    protected $di;

    protected $application;

    public function setUp()
    {
        $di = new DI();

        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/path?foo=aaa&bar=bbb';
        $_GET = array(
            '_url' => '/path',
            'foo' => 'aaa',
            'bar' => 'bbb'
        );
        $request = new Request();
        $request->setDI($di);
        $this->request = $request;

        $response = new Response();
        $response->setDI($di);
        $this->response = $response;

        $eventsManager = new Manager();

        $cors = new Cors(
            array(
                array(
                    'domain' => 'bar.com'
                )
            )
        );

        $di->set('request', $request, true);
        $di->set('response', $response, true);
        $di->set('eventsManager', $eventsManager);

        $di->set('cors', $cors);

        $this->di = $di;

        $application = new Application();
        $application->setDI($di);
        $application->setEventsManager($eventsManager);
        $this->application = $application;
    }

    public function testBasicRequest()
    {
        $this->assertEquals($this->request->getHttpHost(), 'example.com');
        $this->assertEquals(
            $this->request->getQuery(),
            array(
                '_url' => '/path',
                'foo' => 'aaa',
                'bar' => 'bbb'
            )
        );
        $this->assertEquals($this->request->getMethod(), 'GET');
    }

    public function testItDoesNotModifyResponseOnARequestWithoutOrigin()
    {
        $unmodifiedResponse = $this->response;
        $this->application->getDI()->getCors()->preflightRequests();
        $this->assertEquals($unmodifiedResponse->getHeaders(), $this->response->getHeaders());
    }

    public function testItDoesNotModifyResponseOnARequestWithSameOrigin()
    {
        $unmodifiedResponse = $this->response;
        $_SERVER['HTTP_ORIGIN'] = 'http://example.com';
        $this->application->getDI()->getCors()->preflightRequests();
        $this->assertEquals($unmodifiedResponse->getHeaders(), $this->response->getHeaders());
    }

    /**
     * @expectedException \Eva\EvaEngine\Exception\OriginNotAllowedException
     */
    public function testPreflightRequestWithOriginNotAllowed()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://foo.com';
        $this->application->getDI()->getCors()->preflightRequests();
    }

    /**
     * @expectedException \Eva\EvaEngine\Exception\OriginNotAllowedException
     */
    public function testSimpleRequestWithOriginNotAllowed()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://foo.com';
        $this->application->getDI()->getCors()->simpleRequests();
    }

    public function testPreflightRequestWithRequestedHeadersAllowed()
    {

    }

    public function testSimpleRequestWithRequestedHeadersAllowed()
    {

    }

    /**
     * @expectedException \Eva\EvaEngine\Exception\OriginNotAllowedException
     */
    public function testItDoesNotReturnAllowOriginHeaderOnPreflightRequestWithOriginNotAllowed()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://api.foo.com';
        $this->application->getDI()->getCors()->preflightRequests();
        $headers = $this->response->getHeaders();
        $this->assertFalse(isset($headers['Access-Control-Allow-Origin']));
    }

    /**
     * @expectedException \Eva\EvaEngine\Exception\OriginNotAllowedException
     */
    public function testItDoesNotReturnAllowOriginHeaderOnSimpleRequestWithOriginNotAllowed()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://foo.com';
        $this->application->getDI()->getCors()->simpleRequests();
        $headers = $this->response->getHeaders();
        $this->assertFalse(isset($headers['Access-Control-Allow-Origin']));
    }

    public function testItSetsAllowCredentialsHeaderOnValidActualRequestWhenFlagIsSetToTrue()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://bar.com';
        $this->application->getDI()->getCors()->preflightRequests();
        $this->assertEquals('true', $this->response->getHeaders()->get('Access-Control-Allow-Credentials'));
    }

    public function testItSetsAllowCredentialsHeaderOnValidActualRequestWhenFlagIsSetToFalse()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://bar.com';
        $this->application->getDI()->getCors()->preflightRequests('false');
        $this->assertEquals('false', $this->response->getHeaders()->get('Access-Control-Allow-Credentials'));
    }

    public function testItSetsAccessControlAllowOriginHeaderOnValidActualRequest()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://bar.com';
        $this->application->getDI()->getCors()->preflightRequests();
        $this->assertEquals($_SERVER['HTTP_ORIGIN'], $this->response->getHeaders()->get('Access-Control-Allow-Origin'));
    }

    public function testItSetsAccessControlAllowMethodsOnValidActualRequestWhenFlagIsSet()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://api-v2.bar.com';
        $allowMethods = 'GET, POST, PUT';
        $this->application->getDI()->getCors()->preflightRequests('true', $allowMethods);
        $this->assertEquals($allowMethods, $this->response->getHeaders()->get('Access-Control-Allow-Methods'));
    }

    public function testItSetsAccessControlAllowHeadersOnValidActualRequestWhenFlagIsSet()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://bar.com';
        $allowedHeaders = 'Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma';
        $this->application->getDI()->getCors()->preflightRequests('true', 'GET, POST, PUT', $allowedHeaders);
        $this->assertEquals($allowedHeaders, $this->response->getHeaders()->get('Access-Control-Allow-Headers'));
    }

}