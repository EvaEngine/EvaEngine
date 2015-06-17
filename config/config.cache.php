<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/12 下午4:17
// +----------------------------------------------------------------------
// + config.cache.php
// +----------------------------------------------------------------------

return array(
    'enable' => false,
    // 对应到 systemCache service
    'systemCache' => array(
        'enable' => true,
        'frontend' => array(
            'adapter' => 'Data',
            'options' => array(),
        ),
        'backend' => array(
            'adapter' => 'File',
            'options' => array(
                'cacheDir' => __DIR__ . '/../cache/system/',
                'prefix' => 'system/'
            ),
        ),
    ),
    // 对应到 fastCache service
    'fastCache' => array(
        'enable' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 1,
    ),
    // 对应到 globalCache service
    'globalCache' => array(
        'enable' => true,
        'frontend' => array(
            'adapter' => 'Data',
            'options' => array(),
        ),
        'backend' => array(
            'adapter' => 'File',
            'options' => array(
                'cacheDir' => __DIR__ . '/../cache/global/',
                'prefix' => 'global:'
            ),
        ),
    ),
    // 对应到 viewCache service
    'viewCache' => array(
        'enable' => true,
        'frontend' => array(
            'adapter' => 'Output',
            'options' => array(),
        ),
        'backend' => array(
            'adapter' => 'File',
            'options' => array(
                'cacheDir' => __DIR__ . '/../cache/view/',
                'prefix' => 'view:'
            ),
        ),
    ),
    // 对应到 modelsCache service
    'modelsCache' => array(
        'enable' => true,
        'frontend' => array(
            'adapter' => 'Data',
            'options' => array(),
        ),
        'backend' => array(
            'adapter' => 'File',
            'options' => array(
                'cacheDir' => __DIR__ . '/../cache/model/',
                'prefix' => 'models:'
            ),
        ),
    ),
    // 对应到 apiCache service
    'apiCache' => array(
        'enable' => true,
        'frontend' => array(
            'adapter' => 'Json',
            'options' => array(),
        ),
        'backend' => array(
            'adapter' => 'File',
            'options' => array(
                'cacheDir' => __DIR__ . '/../cache/api/',
                'prefix' => 'api:'
            ),
        ),
    ),
);