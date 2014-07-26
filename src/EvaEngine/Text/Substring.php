<?php

namespace Eva\EvaEngine\Text;

class Substring
{
    /**
    * Cutting string without word break
    *
    * @access public
    * @param string $str string
    * @param int $length allowed length int
    *
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
