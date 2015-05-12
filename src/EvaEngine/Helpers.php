<?php
/**
 * Created by PhpStorm.
 * User: wscn
 * Date: 15/5/7
 * Time: 下午8:35
 */

namespace Eva\EvaEngine;

class Helpers
{
    static public function domain($domainName)
    {
        $config = IoC::get('config');

        return @$config->domains->$domainName->domain;
    }

    static public function url($domainName, $baseUri = '/', $params = array(), $https = false)
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