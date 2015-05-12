<?php
/**
 * Created by PhpStorm.
 * User: wscn
 * Date: 15/5/7
 * Time: 下午8:35
 */


use Eva\EvaEngine\IoC;

if (!function_exists('p')) {
    function p($r)
    {
        if (function_exists('xdebug_var_dump')) {
            echo '<pre>';
            xdebug_var_dump($r);
            echo '</pre>';
            //(new \Phalcon\Debug\Dump())->dump($r, true);
        } else {
            echo '<pre>';
            var_dump($r);
            echo '</pre>';
        }
    }
}
if (!function_exists('dd')) {
    /**
     * 打印指定的变量并结束程序运行
     *
     * @param $r
     */
    function dd($r)
    {
        p($r);
        exit();
    }
}

/**
 * 方便链式调用，避免过多的中间变量，例如：with(new Post())->findPosts()
 *
 * @param  $obj
 * @return mixed
 */
if (!function_exists('with')) {
    function with($obj)
    {
        return $obj;
    }
}
if (!function_exists('array_pluck')) {
    function array_pluck($array, $itemKey, $keepItemKey = true)
    {
        $results = array();
        foreach ($array as $key => $item) {
            $itemValue = is_object($item) ? $item->$itemKey : $item[$itemKey];
            if ($keepItemKey) {
                $results[$key] = $itemValue;
            } else {
                $results[] = $itemValue;
            }
        }

        return $results;
    }
}
if (!function_exists('starts_with')) {
    function starts_with($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
}
if (!function_exists('ends_with')) {
    function ends_with($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos(
            $haystack,
            $needle,
            $temp
        ) !== false);
    }
}


if (!function_exists('eva_url')) {
    function eva_url($domainName, $baseUri = '/', $params = array(), $https = false)
    {
        $config = IoC::get('config');
        $domainConfig = @$config->domains->$domainName;
        $url = 'http://';
        if ($domainConfig->https && $https || !$domainConfig->http) {
            $url = 'https://';
        }
        $url .= $domainConfig->domain . $baseUri;

        $query = http_build_query($params);
        if ($query) {
            if (strpos($url, '?') !== false) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= $query;
        }

        return $url;
    }
}
if (!function_exists('eva_domain')) {
    function eva_domain($domainName)
    {
        $config = IoC::get('config');

        return @$config->domains->$domainName->domain;
    }
}
