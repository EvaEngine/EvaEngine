<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/12 下午3:50
// +----------------------------------------------------------------------
// + config.di.php
// +----------------------------------------------------------------------


use Phalcon\Mvc\Router;
$a = 'aaa';
return array(
    /**
     * disposable DI 在每次 get 的时候都会重新构造一个对象
     */
    'disposables' => array(
        'mailMessage' => 'Eva\EvaEngine\MailMessage',
        'placeholder' => 'Eva\EvaEngine\View\Helper\Placeholder',
        'flash' => 'Phalcon\Flash\Session',
        'cookies' => function () use ($a){
            $cookies = new \Phalcon\Http\Response\Cookies();
            $cookies->useEncryption(false);

            return $cookies;
        },
        'translate' =>
            function () {
                $config = eva_get('config');
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
            },

    ),
    /**
     * shares DI 是共享的服务，每次获取的对象都是同一个，类似单例。
     */
    'shares' => array(
        'eventsManager' => function () {
            $eventsManager = new \Phalcon\Events\Manager();
            $eventsManager->enablePriorities(true);

            return $eventsManager;
        },
        'fastCache' => function () {
            $config = eva_get('config');
            if (!($config->cache->fastCache->enable)) {
                return new \Eva\EvaEngine\Cache\RedisDisabled();
            }

            $redis = new \Redis();
            $redis->connect(
                $config->cache->fastCache->host,
                $config->cache->fastCache->port,
                $config->cache->fastCache->timeout
            );

            return $redis;
        },
        /*
       |--------------------------------------------------------------------------
       | 全局的 Redis 服务
       |--------------------------------------------------------------------------
       | fastCache 将会修改为普通的 cache
       |
       */
        'redis' => function () {
            $config = eva_get('config');

            $redis = new \Redis();
            $redis->connect(
                $config->redis->host,
                $config->redis->port,
                $config->redis->timeout
            );
            if (isset($config->redis->db)) {
                $redis->select($config->redis->db);
            }

            return $redis;
        },
        /*
        |--------------------------------------------------------------------------
        | 视图缓存
        |--------------------------------------------------------------------------
        | 主要用于存储页面级别的缓存，默认地，EvaEngine 内置的 RouterCache 中间件使用的是 ViewCache
        |
        */
        'viewCache' => function () {
            return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig(eva_get('config'), 'viewCache');

        },
        /*
        |--------------------------------------------------------------------------
        | 模型缓存
        |--------------------------------------------------------------------------
        | 主要用于存储数据库结果集
        |
        */
        'modelsCache' => function () {
            return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig(eva_get('config'), 'modelsCache');
        },
        /*
        |--------------------------------------------------------------------------
        | API 缓存
        |--------------------------------------------------------------------------
        | 主要缓存 API 结果
        |
        */
        'apiCache' => function () {
            return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig(eva_get('config'), 'apiCache');
        },
        /*
        |--------------------------------------------------------------------------
        | 通用缓存
        |--------------------------------------------------------------------------
        | 当上面定义的缓存服务不适用当前语境时，可以使用通用缓存，或者通过
        | `\Eva\EvaEngine\Cache\CacheAdapterCreator` 创建一个自己的缓存服务
        |
        */
        'globalCache' => function () {
            return (new \Eva\EvaEngine\Cache\CacheAdapterCreator)->createFromConfig(eva_get('config'), 'globalCache');
        },
        /*
        |--------------------------------------------------------------------------
        | 配置服务
        |--------------------------------------------------------------------------
        | //TODO 完善「配置」的文档
        |
        |
        */

        'config' => function () {
            /** @var \Eva\EvaEngine\Module\Manager $moduleManager */
            $moduleManager = eva_get('moduleManager');
            $evaengine = eva_get('evaengine');

            $config = $moduleManager->getAllConfig();
            $config->merge(
                new \Phalcon\Config(include $evaengine->getProjectRoot() . '/config/config.default.php')
            );

            $config->merge(
                new \Phalcon\Config(include $evaengine->getProjectRoot() . '/config/config.local.php')
            );

            return $config;
        },
        /*
        |--------------------------------------------------------------------------
        | 默认的路由服务
        |--------------------------------------------------------------------------
        | //TODO 完善「路由」的文档
        |
        |
        */
        'router' => function () {
            //Disable default router
            $router = new Router(false);
            //Last extra slash
            $router->removeExtraSlashes(true);
            //Set last module as default module
            $router->setDefaultModule(eva_get('moduleManager')->getDefaultModule()->getName());
            //NOTICE: Set a strange controller here to make router not match default index/index
            $router->setDefaultController('EvaEngineDefaultController');

            return $router;

        },
        /*
        |--------------------------------------------------------------------------
        | 默认的分发器服务
        |--------------------------------------------------------------------------
        | //TODO 完善「分发器」的文档
        |
        |
        */
        'dispatcher' =>
            function () {
                $dispatcher = new \Phalcon\Mvc\Dispatcher();
                $dispatcher->setEventsManager(eva_get('eventsManager'));

                return $dispatcher;
            },
        'modelsMetadata' => function () {
            $config = eva_get('config');
            $metadataConfig = $config->modelsMetadata;

            return (new \Eva\EvaEngine\Mvc\Model\ModelsMetadataAdapterCreator())
                ->create($metadataConfig->adapter, $metadataConfig->options->toArray());
        },
        'modelsManager' => function () {
            $config = eva_get('config');
            //for solving db master/slave under static find method
            \Eva\EvaEngine\Mvc\Model\Manager::setDefaultPrefix($config->dbAdapter->prefix);
            $modelsManager = new  \Eva\EvaEngine\Mvc\Model\Manager();

            return $modelsManager;
        },
        'view' => function () {
            $view = new \Eva\EvaEngine\Mvc\View();
            $view->setViewsDir(__DIR__ . '/views/');
            $view->setEventsManager(eva_get('eventsManager'));

            return $view;
        },
        'session' => function () {
            $config = eva_get('config');
            $sessionConfig = $config->session;

            return (new \Eva\EvaEngine\Session\SessionAdapterCreator())->create(
                $sessionConfig->adapter,
                $sessionConfig->options->toArray(),
                $sessionConfig->session_name,
                $sessionConfig->cookie_params->toArray()
            );
        },
//        'tokenStorage' => function () use ($self) {
//            return $self->diTokenStorage();
//        },
        'dbMaster' => function () {
            $config = eva_get('config');
            if (!isset($config->dbAdapter->master->adapter) || !$config->dbAdapter->master) {
                throw new \Eva\EvaEngine\Exception\RuntimeException(sprintf('No DB Master options found'));
            }

            return (new \Eva\EvaEngine\Db\DbAdapterCreator())->create(
                $config->dbAdapter->master->adapter,
                $config->dbAdapter->master->toArray(),
                eva_get('eventsManager')
            );
        },
        'dbSlave' => function () {
            $config = eva_get('config');
            $slaves = $config->dbAdapter->slave;
            $slaveKey = array_rand($slaves->toArray());
            if (!isset($slaves->$slaveKey) || count($slaves) < 1) {
                throw new RuntimeException(sprintf('No DB slave options found'));
            }


            return (new \Eva\EvaEngine\Db\DbAdapterCreator())->create(
                $config->dbAdapter->slave->$slaveKey->adapter,
                $config->dbAdapter->slave->$slaveKey->toArray(),
                eva_get('eventsManager')
            );
        },
        'transactions' => function () {
            $transactions = new \Phalcon\Mvc\Model\Transaction\Manager();
            $transactions->setDbService('dbMaster');

            return $transactions;
        },
        'queue' => function () {
            $config = eva_get('config');
            $client = new \GearmanClient();
            $client->setTimeout(1000);
            foreach ($config->queue->servers as $key => $server) {
                $client->addServer($server->host, $server->port);
            }

            return $client;
        },
        'worker' => function () {
            $config = eva_get('config');
            $worker = new \GearmanWorker();
            foreach ($config->queue->servers as $key => $server) {
                $worker->addServer($server->host, $server->port);
            }

            return $worker;
        },
//        'mailer' => function () {
//            return $self->diMailer();
//        },
//        'smsSender' => function () {
//            return $self->diSmsSender();
//        },
        'url' => function () {
            $config = eva_get('config');
            $url = new Eva\EvaEngine\Mvc\Url();
            $url->setVersionFile($config->staticBaseUriVersionFile);
            $url->setBaseUri($config->baseUri);
            $url->setStaticBaseUri($config->staticBaseUri);

            return $url;
        },
        'escaper' => 'Phalcon\Escaper',
        'tag' => function () {
            \Eva\EvaEngine\Tag::setDi(\Phalcon\DI::getDefault());
            \Eva\EvaEngine\Tag::registerHelpers(eva_get('moduleManager')->getAllViewHelpers());

            return new \Eva\EvaEngine\Tag();
        },
        'fileSystem' =>
            function () {
//            return $di->diFileSystem();
            },
        /*
        |--------------------------------------------------------------------------
        | error 日志服务
        |--------------------------------------------------------------------------
        | 主要用于记录各种 error 日志信息，EvaEngine 默认的 ErrorHandler 依赖 errorLogger
        | 服务，用于记录未捕捉的异常。
        |
        */
        'errorLogger' => function () {
            $config = eva_get('config');

            return new Phalcon\Logger\Adapter\File(rtrim($config->logger->path,
                    '/') . '/error.' . date('Y-m-d') . '.log');
        },
        /*
        |--------------------------------------------------------------------------
        | debug 日志服务
        |--------------------------------------------------------------------------
        | 主要用于记录各种 debug 日志信息
        |
        */
        'debugLogger' => function () {
            $config = eva_get('config');

            return new Phalcon\Logger\Adapter\File(rtrim($config->logger->path,
                    '/') . '/debug.' . date('Y-m-d') . '.log');
        },
        /*
        |--------------------------------------------------------------------------
        | 数据库查询日志记录服务
        |--------------------------------------------------------------------------
        | 默认地，EvaEngine 会监听 db:beforeQuery 事件，并通过
        | $dbAdapter->getSQLStatement() 获取每条执行的 SQL 语句，然后产生一条 INFO 级别
        | 的日志，通过 dbQueryLogger 来记录 SQL 查询记录。
        */
        'dbQueryLogger' => function () {
            $config = eva_get('config');

            return new Phalcon\Logger\Adapter\File($config->logger->path . 'db_query.' . date('Y-m-d') . '.log');
        },
        /*
        |--------------------------------------------------------------------------
        | FirePHP 调试服务
        |--------------------------------------------------------------------------
        | firephp 服务是一个标准的日志实现，可以通过浏览器 firephp 插件获取到服务器上产生的
        | firephp 日志
        |
        */
        'firephp' => function () {
            return new \Phalcon\Logger\Adapter\Firephp();
        },
    ),
);
