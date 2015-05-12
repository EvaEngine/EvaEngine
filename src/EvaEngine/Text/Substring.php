<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Text;

/**
 * Class Substring
 * @package Eva\EvaEngine\Text
 */
class Substring
{

    /**
     * Cutting string without word break
     * @param string $str
     * @param int    $length
     * @param bool   $withWrap
     * @return string
     */
    public static function substrEn($str = '', $length = 1, $withWrap = true)
    {
        $len = strlen($str);

        if ($len <= $length) {
            return $str;
        }

        for ($i = $length; $i > -1; $i--) {
            if ($str{$i} == ' ') {
                return substr($str, 0, $i) . ($withWrap ? ' ...' : '');
            }
        }

        return substr($str, 0, $length) . ($withWrap ? ' ...' : '');
    }

    /**
     * Cutting string support Chinese
     * @param $str
     * @param $length
     * @param bool   $withWrap
     * @param string $encoding
     * @return string
     */
    public static function substrCn($str, $length, $withWrap = true, $encoding = "UTF-8")
    {
        mb_internal_encoding($encoding);
        
        $len = mb_strlen($str);
        
        if ($len > $length) {
            $str = mb_substr($str, 0, $length);
        } else {
            return $str;
        }
        
        return $str . ($withWrap ? '...' : '');
    }
}
