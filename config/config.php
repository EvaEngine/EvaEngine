<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'debug' => true,
    'baseUri' => '/',
    'staticBaseUri' => '/',
    'staticBaseUriVersionFile' => __DIR__ . '/../.git/FETCH_HEAD',
    'session' => array(
        'adapter' => 'files',
        'options' => array(
            'lifetime' => 3600 * 3, //3 hours
        ),
    ),
    'alias' => array(
        'Annotations' => \Eva\EvaEngine\Facades\Annotations::class,
        'Config' => \Eva\EvaEngine\Facades\Config::class,
        'Cookies' => \Eva\EvaEngine\Facades\Cookies::class,
        'Crypt' => \Eva\EvaEngine\Facades\Crypt::class,
//        \Eva\EvaEngine\Facades\Db::class,
        'Dispatcher' => \Eva\EvaEngine\Facades\Dispatcher::class,
        'Escaper' => \Eva\EvaEngine\Facades\Escaper::class,
        'Event' => \Eva\EvaEngine\Facades\Event::class,
        'FlashSession' => \Eva\EvaEngine\Facades\FlashSession::class,
        'GlobalCache' => \Eva\EvaEngine\Facades\GlobalCache::class,
        'ModelsCache' => \Eva\EvaEngine\Facades\ModelsCache::class,
        'ModelsManager' => \Eva\EvaEngine\Facades\ModelsManager::class,
        'ModelsMetadata' => \Eva\EvaEngine\Facades\ModelsMetadata::class,
        'Request' => \Eva\EvaEngine\Facades\Request::class,
        'Response' => \Eva\EvaEngine\Facades\Response::class,
        'Router' => \Eva\EvaEngine\Facades\Router::class,
        'Security' => \Eva\EvaEngine\Facades\Security::class,
        'Session' => \Eva\EvaEngine\Facades\Session::class,
        'Tag' => \Eva\EvaEngine\Facades\Tag::class,
        'TransactionManager' => \Eva\EvaEngine\Facades\TransactionManager::class,
        'Url' => \Eva\EvaEngine\Facades\Url::class,
        'ViewCache' => \Eva\EvaEngine\Facades\ViewCache::class,
        'View' => \Eva\EvaEngine\Facades\View::class,
    ),

);