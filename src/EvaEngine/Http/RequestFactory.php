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

/**
 * Build a fake http request
 * @package Eva\EvaEngine\Http
 */
class RequestFactory
{
    public static function reset()
    {
        $_GET = null;
        $_POST = null;
        $_COOKIE = null;
        $_FILES = null;
        $_REQUEST = null;
        foreach ($_SERVER as $key => $value) {
            $_SERVER[$key] = null;
        }
    }

    /**
     * Build Phalcon http request object
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param mixed $body if array will use as $_POST, if string will use as rawBody
     * @param array $files
     * @return Request
     */
    public static function build($method, $uri, array $headers = [], $body = null, array $files = [])
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
        $queryArray = [];
        $_SERVER['REQUEST_URI'] = $query ? $path . '?' . $query : $path;
        if ($query) {
            parse_str($query, $queryArray);
            $_GET = $queryArray;

            //REQUEST effects Phalcon Request->get() method
            $_REQUEST = $queryArray;
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

        $request = new Request();
        if (is_string($body)) {
            $request->setRawBody($body);
            parse_str($body, $bodyArray);

            $contentType = empty($_SERVER['HTTP_CONTENT_TYPE']) ? '' : $_SERVER['HTTP_CONTENT_TYPE'];
            if ($method === 'POST' && true === in_array($contentType, [
                    'application/x-www-form-urlencoded',
                    'multipart/form-data'
                ])
            ) {
                $_POST = $bodyArray;
                $_REQUEST = $bodyArray + $queryArray;
            }

            if ($method === 'PUT' && $bodyArray) {
                $request->setPutCache($bodyArray);
            }
        }

        if (true === is_array($body)) {
            $request = new Request();
            if ($method == 'POST') {
                $_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                $_POST = $body;
                $_REQUEST = $body + $queryArray;
                $request->setRawBody(http_build_query($body));
            }

            if ($method == 'PUT') {
                $request->setPutCache($body);
                $request->setRawBody(http_build_query($body));
            }
        }

        return $request;
    }
}
