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
 * A virtual cache class to make cache disabled, all data passed in will be dropped
 * @package Eva\EvaEngine\Cache\Backend
 */
class Disable extends CacheBackend
{
    /**
     * @param int|string $keyName
     * @param null       $lifetime
     * @return mixed|null
     */
    public function get($keyName, $lifetime = null)
    {
        return null;
    }

    /**
     * @param null $keyName
     * @param null $lifetime
     * @return bool
     */
    public function exists($keyName = null, $lifetime = null)
    {
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
    }

    /**
     * @param int|string $keyName
     * @return bool|void
     */
    public function delete($keyName)
    {
    }

    /**
     * @param null $prefix
     * @return array|void
     */
    public function queryKeys($prefix = null)
    {
    }

    /**
     * @return bool|void
     */
    public function flush()
    {
    }
}
