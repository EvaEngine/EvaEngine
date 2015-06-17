<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/16 下午5:54
// +----------------------------------------------------------------------
// + ViewHelper.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\View;


use Eva\EvaEngine\Exception\RuntimeException;
use Eva\EvaEngine\Mvc\View;
use Phalcon\Text;

class ViewHelper
{
    protected $helpers = array();

    public function register($name, $definition)
    {
        if (!is_callable($definition)) {
            throw new RuntimeException(sprintf('ViewHelper definition must be callable, viewHelper name: "%s"', $name));
        }
        $this->helpers[$name] = $definition;
    }

    public function __call($helperName, $args)
    {
        if (empty($this->helpers[$helperName])) {
            throw new RuntimeException(sprintf('Request helper %s not registered', $helperName));
        }
        call_user_func_array($this->helpers[$helperName], $args);
    }

    public function config()
    {
        $config = eva_get('config');
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

    public function component($componentName, array $params = array())
    {
        return View::getComponent($componentName, $params);
    }

    public function _($message = null, $replacement = null)
    {
        return $this->translate($message, $replacement);
    }

    public function translate($message = null, $replacement = null)
    {
        $translate = eva_get('translate');
        if ($message) {
            return $translate->_(trim($message), $replacement);
        }

        return $translate;
    }

    public function flashOutput()
    {
        $flash = eva_get('flash');
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

    public function uri($uri, array $query = null, array $baseQuery = null)
    {
        $url = eva_get('url');
        if ($query && $baseQuery) {
            $query = array_merge($baseQuery, $query);
        }

        return $url->get($uri, $query);
    }

    public function thumb($uri, $query = null, $configKey = 'default')
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


        $config = eva_get('config');
        if (isset($config->thumbnail->$configKey->baseUri) && $baseUrl = $config->thumbnail->$configKey->baseUri) {
            return $baseUrl . $uri;
        }

        return $uri;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     * @param $email
     * @param int $s
     * @param string $d
     * @param string $r
     * @return string
     */
    public function gravatar($email, $s = 80, $d = 'mm', $r = 'g')
    {
        $url = 'http://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";

        return $url;
    }


    /**
     * Transform input time to time string which can be parsed by javascript
     * @param string $time
     * @param null $timezone
     * @return string javascript parse-able string
     */
    public function jsTime($time = '', $timezone = null)
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
     * @param int $timezone
     *
     * @access public
     *
     * @return string time string
     */
    public function isoTime($time = null, $timezone = null)
    {
        //$timezone = $timezone ? $timezone : self::getDI()->getConfig()->datetime->defaultTimezone;
        $time = $time ? $time : time();

        return $time = gmdate('c', $time);
    }

    public function datetime($time = '', $format = '', $timezone = null)
    {
        $timezone = $timezone ? $timezone : self::getDI()->getConfig()->datetime->defaultTimezone;
        $format = $format ? $format : self::getDI()->getConfig()->datetime->defaultFormat;
        $time = $time ? $time : time();
        $time = is_numeric($time) ? $time : strtotime($time);
        $time = $time + $timezone * 3600;

        return gmdate($format, $time);
    }
}