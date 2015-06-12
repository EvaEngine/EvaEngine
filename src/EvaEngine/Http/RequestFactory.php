<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Http;

use Eva\EvaEngine\Exception;
use Phalcon\Http\Request;

/**
 * Build a fake http request
 * @package Eva\EvaEngine\Http
 */
class RequestFactory
{
    public static function reset()
    {
        foreach ($_SERVER as $key => $value) {
            $_SERVER[$key] = null;
        }
    }

    /**
     * Provider params as same as Guzzle
     *
     * @param $method
     * @param $uri
     * @param array $headers
     * @param string $body
     * @return Request
     */
    public static function build($method, $uri, array $headers = [], $body = null)
    {
        self::reset();

        $method = strtoupper($method);
        $method = in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH']) ? $method : 'GET';
        $_SERVER["REQUEST_METHOD"] = $method;

        $url = parse_url($uri);
        $_SERVER['SERVER_NAME'] = $url['host'];
        if (!empty($url['port'])) {
            $_SERVER['HTTP_HOST'] = $url['host'] . ':' . $url['port'];
            $_SERVER['SERVER_PORT'] = $url['port'];
        } else {
            $_SERVER['HTTP_HOST'] = $url['host'];
        }

        $scheme = strtolower($url['scheme']);
        if ($scheme && $scheme == 'https') {
            $_SERVER['HTTPS'] = 'on';
        }

        $path = empty($url['path']) ? '' : $url['path'];
        $query = empty($url['query']) ? '' : $url['query'];
        $_SERVER['REQUEST_URI'] = $query ? $path . '?' . $query : $path;
        if ($query) {
            parse_str($query, $queryArray);
            $_GET = $queryArray;
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $key = 'HTTP_' . str_replace('-', '_', strtoupper($key));
                $_SERVER[$key] = $value;
            }
        }

        if (!$body) {
            return new Request();
        }

        /*
        if (is_string($body)) {
        }
        */

        return new Request();
    }
}
