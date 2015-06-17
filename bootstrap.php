<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/11 下午3:31
// +----------------------------------------------------------------------
// + bootstrap.php
// +----------------------------------------------------------------------

/** @var \Phalcon\DI $di */
$di->setShared(
    'eventsManager',
    function () use ($di) {
        $eventsManager = new \Phalcon\Events\Manager();
        $eventsManager->enablePriorities(true);

        return $eventsManager;
    }
);
$di->setShared('fastCache', function () use ($di) {
    $config = $this->getDI()->getConfig();
    if (!($config->cache->fastCache->enable)) {
        return false;
    }

    $redis = new \Redis();
    $redis->connect(
        $config->cache->fastCache->host,
        $config->cache->fastCache->port,
        $config->cache->fastCache->timeout
    );

    return $redis;
});


/*
|--------------------------------------------------------------------------
| 视图缓存
|--------------------------------------------------------------------------
| 主要用于存储页面级别的缓存，默认地，EvaEngine 内置的 RouterCache 使用的是 ViewCache
|
*/
$di->setShared('viewCache', function () use ($di) {
    return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig($di->getConfig(), 'viewCache');

});

/*
|--------------------------------------------------------------------------
| 模型缓存
|--------------------------------------------------------------------------
| 主要用于存储数据库结果集
|
*/
$di->setShared('modelsCache', function () use ($di) {
    return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig($di->getConfig(), 'modelsCache');
});

/*
|--------------------------------------------------------------------------
| API 缓存
|--------------------------------------------------------------------------
| 主要缓存 API 结果
|
*/
$di->setShared('apiCache', function () use ($di) {
    return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig($di->getConfig(), 'apiCache');
});
/*
|--------------------------------------------------------------------------
| 通用缓存
|--------------------------------------------------------------------------
| 当上面定义的缓存服务不适用当前语境时，可以使用通用缓存，或者通过
| `\Eva\EvaEngine\Cache\CacheAdapterCreator` 创建一个自己的缓存服务
|
*/
$di->setShared('globalCache', function () use ($di) {
    return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig($di->getConfig(), 'globalCache');
});

/*
|--------------------------------------------------------------------------
| 配置服务
|--------------------------------------------------------------------------
| //TODO 完善「配置」的文档
|
|
*/
$di->setShared(
    'config',
    function () use ($self) {
        return $self->diConfig();
    }
);
/*
|--------------------------------------------------------------------------
| 默认的路由服务
|--------------------------------------------------------------------------
| //TODO 完善「路由」的文档
|
|
*/
$di->setShared(
    'router',
    function () use ($self) {
        return $self->diRouter();
    }
);
/*
|--------------------------------------------------------------------------
| 默认的分发器服务
|--------------------------------------------------------------------------
| //TODO 完善「分发器」的文档
|
|
*/
$di->setShared(
    'dispatcher',
    function () use ($di) {
        $dispatcher = new \Phalcon\Mvc\Dispatcher();
        $dispatcher->setEventsManager($di->getEventsManager());

        return $dispatcher;
    }
);

$di->setShared(
    'modelsMetadata',
    function () use ($di) {
        $config = $di->getConfig();
        $metadataConfig = $config->modelsMetadata;

        return (new \Eva\EvaEngine\Mvc\Model\ModelsMetadataAdapterCreator())
            ->create($metadataConfig->adapter, $metadataConfig->options->toArray());
    }
);

$di->setShared(
    'modelsManager',
    function () use ($di) {
        $config = $di->getConfig();
        //for solving db master/slave under static find method
        \Eva\EvaEngine\Mvc\Model\Manager::setDefaultPrefix($config->dbAdapter->prefix);
        $modelsManager = new  \Eva\EvaEngine\Mvc\Model\Manager();

        return $modelsManager;
    }
);

$di->setShared(
    'view',
    function () use ($di) {
        $view = new \Eva\EvaEngine\Mvc\View();
        $view->setViewsDir(__DIR__ . '/views/');
        $view->setEventsManager($di->getEventsManager());

        return $view;
    }
);

$di->setShared(
    'session',
    function () use ($di) {
        $config = $di->getConfig();
        $sessionConfig = $config->session;

        return (new \Eva\EvaEngine\Session\SessionAdapterCreator())->create(
            $sessionConfig->adapter,
            $sessionConfig->options->toArray(),
            $sessionConfig->session_name,
            $sessionConfig->cookie_params
        );
    }
);

$di->setShared(
    'tokenStorage',
    function () use ($self) {
        return $self->diTokenStorage();
    }
);


$di->setShared(
    'dbMaster',
    function () use ($di) {
        $config = $di->getConfig();
        if (!isset($config->dbAdapter->master->adapter) || !$config->dbAdapter->master) {
            throw new \Eva\EvaEngine\Exception\RuntimeException(sprintf('No DB Master options found'));
        }

        return (new \Eva\EvaEngine\Db\DbAdapterCreator())->create(
            $config->dbAdapter->master->adapter,
            $config->dbAdapter->master->options->toArray(),
            $di->getEventsManager()
        );
    }
);

$di->setShared(
    'dbSlave',
    function () use ($di) {
        $config = $di->getConfig();
        if (!isset($config->dbAdapter->slave->adapter) || !$config->dbAdapter->slave) {
            throw new \Eva\EvaEngine\Exception\RuntimeException(sprintf('No DB Master options found'));
        }

        return (new \Eva\EvaEngine\Db\DbAdapterCreator())->create(
            $config->dbAdapter->slave->adapter,
            $config->dbAdapter->slave->options->toArray(),
            $di->getEventsManager()
        );
    }
);

$di->setShared(
    'transactions',
    function () use ($di) {
        $transactions = new \Phalcon\Mvc\Model\Transaction\Manager();
        $transactions->setDbService('dbMaster');

        return $transactions;
    }
);


$di->setShared(
    'queue',
    function () use ($di) {
        $config = $di->getConfig();
        $client = new \GearmanClient();
        $client->setTimeout(1000);
        foreach ($config->queue->servers as $key => $server) {
            $client->addServer($server->host, $server->port);
        }

        return $client;
    }
);

$di->setShared(
    'worker',
    function () use ($di) {
        $config = $di->getConfig();
        $worker = new \GearmanWorker();
        foreach ($config->queue->servers as $key => $server) {
            $worker->addServer($server->host, $server->port);
        }

        return $worker;
    }
);


/**********************************
 * DI initialize for email
 ***********************************/
$di->setShared(
    'mailer',
    function () use ($self) {
        return $self->diMailer();
    }
);

$di->set('mailMessage', 'Eva\EvaEngine\MailMessage');

$di->setShared(
    'smsSender',
    function () use ($self) {
        return $self->diSmsSender();
    }
);
/**********************************
 * DI initialize for helpers
 ***********************************/
$di->setShared(
    'url',
    function () use ($di) {
        $config = $di->getConfig();
        $url = new Eva\EvaEngine\Mvc\Url();
        $url->setVersionFile($config->staticBaseUriVersionFile);
        $url->setBaseUri($config->baseUri);
        $url->setStaticBaseUri($config->staticBaseUri);

        return $url;
    }
);

$di->set('escaper', 'Phalcon\Escaper');

$di->setShared(
    'tag',
    function () use ($di, $self) {
        \Eva\EvaEngine\Tag::setDi($di);
        $self->registerViewHelpers();

        return new Tag();
    }
);

$di->setShared('flash', 'Phalcon\Flash\Session');

$di->set('placeholder', 'Eva\EvaEngine\View\Helper\Placeholder');

$di->setShared(
    'cookies',
    function () {
        $cookies = new \Phalcon\Http\Response\Cookies();
        $cookies->useEncryption(false);

        return $cookies;
    }
);

$di->setShared(
    'translate',
    function () use ($di) {
        $config = $di->getConfig();
        $file = $config->translate->path . $config->translate->forceLang . '.csv';
        if (false === file_exists($file)) {
            //empty translator
            return new \Phalcon\Translate\Adapter\NativeArray(
                array(
                    'content' => array()
                )
            );
        }
        $translate = new \Phalcon\Translate\Adapter\Csv(
            array(
                'file' => $file,
                'delimiter' => ',',
            )
        );

        return $translate;
    }
);

$di->set(
    'fileSystem',
    function () use ($di) {
        return $di->diFileSystem();
    }
);
/*
|--------------------------------------------------------------------------
| error 日志服务
|--------------------------------------------------------------------------
| 主要用于记录各种 error 日志信息，EvaEngine 默认的 ErrorHandler 依赖 errorLogger
| 服务，用于记录未捕捉的异常。
|
*/
$di->set(
    'errorLogger',
    function () use ($di) {
        $config = $di->getConfig();

        return new Phalcon\Logger\Adapter\File(rtrim($config->logger->path, '/') . '/error.' . date('Y-m-d') . '.log');
    }
);
/*
|--------------------------------------------------------------------------
| debug 日志服务
|--------------------------------------------------------------------------
| 主要用于记录各种 debug 日志信息
|
*/
$di->set(
    'debugLogger',
    function () use ($di) {
        $config = $di->getConfig();

        return new Phalcon\Logger\Adapter\File(rtrim($config->logger->path, '/') . '/debug.' . date('Y-m-d') . '.log');
    }
);
/*
|--------------------------------------------------------------------------
| 数据库查询日志记录服务
|--------------------------------------------------------------------------
| 默认地，EvaEngine 会监听 db:beforeQuery 事件，并通过
| $dbAdapter->getSQLStatement() 获取每条执行的 SQL 语句，然后产生一条 INFO 级别
| 的日志，通过 dbQueryLogger 来记录 SQL 查询记录。
*/
$di->setShared('dbQueryLogger', function () use ($di) {
    $config = $di->getConfig();

    return new Phalcon\Logger\Adapter\File($config->logger->path . 'db_query.' . date('Y-m-d') . '.log');
});
/*
|--------------------------------------------------------------------------
| FirePHP 调试服务
|--------------------------------------------------------------------------
| firephp 服务是一个标准的日志实现，可以通过浏览器 firephp 插件获取到服务器上产生的
| firephp 日志
|
*/
$di->setShared('firephp', function () {
    return new \Phalcon\Logger\Adapter\Firephp();
});