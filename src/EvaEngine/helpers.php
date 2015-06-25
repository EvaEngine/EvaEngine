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
 * 方便链式调用，避免过多的中间变量，例如：with(new Post())->findPosts()，较老的版本
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
    /**
     * 判断 $haystack 是否以 $needle 打头
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    function starts_with($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
}
if (!function_exists('ends_with')) {
    /**
     * 判断 $haystack 是否以 $needle 结尾
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
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
    /**
     * 根据域名名称（站点名称）生成 URL
     *
     * @param string $domainName 域名名称（站点名称）
     * @param string $baseUri 基础 URI
     * @param array $params query参数数组
     * @param bool $https 是否是https，留空则自动检测
     * @return string
     * @throws \Eva\EvaEngine\Exception\RuntimeException
     */
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
    /**
     * 根据域名名称（站点名称）获取域名
     *
     * @param string $domainName 域名名称（站点名称）
     * @return mixed
     * @throws \Eva\EvaEngine\Exception\RuntimeException
     */
    function eva_domain($domainName)
    {
        $config = IoC::get('config');

        return @$config->domains->$domainName->domain;
    }
}
if (!function_exists('eva_get')) {
    /**
     * 从 DI 容器中获取服务
     *
     * @param string $serviceName 服务名
     * @return mixed
     * @throws \Eva\EvaEngine\Exception\RuntimeException
     */
    function eva_get($serviceName)
    {
        return \Phalcon\DI::getDefault()->get($serviceName);
    }
}