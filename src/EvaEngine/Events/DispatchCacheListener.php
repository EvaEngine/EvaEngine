<?php

namespace Eva\EvaEngine\Events;

// +----------------------------------------------------------------------
// | [evaengine]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-8-28 15:07
// +----------------------------------------------------------------------

use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Events\Event;

class DispatchCacheListener
{
    /**
     *
     *
     * @param $event
     * @param DispatcherInterface $dispatcher
     */
    public function beforeExecuteRoute(Event $event, DispatcherInterface $dispatcher)
    {
        /** @var \Phalcon\DI $di */
        $di = $dispatcher->getDI();
        $config = $di->getConfig();
        // cache is disable
        if (!$config->cache->enable) {
            return;
        }
        $dispatch_cache_config = $dispatcher->getParam('_dispatch_cache');

        if (!$dispatch_cache_config) {
            return;
        }

        /** @var \Phalcon\Http\Request $request */
        $request = $di->getRequest();
        $params = $this->parseParams($dispatch_cache_config);
        $lifetime = intval($params['lifetime']);
        $methodsAllow = $params['methods'];
        if ($lifetime <= 0) {
            return;
        }

        if (!$methodsAllow) {
            $methodsAllow = 'get';
        }
        $methodsAllow = explode('|', strtolower($methodsAllow));
        $requestMethod = strtolower($request->getMethod());

        if (!in_array($requestMethod, $methodsAllow)) {
            return;
        }
        $cache_key_prefix = $_SERVER['HTTP_HOST'] . preg_replace(
            '/[&?]_eva_refresh_dispatch_cache\=1/i',
            '',
            $_SERVER['REQUEST_URI']
        ) . file_get_contents('php://input');
        $cache_key_prefix = md5($cache_key_prefix);
        /** @var \Phalcon\Cache\Backend $cache */
        $cache = $di->getViewCache();
        $bodyKey = $cache_key_prefix . '_b';
        $headersKey = $cache_key_prefix . '_h';

        $bodyCached = $cache->get($bodyKey);
        $headersCached = $cache->get($headersKey);

        $hasCached = $headersCached && $bodyCached;
        // cache missing
        if ($di->getRequest()->getQuery('_eva_refresh_dispatch_cache') || !$hasCached) {
            /** @var \Phalcon\Events\Manager $eventsManager */
            $eventsManager = $di->get('eventsManager');
            $eventsManager->attach(
                'application:beforeSendResponse',
                function ($event, $application) use ($di, $headersKey, $bodyKey, $lifetime, $cache) {
                    /** @var \Phalcon\Http\ResponseInterface $response */
                    $response = $di->getResponse();
                    $body = $response->getContent();

                    $headers = $response->getHeaders()->toArray();
                    !$headers && $headers = array();

                    $headersByHeaderFunc = headers_list();

                    if ($headersByHeaderFunc) {
                        $headers = array_merge($headers, $headersByHeaderFunc);
                    }
                    if (isset($headers['Set-Cookie'])) {
                        unset($headers['Set-Cookie']);
                    }
                    if (isset($headers['X-Permission-Auth'])) {
                        unset($headers['X-Permission-Auth']);
                    }
                    $headers['X-Eva-Dsp-Cache'] = time();
                    $cache->save($bodyKey, $body, $lifetime);
                    $cache->save($headersKey, serialize($headers), $lifetime);
                }
            );
            return;
        }
        /** @var \Phalcon\Http\ResponseInterface $response */
        $response = $di->getResponse();
        if ($hasCached) {
            if ($headersCached) {
                $headersCached = unserialize($headersCached);
                isset($headersCached['X-Eva-Dsp-Cache']) && $headersCached['X-Eva-Dsp-Cache'] = date(
                    'Y-m-d H:i:s',
                    $headersCached['X-Eva-Dsp-Cache']
                );
                foreach ($headersCached as $_k => $_herder) {
                    if (is_int($_k)) {
                        $response->setRawHeader($_herder);
                    } else {
                        $response->setHeader($_k, $_herder);
                    }
                }
            }

            $response->setContent($bodyCached);
            $response->send();
            exit();
        }

    }


    private function parseParams($params)
    {
        $params = explode("&", $params);
        $paramsArray = array();
        foreach ($params as $param) {
            $param = trim($param);
            list($k, $v) = explode('=', $param);
            $paramsArray[trim($k)] = trim($v);
        }
        return $paramsArray;
    }
}
