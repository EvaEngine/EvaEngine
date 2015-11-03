<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpRequestInvalidArgumentException extends InvalidArgumentException implements HttpRequestExceptionInterface
{
    protected $statusCode = 400;

    use HttpRequestExceptionTrait;

    public function getIssueCode()
    {
        return '';
    }

    public function getIssueMessage()
    {
        return '';
    }
}
