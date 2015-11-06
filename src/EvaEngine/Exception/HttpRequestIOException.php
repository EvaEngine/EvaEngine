<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpRequestIOException extends IOException implements HttpRequestExceptionInterface
{
    use HttpRequestExceptionTrait;


    /**
     * HttpRequestExceptionTrait constructor.
     * @param $message
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param array $handlerContext
     * @param null $code
     * @param \Exception|null $previous
     * @param null $statusCode
     */
    public function __construct(
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        array $handlerContext = [],
        $code = null,
        \Exception $previous = null,
        $statusCode = null
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->handlerContext = $handlerContext;

        parent::__construct($message, $code, $previous, $statusCode);
    }
}
