<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Interceptor;

use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Events\Event;
use Phalcon\Http\Request;
use Phalcon\Cache\BackendInterface as CacheInterface;

/**
 * Dispatch Interceptor for http cache
 * Class Dispatch
 * @package Eva\EvaEngine\Interceptor
 */
class Dispatch
{

    /**
     * default params key for dispatcher
     */
    const INTERCEPTOR_KEY = '_dispatch_cache';

    /**
     * default debug flag in http header when cache hit
     */
    const CACHE_HEADER_FLAG = 'X-EvaEngine-Interceptor-Cache';

    /**
     * default debug query key, url contains this key will make cache re-genrated
     * @var string
     */
    protected $debugQueryKey = '_eva_refresh_dispatch_cache';

    /**
     * HTTP header keys allow to cache
     * WARNING: DONOT cache Set-Cookies!
     * @var array
     */
    protected $cachableHeaderKeys = array(
        'Content-Type'
    );

    /**
     * Cache key for http header
     * @var string
     */
    protected $cacheHeadersKey;

    /**
     * Cache key for http body
     * @var string
     */
    protected $cacheBodyKey;

    /**
     * @return string
     */
    public function getDebugQueryKey()
    {
        return $this->debugQueryKey;
    }

    /**
     * @param $debugQueryKey
     * @return $this
     */
    public function setDebugQueryKey($debugQueryKey)
    {
        $this->debugQueryKey = $debugQueryKey;
        return $this;
    }

    /**
     * @param array $cachableHeaderKeys
     * @return $this
     */
    public function setCachableHeaderKeys(array $cachableHeaderKeys)
    {
        $this->cachableHeaderKeys = $cachableHeaderKeys;
        return $this;
    }

    /**
     * @return array
     */
    public function getCachableHeaderKeys()
    {
        return $this->cachableHeaderKeys;
    }

    /**
     * @return string
     */
    public function getCacheHeadersKey()
    {
        return $this->cacheHeadersKey;
    }

    /**
     * @return string
     */
    public function getCacheBodyKey()
    {
        return $this->cacheBodyKey;
    }

    /**
     * Intercept a input http request
     * @param Request        $request
     * @param array          $params
     * @param CacheInterface $cache
     * @return bool Will return true if cache hit.
     */
    protected function intercept(Request $request, array $params, CacheInterface $cache)
    {
        $ignores = $params['ignore_query_keys'];
        array_push($ignores, $params['jsonp_callback_key']);
        $debugQueryKey = $this->getDebugQueryKey();
        array_push($ignores, $debugQueryKey);
        list($headersKey, $bodyKey) = $this->generateCacheKeys($request, $ignores);

        $hasCache = false;
        $headersCache = $bodyCache = '';
        if (!$request->getQuery($debugQueryKey)) {
            $bodyCache = $cache->get($bodyKey);
            $headersCache = $cache->get($headersKey);
            $hasCache = $headersCache && $bodyCache;
        }

        if ($hasCache) {
            /**
 * @var \Phalcon\Http\ResponseInterface $response
*/
            $response = $request->getDI()->getResponse();

            if ($headersCache) {
                //$headersCache = unserialize($headersCache);
                $headersCache = json_decode($headersCache);
                foreach ($headersCache as $key => $headerValue) {
                    if (is_int($key)) {
                        $response->setRawHeader($headerValue);
                    } else {
                        $response->setHeader($key, $headerValue);
                    }
                }
            }

            $callbackKey = $params['jsonp_callback_key'];
            if ($params['format'] == 'jsonp' && $callbackKey && ($callbackValue = $request->getQuery($callbackKey))) {
                $bodyCache = $callbackValue . '(' . $bodyCache . ')';
            }
            $response->setContent($bodyCache);
            return true;
        }
        // cache missing
        return false;
    }

    /**
     * Generate cache key pair (for response header / body) by Host + Uri + Allowed Queries
     * @param Request $request
     * @param array   $ignores
     * @return array
     */
    public function generateCacheKeys(Request $request, array $ignores = array())
    {
        list($urlPath) = explode('?', $request->getURI());
        $urlQuery = $request->getQuery();

        //NOTE: remove Phalcon default url rewrite param here
        unset($urlQuery['_url']);

        if ($ignores) {
            foreach ($ignores as $ignoreKey) {
                unset($urlQuery[$ignoreKey]);
            }
        }

        $cacheKeyPrefix = $request->getHttpHost() . $urlPath . json_encode($urlQuery);
        $cacheKeyPrefix = md5($cacheKeyPrefix);
        $bodyKey = $cacheKeyPrefix . '_b';
        $headersKey = $cacheKeyPrefix . '_h';
        $this->cacheHeadersKey = $headersKey;
        $this->cacheBodyKey = $bodyKey;
        return array($headersKey, $bodyKey);
    }

    /**
     * Parse Dispatcher params to array
     * @param DispatcherInterface $dispatcher
     * @return array
     */
    public function getInterceptorParams(DispatcherInterface $dispatcher)
    {
        $interceptorConfig = strtolower($dispatcher->getParam(self::INTERCEPTOR_KEY));

        if (!$interceptorConfig) {
            return array();
        }

        parse_str($interceptorConfig, $interceptorParams);
        //Make default
        $interceptorParams = array_merge(
            array(
            'lifetime' => 0,
            'methods' => 'get',
            'ignore_query_keys' => '_',
            'jsonp_callback_key' => 'callback',
            'format' => 'text', //allow text | jsonp
            ),
            $interceptorParams
        );

        $lifetime = $interceptorParams['lifetime'] = (int)$interceptorParams['lifetime'];
        if ($lifetime <= 0) {
            return array();
        }

        $methodsAllow = $interceptorParams['methods'] ? explode('|', $interceptorParams['methods']) : array('get');
        $interceptorParams['methods'] = $methodsAllow;

        $ignoreQueryKeys = $interceptorParams['ignore_query_keys'] ?
            explode('|', $interceptorParams['ignore_query_keys']) : array();
        $interceptorParams['ignore_query_keys'] = $ignoreQueryKeys;

        return $interceptorParams;
    }

    /**
     * @param DispatcherInterface $dispatcher
     * @return bool true if cache missed(intercepter injected), false if cache hit(intercepter not injected)
     */
    public function injectInterceptor(DispatcherInterface $dispatcher)
    {
        /**
 * @var \Phalcon\DI $di
*/
        $di = $dispatcher->getDI();
        $config = $di->getConfig();
        // cache is disable
        if (!$config->cache->enable) {
            return true;
        }

        $params = $this->getInterceptorParams($dispatcher);
        if (!$params) {
            return true;
        }

        $methodsAllow = $params['methods'];
        /**
 * @var \Phalcon\Http\Request $request
*/
        $request = $di->getRequest();
        $requestMethod = strtolower($request->getMethod());
        if (false === in_array($requestMethod, $methodsAllow)) {
            return true;
        }

        /**
 * @var \Phalcon\Cache\Backend $cache
*/
        $cache = $di->getViewCache();
        $interceptResult = $this->intercept($request, $params, $cache);

        //cache key matched, response already prepared
        if (true === $interceptResult) {
            $di->getResponse()->send();
            return false;
        }

        $self = $this;
        //Cache missed
        /**
 * @var \Phalcon\Events\Manager $eventsManager
*/
        $eventsManager = $di->getEventsManager();
        $eventsManager->attach(
            'application:beforeSendResponse',
            function ($event, $application) use ($self, $params) {
                $bodyKey = $self->getCacheBodyKey();
                $headersKey = $self->getCacheHeadersKey();
                if (!$bodyKey || !$headersKey) {
                    return true;
                }


                /**
            * @var \Phalcon\Http\ResponseInterface $response
            */
                $response = $application->getDI()->getResponse();
                $body = $response->getContent();

                $headers = $response->getHeaders()->toArray();
                $headersCache = array();
                if ($headers) {
                    //Filter allowed headers
                    $headersCache = array_intersect_key($headers, array_flip($self->getCachableHeaderKeys()));
                }
                $headersCache[Dispatch::CACHE_HEADER_FLAG] = date(DATE_ISO8601);
                $cache = $application->getDI()->getViewCache();

                $request = $application->getDI()->getRequest();
                $callbackKey = $params['jsonp_callback_key'];
                //Jsonp change to json
                if ($params['format'] == 'jsonp'
                    && $callbackKey
                    && ($callbackValue = $request->getQuery($callbackKey))
                ) {
                    $body = Dispatch::changeJsonpToJson($body, $callbackValue);
                }
                $cache->save($bodyKey, $body, $params['lifetime']);
                //$cache->save($headersKey, serialize($headersCache), $params['lifetime']);
                $cache->save($headersKey, json_encode($headersCache), $params['lifetime']);
                return true;
            }
        );
        return true;
    }

    /**
     * Change jsonp string to json
     * @param $body
     * @param $callback
     * @return string
     */
    public static function changeJsonpToJson($body, $callback)
    {
        $body = trim($body);
        if (strpos($body, $callback) !== 0) {
            return $body;
        }

        $body = substr($body, strlen($callback));
        $body = rtrim($body, ';');
        $body = ltrim($body, "(");
        $body = rtrim($body, ")");
        return $body;
    }


    /**
     * @param Event               $event
     * @param DispatcherInterface $dispatcher
     * @return bool
     */
    public function beforeExecuteRoute(Event $event, DispatcherInterface $dispatcher)
    {
        return $this->injectInterceptor($dispatcher);
    }
}
