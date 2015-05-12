<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine;

use Phalcon\Text;

/**
 * Class Tag
 * @package Eva\EvaEngine
 */
class Tag extends \Phalcon\Tag
{
    protected static $helpers = array();

    public static function registerHelpers(array $helpers = array())
    {
        self::$helpers = array_merge(self::$helpers, $helpers);
    }

    public static function unregisterHelper($helperName)
    {
        if (isset(self::$helpers[$helperName])) {
            unset(self::$helpers[$helperName]);
        }
    }

    public function __call($helperName, $arguments)
    {
        //Add alias for i18n helper
        if ($helperName === '_') {
            return call_user_func_array(
                array(
                __CLASS__,
                'translate'
                ),
                $arguments
            );
        }

        if (method_exists(__CLASS__, $helperName)) {
            return call_user_func_array(__CLASS__ . "::$helperName", $arguments);
        }
        if (empty(self::$helpers[$helperName])) {
            throw new Exception\BadMethodCallException(sprintf('Request helper %s not registered', $helperName));
        }
        $helperClass = self::$helpers[$helperName];
        $helper = new $helperClass();
        return call_user_func_array(
            array(
            $helper,
            '__invoke'
            ),
            $arguments
        );
    }

    public static function config()
    {
        $config = self::getDI()->getConfig();
        if (!$args = func_get_args()) {
            return $config;
        }

        $res = $config;
        foreach ($args as $arg) {
            if (!isset($res->$arg)) {
                return '';
            }
            $res = $res->$arg;
        }
        return $res;
    }

    public static function component($componentName, array $params = array())
    {
        return Mvc\View::getComponent($componentName, $params);
    }

    public static function translate($message = null, $replacement = null)
    {
        $translate = self::getDI()->getTranslate();
        if ($message) {
            return $translate->_(trim($message), $replacement);
        }

        return $translate;
    }

    public static function flashOutput()
    {
        $flash = self::getDI()->getFlash();
        if (!$flash) {
            return '';
        }
        $messages = $flash->getMessages();
        $classMapping = array(
            'error' => 'alert alert-danger',
            'warning' => 'alert alert-warning',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info',
        );

        $messageString = '';
        $escaper = self::getDI()->getEscaper();
        foreach ($messages as $type => $submessages) {
            foreach ($submessages as $message) {
                $messageString .=
                    '<div class="alert '
                    . $classMapping[$type]
                    . '" data-raw-message="'
                    . $escaper->escapeHtmlAttr($message)
                    . '">'
                    . self::translate($message)
                    . '</div>';
            }
        }

        return $messageString;
    }

    public static function uri($uri, array $query = null, array $baseQuery = null)
    {
        $url = self::getDI()->getUrl();
        if ($query && $baseQuery) {
            $query = array_merge($baseQuery, $query);
        }

        return $url->get($uri, $query);
    }

    public static function thumb($uri, $query = null, $configKey = 'default')
    {

        if ($query) {
            if (true === is_array($query)) {
                $query = implode(',', $query);
            }

            if (false !== ($pos = strrpos($uri, '.'))) {
                $uri = explode('/', $uri);
                $fileName = array_pop($uri);
                $nameArray = explode('.', $fileName);
                $nameExt = array_pop($nameArray);
                $nameFinal = array_pop($nameArray);
                $nameFinal .= ',' . $query;
                array_push($nameArray, $nameFinal, $nameExt);
                $fileName = implode('.', $nameArray);
                array_push($uri, $fileName);
                $uri = implode('/', $uri);
            }
        }


        if (Text::startsWith($uri, 'http://', false) || Text::startsWith($uri, 'https://', false)) {
            return $uri;
        }


        $config = self::getDI()->getConfig();
        if (isset($config->thumbnail->$configKey->baseUri) && $baseUrl = $config->thumbnail->$configKey->baseUri) {
            return $baseUrl . $uri;
        }

        return $uri;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     * @param $email
     * @param int    $s
     * @param string $d
     * @param string $r
     * @return string
     */
    public static function gravatar($email, $s = 80, $d = 'mm', $r = 'g')
    {
        $url = 'http://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        return $url;
    }


    /**
     * Transform input time to time string which can be parsed by javascript
     * @param string $time
     * @param null   $timezone
     * @return string javascript parse-able string
     */
    public static function jsTime($time = '', $timezone = null)
    {
        $time = $time ? $time : time();
        $timezone = $timezone ? $timezone : self::getDI()->getConfig()->datetime->defaultTimezone;
        $time = $time + $timezone * 3600;
        $prefix = $timezone < 0 ? '-' : '+';

        $zone = str_pad(str_pad(abs($timezone), 2, 0, STR_PAD_LEFT), 4, 0);

        return gmdate('D M j H:i:s', $time) . ' UTC' . $prefix . $zone . ' ' . gmdate('Y', $time);
    }

    /**
     * Transform input time to iso time
     *
     * @param string $time
     * @param int    $timezone
     *
     * @access public
     *
     * @return string time string
     */
    public static function isoTime($time = null, $timezone = null)
    {
        //$timezone = $timezone ? $timezone : self::getDI()->getConfig()->datetime->defaultTimezone;
        $time = $time ? $time : time();
        return $time = gmdate('c', $time);
    }

    public static function datetime($time = '', $format = '', $timezone = null)
    {
        $timezone = $timezone ? $timezone : self::getDI()->getConfig()->datetime->defaultTimezone;
        $format = $format ? $format : self::getDI()->getConfig()->datetime->defaultFormat;
        $time = $time ? $time : time();
        $time = is_numeric($time) ? $time : strtotime($time);
        $time = $time + $timezone * 3600;

        return gmdate($format, $time);
    }
}
