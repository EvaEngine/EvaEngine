<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpRequestExceptionTrait
{
    /**
     * @var RequestInterface
     */
    protected $request;


    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response ? true : false;
    }

    public function __construct(
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        $code = null,
        \Exception $previous = null,
        $statusCode = null
    ) {
        $this->request = $request;
        $this->response = $response;

        parent::__construct($message, $code, $previous, $statusCode);
    }
}
