<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Cache\Backend;

use Phalcon\Cache\Backend as CacheBackend;

/**
 * Keep all input data in php memory, this class usually used to verify data in unit test.
 * @package Eva\EvaEngine\Cache\Backend
 */
class Memory extends CacheBackend
{
    /**
     * Cache data container
     * @var array
     */
    public static $data = array();

    /**
     * @param int|string $keyName
     * @param null|int   $lifetime
     * @return mixed|null
     */
    public function get($keyName, $lifetime = null)
    {
        if (!empty(Memory::$data[$keyName])) {
            return Memory::$data[$keyName];
        }
        return null;
    }

    /**
     * @param null $keyName
     * @param null $lifetime
     * @return bool
     */
    public function exists($keyName = null, $lifetime = null)
    {
        if (!empty(Memory::$data[$keyName])) {
            return true;
        }
        return false;
    }

    /**
     * @param null $keyName
     * @param null $content
     * @param null $lifetime
     * @param null $stopBuffer
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
    {
        Memory::$data[$keyName] = $content;
    }

    /**
     * @param int|string $keyName
     * @return bool
     */
    public function delete($keyName)
    {
        if (!empty(Memory::$data[$keyName])) {
            unset(Memory::$data[$keyName]);
        }
        return false;
    }

    /**
     * @param null $prefix
     * @return array
     */
    public function queryKeys($prefix = null)
    {
        return array_keys(Memory::$data);
    }

    /**
     * @return bool|void
     */
    public function flush()
    {
        Memory::$data = array();
    }
}
