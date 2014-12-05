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
        'Content-Type' => ''
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

    protected function textIntercept(Request $request, array $params, CacheInterface $cache)
    {
        $ignores = $params['ignore_query_keys'] ? explode('|', $params['ignore_query_keys']) : array();
        array_push($ignores, $this->getDebugQueryKey());
        list($headersKey, $bodyKey) = $this->generateCacheKeys($request, $ignores);

        $debugQueryKey = $this->getDebugQueryKey();
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
            $response->setContent($bodyCache);
            return true;
        }
        // cache missing
        return false;
    }

    protected function jsonpIntercept(Request $request, array $param, CacheInterface $cache)
    {
//        $params['ignore_query_keys'] = $params['ignore_query_keys'] ? :
        $this->textIntercept($request, $param, $cache);
    }

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

    public function injectInterceptor(DispatcherInterface $dispatcher)
    {
        /** @var \Phalcon\DI $di */
        $di = $dispatcher->getDI();
        $config = $di->getConfig();
        // cache is disable
        if (!$config->cache->enable) {
            return true; 
        }

        $interceptorConfig = strtolower($dispatcher->getParam(self::INTERCEPTOR_KEY));

        if (!$interceptorConfig) {
            return true;
        }

        parse_str($interceptorConfig, $interceptorParams);
        //Make default
        $interceptorParams = array_merge(array(
            'lifetime' => 0,
            'methods' => 'get',
            'ignore_query_keys' => '',
            'jsonp_callback_key' => '',
            'format' => 'text', //allow text | jsonp
        ), $interceptorParams);

        $lifetime = (int) $interceptorParams['lifetime'];
        if ($lifetime <= 0) {
            return true;
        }

        $methods = $interceptorParams['methods'];
        $methodsAllow = explode('|', $methods);
        /** @var \Phalcon\Http\Request $request */
        $request = $di->getRequest();
        $requestMethod = strtolower($request->getMethod());
        if (false === in_array($requestMethod, $methodsAllow)) {
            return true;
        }

        $format = $interceptorParams['format'];

        /** @var \Phalcon\Cache\Backend $cache */
        $cache = $di->getViewCache();

        if ($format === 'jsonp') {
            $interceptResult = $this->jsonpIntercept($request, $interceptorParams, $cache);
        } else {
            $interceptResult = $this->textIntercept($request, $interceptorParams, $cache);
        }

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
            function ($event, $application) use ($self, $format, $lifetime) {
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
                $allowHeaderKeys = $self->getCachableHeaderKeys();
                if ($headers) {
                    $headersCache = array_filter($headers, function($key) use ($allowHeaderKeys) {
                        return in_array($key, $allowHeaderKeys); 
                    });
                }
                $headersCache[Dispatch::CACHE_HEADER_FLAG] = time();
                $cache = $application->getDI()->getViewCache();
                $cache->save($bodyKey, $body, $lifetime);
                $cache->save($headersKey, serialize($headersCache), $lifetime);
            }
        );
    }

    /**
     *
     *
     * @param $event
     * @param DispatcherInterface $dispatcher
     */
    public function beforeExecuteRoute(Event $event, DispatcherInterface $dispatcher)
    {
        return $this->injectInterceptor($dispatcher);
    }
}
