<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/11 下午3:40
// +----------------------------------------------------------------------
// + CacheAdapterCreator.php
// +----------------------------------------------------------------------
namespace Eva\EvaEngine\Cache;

use Eva\EvaEngine\Exception\RuntimeException;
use Eva\EvaEngine\Foundation\AdapterCreator;

class CacheAdapterCreator extends AdapterCreator
{
    /**
     * {@inheritdoc}
     */
    protected function getAdaptersMapping($category = 'default')
    {
        return array(
            'frontend' => array(
                'base64' => 'Phalcon\Cache\Frontend\Base64',
                'data' => 'Phalcon\Cache\Frontend\Data',
                'igbinary' => 'Phalcon\Cache\Frontend\Igbinary',
                'json' => 'Phalcon\Cache\Frontend\Json',
                'none' => 'Phalcon\Cache\Frontend\None',
                'output' => 'Phalcon\Cache\Frontend\Output',
            ),
            'backend' => array(
                'apc' => 'Phalcon\Cache\Backend\Apc',
                'disable' => 'Eva\EvaEngine\Cache\Backend\Disable',
                'file' => 'Phalcon\Cache\Backend\File',
                'libmemcached' => 'Phalcon\Cache\Backend\Libmemcached',
                'memcache' => 'Phalcon\Cache\Backend\Memcache',
                'memory' => 'Phalcon\Cache\Backend\Memory',
                'mongo' => 'Phalcon\Cache\Backend\Mongo',
                'xcache' => 'Phalcon\Cache\Backend\Xcache',
                'redis' => 'Phalcon\Cache\Backend\Redis',
                'wincache' => 'Phalcon\Cache\Backend\Wincache',
            )
        );
    }

    /**
     * create a cache adapter
     *
     * @param string $frontendAdapter frontend adapter name or class name
     * @param array $frontendOptions frontend options
     * @param string $backendAdapter backend adapter name or class name
     * @param array $backendOptions backend options
     * @return \Phalcon\Cache\BackendInterface
     * @throws RuntimeException
     */
    public function create($frontendAdapter, array $frontendOptions, $backendAdapter, array $backendOptions)
    {
        $frontendCacheClass = $this->getAdapterClass($frontendAdapter, 'frontend');

        $backendCacheClass = $this->getAdapterClass($backendAdapter, 'backend');

        $frontendCache = new $frontendCacheClass($frontendOptions);
        $backendCache = new $backendCacheClass($frontendCache, $backendOptions);

        return $backendCache;
    }

    public function createFromConfig($config, $configKey)
    {

        $frontendAdapter = $config->cache->$configKey->frontend->adapter;
        $frontendOptions = $config->cache->$configKey->frontend->options;
        $frontendOptions = !empty($frontendOptions) ? $frontendOptions->toArray() : array();

        $backendAdapter = $config->cache->$configKey->backend->adapter;
        $backendOptions = $config->cache->$configKey->backend->options;
        $backendOptions = !empty($backendOptions) ? $backendOptions->toArray() : array();

        if (!$config->cache->enable || !$config->cache->$configKey->enable) {
            $backendAdapter = 'disable';
            $backendOptions = array();
        }

        return $this->create($frontendAdapter, $frontendOptions, $backendAdapter, $backendOptions);
    }
}