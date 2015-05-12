<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Eva\EvaEngine\Session\Adapter;

use Phalcon;

/**
 * Memcache session adapter
 * @package Eva\EvaEngine\Session\Adapter
 */
class Libmemcached extends Phalcon\Session\Adapter implements Phalcon\Session\AdapterInterface
{
    /**
     * Default option for memcache port
     *
     * @var integer
     */
    const DEFAULT_OPTION_PORT = 11211;

    /**
     * Default option for session lifetime
     *
     * @var integer
     */
    const DEFAULT_OPTION_LIFETIME = 8600;

    /**
     * Default option for persistent session
     *
     * @var boolean
     */
    const DEFAULT_OPTION_PERSISTENT = false;

    /**
     * Default option for prefix of sessionId's
     *
     * @var string
     */
    const DEFAULT_OPTION_PREFIX = '';

    /**
     * Contains the memcache instance
     *
     * @var \Phalcon\Cache\Backend\Memcache
     */
    protected $memcacheInstance = null;

    /**
     * Class constructor.
     * $session = new Phalcon\Session\Adapter\Libmemcached(array(
     *     'servers' => array(
     *         array('host' => 'localhost', 'port' => 11211, 'weight' => 1),
     *     ),
     *     'client' => array(
     *         Memcached::OPT_HASH => Memcached::HASH_MD5,
     *         Memcached::OPT_PREFIX_KEY => 'prefix.',
     *     ),
     *    'lifetime' => 3600,
     *    'prefix' => 'my_'
     * ));
     *
     * @param  null|array $options
     * @throws Phalcon\Session\Exception
     */
    public function __construct($options = null)
    {
        if (empty($options)) {
            throw new Phalcon\Session\Exception("No configuration given");
        }

        if (empty($options['servers']) || !is_array($options['servers'])) {
            throw new Phalcon\Session\Exception("No session servers given in options");
        }

        foreach ($options['servers'] as $key => $server) {
            if (empty($server['host'])) {
                throw new Phalcon\Session\Exception("No session host given in options");
            }
            $options['server'][$key] = array_merge(
                array(
                'port' => self::DEFAULT_OPTION_PORT
                ),
                $server
            );
        }

        if (!isset($options["lifetime"])) {
            $options["lifetime"] = self::DEFAULT_OPTION_LIFETIME;
        }

        if (!isset($options["prefix"])) {
            $options["prefix"] = self::DEFAULT_OPTION_PREFIX;
        }

        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     *
     * @return boolean
     */
    public function open()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $sessionId
     * @return mixed
     */
    public function read($sessionId)
    {
        return $this->getMemcacheInstance()->get(
            $this->getSessionId($sessionId),
            $this->getOption('lifetime')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $sessionId
     * @param string $data
     */
    public function write($sessionId, $data)
    {
        $this->getMemcacheInstance()->save(
            $this->getSessionId($sessionId),
            $data,
            $this->getOption('lifetime')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $sessionId
     * @return boolean
     */
    public function destroy($session_id = null)
    {
        if (is_null($session_id)) {
            $session_id = $this->getId();
        }
        
        return $this->getMemcacheInstance()->delete($this->getSessionId($session_id));
    }

    /**
     * {@inheritdoc}
     */
    public function gc()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        $options = $this->getOptions();
        if (isset($options[$key])) {
            return $options[$key];
        }

        return null;
    }

    /**
     * Returns the memcache instance.
     *
     * @return \Phalcon\Cache\Backend\Memcache
     */
    protected function getMemcacheInstance()
    {
        if ($this->memcacheInstance === null) {
            $this->memcacheInstance = new Phalcon\Cache\Backend\Libmemcached(
                new Phalcon\Cache\Frontend\Data(array("lifetime" => $this->getOption("lifetime"))),
                array(
                    'statsKey'     => null, //Remove phalcon _PHCM key by force
                    'servers'      => $this->getOption('servers'),
                    'client'       => $this->getOption('client')
                )
            );
        }

        return $this->memcacheInstance;
    }

    /**
     * Returns the sessionId with prefix
     *
     * @param  string $sessionId
     * @return string
     */
    protected function getSessionId($sessionId)
    {
        return (strlen($this->getOption('prefix')) > 0)
            ? $this->getOption('prefix') . '_' . $sessionId
            : $sessionId;
    }
}
