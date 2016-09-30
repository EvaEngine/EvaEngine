<?php
/**
 * Wscn
 *
 * @link: https://github.com/wallstreetcn/wallstreetcn
 * @author: franktung<franktung@gmail.com>
 * @Date: 16/9/30
 * @Time: 下午4:59
 *
 */

namespace Eva\EvaEngine\Exception;


class OriginNotAllowedException extends StandardException
{
    /**
     * @var int
     */
    protected $statusCode = 403;
}