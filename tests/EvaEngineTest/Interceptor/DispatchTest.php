<?php
namespace Eva\EvaEngine\EvaEngineTest\Interceptor;

use Eva\EvaEngine\Interceptor\Dispatch as DispatchInterceptor;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\DI;
use Phalcon\Cache\Frontend\Output as FrontendCache;
use Eva\EvaEngine\Cache\Backend\Memory as BackendCache;
use Phalcon\Config;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Application;

class DispatchTest extends \PHPUnit_Framework_TestCase
{
    protected $request;

    protected $di;

    protected $application;

    protected $dispatcher;

    /**
     *
     */
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

        $dispatcher = new Dispatcher();
        $dispatcher->setDI($di);
        $this->dispatcher = $dispatcher;

        $cache = new BackendCache(new FrontendCache());
        $di->set('viewCache', $cache);

        $config = new Config(
            array(
            'cache' => array(
                'enable' => true
            )
            )
        );
        $di->set('config', $config);

        $eventsManager = new Manager();

        $di->set('request', $request, true);
        $di->set('response', $response, true);
        $di->set('dispatcher', $dispatcher, true);
        $di->set('eventsManager', $eventsManager);
        $this->di = $di;

        $application = new Application();
        $application->setDI($di);
        $application->setEventsManager($eventsManager);
        $this->application = $application;
    }


    public function testDispatcherParams()
    {
        $dispatcher = new Dispatcher();
        $interceptor = new DispatchInterceptor();
        $this->assertEquals($interceptor->getInterceptorParams($dispatcher), array());

        $dispatcher = new Dispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=-1'
            )
        );
        $this->assertEquals($interceptor->getInterceptorParams($dispatcher), array());

        $dispatcher = new Dispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=60'
            )
        );
        $this->assertEquals(
            $interceptor->getInterceptorParams($dispatcher),
            array(
            'lifetime' => 60,
            'methods' => array('get'),
            'ignore_query_keys' => array('_'),
            'jsonp_callback_key' => 'callback',
            'format' => 'text',
            )
        );


        $dispatcher = new Dispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100&methods=get|post&ignore_query_keys=api_key|_&jsonp_callback_key=callback&format=jsonp'
            )
        );
        $this->assertEquals(
            $interceptor->getInterceptorParams($dispatcher),
            array(
            'lifetime' => 100,
            'methods' => array('get', 'post'),
            'ignore_query_keys' => array('api_key', '_'),
            'jsonp_callback_key' => 'callback',
            'format' => 'jsonp',
            )
        );
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

    public function testKeyGenerate()
    {
        $interceptor = new DispatchInterceptor();
        $cacheKeys = $interceptor->generateCacheKeys($this->request, array());
        $expectedKey = md5(
            'example.com' . '/path' . json_encode(
                array(
                'foo' => 'aaa',
                'bar' => 'bbb'
                )
            )
        );
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

    public function testJsonpToJson()
    {
        $this->assertEquals('', DispatchInterceptor::changeJsonpToJson('', 'abc'));

        $this->assertEquals('{"foo":"bar"}', DispatchInterceptor::changeJsonpToJson('{"foo":"bar"}', 'abc'));

        $this->assertEquals('{"foo":"bar"}', DispatchInterceptor::changeJsonpToJson('abc({"foo":"bar"})', 'abc'));

        $this->assertEquals('{"foo":"bar"}', DispatchInterceptor::changeJsonpToJson('  abc({"foo":"bar"});  ', 'abc'));
    }


    public function testTextCacheWithNoDispatchParams()
    {
        $interceptor = new DispatchInterceptor();
        $dispatcher = $this->di->getDispatcher();
        $this->assertEquals(true, $interceptor->injectInterceptor($dispatcher));
    }

    public function testTextCacheMissing()
    {
        $interceptor = new DispatchInterceptor();
        /**
 * @var Dispatcher $dispatcher
*/
        $dispatcher = $this->di->getDispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100'
            )
        );
        $this->assertEquals(true, $interceptor->injectInterceptor($dispatcher));
    }

    public function testTextCacheBodyOnly()
    {
        $this->di->getViewCache()->save('d6bd338ec8eb8666f3d054566f335039_b', 'foo');
        $interceptor = new DispatchInterceptor();
        /**
 * @var Dispatcher $dispatcher
*/
        $dispatcher = $this->di->getDispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100'
            )
        );
        $this->assertEquals(true, $interceptor->injectInterceptor($dispatcher));
    }


    public function testTextCacheHit()
    {
        $this->di->getViewCache()->save('d6bd338ec8eb8666f3d054566f335039_h', '{"foo":"header"}');
        $this->di->getViewCache()->save('d6bd338ec8eb8666f3d054566f335039_b', 'bar');

        $interceptor = new DispatchInterceptor();
        /**
 * @var Dispatcher $dispatcher
*/
        $dispatcher = $this->di->getDispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100'
            )
        );
        $this->assertEquals(false, $interceptor->injectInterceptor($dispatcher));
        $this->assertEquals('bar', $this->di->getResponse()->getContent());
    }

    public function testTextCacheGenerate()
    {
        $this->di->getViewCache()->flush();
        /**
 * @var Response $response
*/
        $response = $this->di->getResponse();
        $response->setHeader('Content-Type', 'test-type');
        $response->setHeader('More-Header', 'test-more-header');
        $interceptor = new DispatchInterceptor();
        /**
 * @var Dispatcher $dispatcher
*/
        $dispatcher = $this->di->getDispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100'
            )
        );
        $this->assertEquals(true, $interceptor->injectInterceptor($dispatcher));

        $this->di->getResponse()->setContent('bar');
        $this->di->getEventsManager()->fire('application:beforeSendResponse', $this->application);
        $this->assertEquals('bar', $this->di->getViewCache()->get('d6bd338ec8eb8666f3d054566f335039_b'));
        $header = $this->di->getViewCache()->get('d6bd338ec8eb8666f3d054566f335039_h');
        $this->assertJson($header);
        $header = json_decode($header, true);
        $this->assertEquals('test-type', $header['Content-Type']);
        //Only cache allowed header
        $this->assertArrayNotHasKey('More-Header', $header);
    }

    public function testTextCacheDisabledByUri()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/path?foo=aaa&bar=bbb&_eva_refresh_dispatch_cache=1';
        $_GET = array(
            '_url' => '/path',
            'foo' => 'aaa',
            'bar' => 'bbb',
            '_eva_refresh_dispatch_cache' => 1
        );
        $request = new Request();
        $this->di->set('request', $request);

        $this->di->getViewCache()->flush();

        $interceptor = new DispatchInterceptor();
        /**
 * @var Dispatcher $dispatcher
*/
        $dispatcher = $this->di->getDispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100'
            )
        );
        $this->assertEquals(true, $interceptor->injectInterceptor($dispatcher));
        $this->assertEquals($interceptor->getCacheBodyKey(), 'd6bd338ec8eb8666f3d054566f335039_b');

        //var_dump($this->di->getDispatcher()->getParams());

    }

    public function testJsonpCacheGenerate()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/path?foo=aaa&bar=bbb&callback=testcallback&_=123456';
        $_GET = array(
            '_url' => '/path',
            'foo' => 'aaa',
            'bar' => 'bbb',
            'callback' => 'testcallback',
            '_' => '123456',
        );
        $request = new Request();



        $this->di->set('request', $request);

        $this->di->getViewCache()->flush();

        $interceptor = new DispatchInterceptor();
        /**
 * @var Dispatcher $dispatcher
*/
        $dispatcher = $this->di->getDispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100&format=jsonp'
            )
        );
        $this->assertEquals(true, $interceptor->injectInterceptor($dispatcher));
        $this->assertEquals($interceptor->getCacheBodyKey(), 'd6bd338ec8eb8666f3d054566f335039_b');
        $this->di->getResponse()->setContent('testcallback({"foo":"bar"});');
        $this->di->getEventsManager()->fire('application:beforeSendResponse', $this->application);
        $this->assertEquals('{"foo":"bar"}', $this->di->getViewCache()->get('d6bd338ec8eb8666f3d054566f335039_b'));
    }

    public function testJsonpCacheHit()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/path?foo=aaa&bar=bbb&callback=testcallback&_=123456';
        $_GET = array(
            '_url' => '/path',
            'foo' => 'aaa',
            'bar' => 'bbb',
            'callback' => 'testcallback',
            '_' => '123456',
        );
        $request = new Request();


        $this->di->set('request', $request);

        $this->di->getViewCache()->flush();

        $this->di->getViewCache()->save('d6bd338ec8eb8666f3d054566f335039_b', '{"foo":"bar"}');
        $this->di->getViewCache()->save('d6bd338ec8eb8666f3d054566f335039_h', '{"Content-Type":"application\/json;+charset=utf-8","X-EvaEngine-Interceptor-Cache":"2014-12-09T06:45:42+0100"}');

        $interceptor = new DispatchInterceptor();
        /**
 * @var Dispatcher $dispatcher
*/
        $dispatcher = $this->di->getDispatcher();
        $dispatcher->setParams(
            array(
            '_dispatch_cache' => 'lifetime=100&format=jsonp'
            )
        );
        $this->assertEquals(false, $interceptor->injectInterceptor($dispatcher));
        $this->assertEquals('testcallback({"foo":"bar"})', $this->di->getResponse()->getContent());
    }
}
