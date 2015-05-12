<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Service;

use Eva\EvaEngine\Exception;
use Phalcon\Session\AdapterInterface as SessionInterface;
use Phalcon\DI\InjectionAwareInterface;
use Phalcon\Http\RequestInterface;
use Phalcon\Text;

/**
 * A data abstraction layer to save API token data
 * Providing same interface as Phalcon\Session
 * @package Eva\EvaEngine\Service
 */
class TokenStorage implements SessionInterface, InjectionAwareInterface
{
    /**
     * @var SessionInterface
     */
    protected $storage;

    /**
     * @var string
     */
    protected $tokenId;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var int
     */
    protected $lifetime;

    /**
     * Token identify key in url query
     */
    const AUTH_QUERY_KEY = 'api_key';

    /**
     * Token identify key in http header
     */
    const AUTH_HEADER_KEY = 'Authorization';

    /**
     * Find token from http request, token may be in http header or url query
     * If find both, use http header priority
     * @param RequestInterface $request
     * @return string
     */
    public static function dicoverToken(RequestInterface $request)
    {
        if ($token = $request->getQuery(TokenStorage::AUTH_QUERY_KEY, 'string')) {
            return $token;
        }

        //For apache
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (!isset($headers[TokenStorage::AUTH_HEADER_KEY])) {
                return '';
            }
            $token = trim($headers[TokenStorage::AUTH_HEADER_KEY]);
            $token = explode(' ', $token);
            return isset($token[1]) ? $token[1] : '';
        }

        //For nginx
        if ($token = $request->getHeader(strtoupper(TokenStorage::AUTH_HEADER_KEY))) {
            $token = trim($token);
            $token = explode(' ', $token);
            return isset($token[1]) ? $token[1] : '';
        }
        return '';
    }

    /**
     * @return SessionInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @return string
     */
    public function getId()
    {
        if ($this->tokenId) {
            return $this->tokenId;
        }

        /**
 * @var RequestInterface $request
*/
        $request = $this->getDI()->getRequest();
        $token = TokenStorage::dicoverToken($request);
        if ($token) {
            return $this->tokenId = $token;
        } else {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            //Generate random hash for even same IP
            return $this->tokenId = 'ip' . ip2long($ip) . Text::random(Text::RANDOM_ALNUM, 6);
        }
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->tokenId = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this
     * @throws Exception\RuntimeException
     */
    public function setOptions($options)
    {
        $defaultOptions = array(
            'uniqueId' => 'evaengine',
            'frontend' => array(
                'adapter' => 'Json',
                'options' => array(),
            ),
            'backend' => array(
                'adapter' => 'File',
                'options' => array(),
            ),
        );
        $this->options = $options = array_merge($defaultOptions, $options);

        $adapterMapping = array(
            'apc' => 'Phalcon\Cache\Backend\Apc',
            'file' => 'Phalcon\Cache\Backend\File',
            'libmemcached' => 'Phalcon\Cache\Backend\Libmemcached',
            'memcache' => 'Phalcon\Cache\Backend\Memcache',
            'memory' => 'Phalcon\Cache\Backend\Memory',
            'mongo' => 'Phalcon\Cache\Backend\Mongo',
            'xcache' => 'Phalcon\Cache\Backend\Xcache',
            'redis' => 'Phalcon\Cache\Backend\Redis',
            'wincache' => 'Phalcon\Cache\Backend\Wincache',
            'base64' => 'Phalcon\Cache\Frontend\Base64',
            'data' => 'Phalcon\Cache\Frontend\Data',
            'igbinary' => 'Phalcon\Cache\Frontend\Igbinary',
            'json' => 'Phalcon\Cache\Frontend\Json',
            'none' => 'Phalcon\Cache\Frontend\None',
            'output' => 'Phalcon\Cache\Frontend\Output',
        );

        $frontCacheClassName = strtolower($options['frontend']['adapter']);
        if (!isset($adapterMapping[$frontCacheClassName])) {
            throw new Exception\RuntimeException(
                sprintf('No frontend cache adapter found by %s', $frontCacheClassName)
            );
        }
        $frontCacheClass = $adapterMapping[$frontCacheClassName];
        $frontCache = new $frontCacheClass($options['frontend']['options']);

        $backendCacheClassName = strtolower($options['backend']['adapter']);
        if (!isset($adapterMapping[$backendCacheClassName])) {
            throw new Exception\RuntimeException(
                sprintf('No backend cache adapter found by %s', $backendCacheClassName)
            );
        }
        $backendCacheClass = $adapterMapping[$backendCacheClassName];
        $storage = new $backendCacheClass($frontCache, array_merge(
            array(
                'prefix' => $options['uniqueId'] . '_',
            ),
            $options['backend']['options']
        ));
        $this->storage = $storage;
        return $this;
    }

    /**
     * @param string $key
     * @param null   $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        //p('get key:' . $this->getId() . '_' . $key);
        return $this->storage->get($this->getId() . '_' . $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @return mixed
     */
    public function set($key, $value)
    {
        //p('set key:' . $this->getId() . '_' . $key);
        return $this->storage->save($this->getId() . '_' . $key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->storage->exists($this->getId() . '_' . $key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function remove($key)
    {
        return $this->storage->delete($this->getId() . '_' . $key);
    }

    /**
     * @param null $id
     * @return bool
     */
    public function destroy($id = null)
    {
        return $this->storage->flush();
    }

    /**
     * @return $this
     */
    public function start()
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * @param \Phalcon\DiInterface $di
     * @return $this
     */
    public function setDI($di)
    {
        $this->di = $di;
        return $this;
    }

    /**
     * @param array $options
     * @throws Exception\RuntimeException
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }
}
