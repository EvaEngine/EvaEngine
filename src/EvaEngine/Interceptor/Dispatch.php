<?php

namespace Eva\EvaEngine\Interceptor;

// +----------------------------------------------------------------------
// | [evaengine]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-8-28 15:07
// +----------------------------------------------------------------------

use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Events\Event;
use Phalcon\Http\Request;
use Eva\EvaEngine\Engine;
use Phalcon\Cache\BackendInterface as CacheInterface;

class Dispatch
{

    const INTERCEPTOR_KEY = '_dispatch_cache';

    const CACHE_HEADER_FLAG = 'X-EvaEngine-Interceptor-Cache';

    protected $debugQueryKey = '_eva_refresh_dispatch_cache';

    protected $cachableHeaderKeys = array(
        'Content-Type'
    );

    protected $cacheHeadersKey;

    protected $cacheBodyKey;

    public function getDebugQueryKey()
    {
        return $this->debugQueryKey;
    }

    public function setDebugQueryKey($debugQueryKey)
    {
        $this->debugQueryKey = $debugQueryKey;
        return $this;
    }

    public function setCachableHeaderKeys(array $cachableHeaderKeys)
    {
        $this->cachableHeaderKeys = $cachableHeaderKeys;
        return $this;
    }

    public function getCachableHeaderKeys()
    {
        return $this->cachableHeaderKeys;
    }

    public function getCacheHeadersKey()
    {
        return $this->cacheHeadersKey;
    }

    public function getCacheBodyKey()
    {
        return $this->cacheBodyKey;
    }

    protected function intercept(Request $request, array $params, CacheInterface $cache)
    {
        $ignores = $params['ignore_query_keys'];
        array_push($ignores, $params['jsonp_callback_key']);
        $debugQueryKey = $this->getDebugQueryKey();
        array_push($ignores, $debugQueryKey);
        list($headersKey, $bodyKey) = $this->generateCacheKeys($request, $ignores);

        $hasCache = false;
        if (!$request->getQuery($debugQueryKey)) {
            $bodyCache = $cache->get($bodyKey);
            $headersCache = $cache->get($headersKey);
            $hasCache = $headersCache && $bodyCache;
        }

        if ($hasCache) {
            if ($headersCache) {
                $headersCache = unserialize($headersCache);
                /** @var \Phalcon\Http\ResponseInterface $response */
                $response = $request->getDI()->getResponse();
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
     * @param Request $request
     * @param array $ignores
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
        $interceptorParams = array_merge(array(
            'lifetime' => 0,
            'methods' => 'get',
            'ignore_query_keys' => '_',
            'jsonp_callback_key' => 'callback',
            'format' => 'text', //allow text | jsonp
        ), $interceptorParams);

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

    public function injectInterceptor(DispatcherInterface $dispatcher)
    {
        /** @var \Phalcon\DI $di */
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
        /** @var \Phalcon\Http\Request $request */
        $request = $di->getRequest();
        $requestMethod = strtolower($request->getMethod());
        if (false === in_array($requestMethod, $methodsAllow)) {
            return true;
        }

        /** @var \Phalcon\Cache\Backend $cache */
        $cache = $di->getViewCache();
        $interceptResult = $this->intercept($request, $params, $cache);

        //cache key matched, response already prepared
        if (true === $interceptResult) {
            $di->getResponse()->send();
            return false;
        }

        $self = $this;
        //Cache missed
        /** @var \Phalcon\Events\Manager $eventsManager */
        $eventsManager = $di->getEventsManager();
        $eventsManager->attach(
            'application:beforeSendResponse',
            function ($event, $application) use ($self, $params) {
                $bodyKey = $self->getCacheBodyKey();
                $headersKey = $self->getCacheHeadersKey();
                if (!$bodyKey || !$headersKey) {
                    return true;
                }

                /** @var \Phalcon\Http\ResponseInterface $response */
                $response = $application->getDI()->getResponse();
                $body = $response->getContent();

                $headers = $response->getHeaders()->toArray();
                $headersCache = array();
                if ($headers) {
                    //Filter allowed headers
                    $headersCache = array_intersect_key($headers, array_flip($this->getCachableHeaderKeys()));
                }
                $headersCache[Dispatch::CACHE_HEADER_FLAG] = date(DATE_ISO8601);
                $cache = $application->getDI()->getViewCache();

                $request = $application->getDI()->getRequest();
                $callbackKey = $params['jsonp_callback_key'];
                //Jsonp change to json
                if ($params['format'] == 'jsonp' && $callbackKey && ($callbackValue = $request->getQuery($callbackKey))) {
                    $body = Dispatch::changeJsonpToJson($body, $callbackValue);
                }
                $cache->save($bodyKey, $body, $params['lifetime']);
                $cache->save($headersKey, serialize($headersCache), $params['lifetime']);
            }
        );
    }

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
     * @param Event $event
     * @param DispatcherInterface $dispatcher
     */
    public function beforeExecuteRoute(Event $event, DispatcherInterface $dispatcher)
    {
        return $this->injectInterceptor($dispatcher);
    }
}
