<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/11 下午5:05
// +----------------------------------------------------------------------
// + SessionAdapterCreator.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Session;

use Eva\EvaEngine\Exception\RuntimeException;
use Eva\EvaEngine\Foundation\AdapterCreator;
use Eva\EvaEngine\IoC;

class SessionAdapterCreator extends AdapterCreator
{

    public function create(
        $adapter,
        array $options = array(),
        $session_name = 'PHPSESSID',
        array $cookie_params = array()
    ) {

        $adapterClass = $this->getAdapterClass($adapter);

        session_name($session_name);

        if (!empty($cookie_params)) {
            session_set_cookie_params(
                isset($cookie_params['lifetime']) ? $cookie_params['lifetime'] : 0,
                isset($cookie_params['path']) ? $cookie_params['path'] : null,
                isset($cookie_params['domain']) ? $cookie_params['domain'] : null,
                isset($cookie_params['secure']) ? $cookie_params['secure'] : false,
                isset($cookie_params['httponly']) ? $cookie_params['httponly'] : false
            );
        }
        /** @var \Phalcon\Session\AdapterInterface $session */
        $session = new $adapterClass($options);
        if (!$session->isStarted()) {
            //NOTICE: Get php warning here, not found reason
            @$session->start();
        }

        return $session;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdaptersMapping()
    {
        return array(
            'default' => array(
                'files' => 'Phalcon\Session\Adapter\Files',
                'database' => 'Phalcon\Session\Adapter\Database',
                'memcache' => 'Phalcon\Session\Adapter\Memcache',
                'libmemcached' => 'Eva\EvaEngine\Session\Adapter\Libmemcached',
                'mongo' => 'Phalcon\Session\Adapter\Mongo',
                'redis' => 'Phalcon\Session\Adapter\Redis',
                'handlersocket' => 'Phalcon\Session\Adapter\HandlerSocket',
            )
        );
    }
}