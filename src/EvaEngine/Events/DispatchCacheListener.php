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
        $cache_key = $_SERVER['HTTP_HOST'] . preg_replace(
            '/[&?]_eva_refresh_dispatch_cache\=1/i',
            '',
            $_SERVER['REQUEST_URI']
        ) . file_get_contents('php://input');
        $cache_key = md5($cache_key);
        /** @var \Phalcon\Cache\Backend\Memcache $cache */
        $cache = $di->getViewCache();
        $contentCached = $cache->get($cache_key);

        // cache missing
        if ($di->getRequest()->getQuery('_eva_refresh_dispatch_cache') || $contentCached === null) {
            /** @var \Phalcon\Events\Manager $eventsManager */
            $eventsManager = $di->get('eventsManager');
            $eventsManager->attach(
                'application:beforeSendResponse',
                function ($event, $application) use ($di, $cache_key, $lifetime, $cache) {
                    /** @var \Phalcon\Http\ResponseInterface $response */
                    $response = $di->getResponse();
                    $content = $response->getContent();
                    $content2cache = array(
                        'time' => time(),
                        'headers' => $response->getHeaders()->toArray(),
                        'body' => $content
                    );
                    $cache->save($cache_key, serialize($content2cache), $lifetime);
                }
            );
            return;
        }
        /** @var \Phalcon\Http\ResponseInterface $response */
        $response = $di->getResponse();
        $contentCached = unserialize($contentCached);
        if ($contentCached) {
            $response->setHeader('Eva-Dsp-Cache', date('Y-m-d H:i:s', $contentCached['time']));
            if ($contentCached['headers']) {
                foreach ($contentCached['headers'] as $_k => $_herder) {
                    $response->setHeader($_k, $_herder);
                }
            }

            $response->setContent($contentCached['body']);
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