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
        /** @var \Phalcon\Cache\Backend\Memcache $cache */
        $cache = $di->getViewCache();
        $headersKey = $cache_key_prefix . '_h';
        $bodyKey = $cache_key_prefix . '_b';

        $headersCached = $cache->get($headersKey);
        $bodyCached = $cache->get($bodyKey);

        $hasCached = $headersCached || $bodyCached;
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
                    $headers['Eva-Dsp-Cache'] = time();
                    $cache->save($headersKey, serialize($headers), $lifetime);
                    $cache->save($bodyKey, $body, $lifetime);
                }
            );
            return;
        }
        /** @var \Phalcon\Http\ResponseInterface $response */
        $response = $di->getResponse();
        if ($hasCached) {
            if ($headersCached) {
                $headersCached = unserialize($headersCached);
                isset($headersCached['Eva-Dsp-Cache']) && $headersCached['Eva-Dsp-Cache'] = date(
                    'Y-m-d H:i:s',
                    $headersCached['Eva-Dsp-Cache']
                );
                foreach ($headersCached as $_k => $_herder) {
                    $response->setHeader($_k, $_herder);
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