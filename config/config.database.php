<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/12 下午4:16
// +----------------------------------------------------------------------
// + config.database.php
// +----------------------------------------------------------------------

return array(
    'adapter' => array(
        'prefix' => 'eva_',
        'master' => array(/*
            'adapter' => 'mysql',
            'host' => '192.168.1.228',
            'dbname' => 'eva',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8',
            'prefix' => 'eva_',
            */
        ),
        'slave' => array(/*
            'slave1' => array(
                'adapter' => 'mysql',
                'host' => '192.168.1.233',
                'dbname' => 'eva',
                'username' => 'root',
                'password' => '',
                'charset'  => 'utf8',
                'prefix' => 'eva_',
            ),
            */
        )
    ),
    'modelsMetadata' => array(
        'enable' => true,
        'adapter' => 'files',
        'options' => array(
            'metaDataDir' => __DIR__ . '/../cache/schema/'
        ),
    ),
);