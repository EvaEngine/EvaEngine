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
    /** @var RequestInterface */
    private $request;

    /** @var ResponseInterface */
    private $response;

    /** @var array */
    private $handlerContext;

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param RequestInterface $request Request
     * @param ResponseInterface $response Response received
     * @param \Exception $previous Previous exception
     * @param array $ctx Optional handler context.
     *
     * @return self
     */
    public static function create(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null,
        array $ctx = []
    ) {
        if (!$response) {
            return new self(
                'Error completing request',
                $request,
                null,
                $previous,
                $ctx
            );
        }

        $level = floor($response->getStatusCode() / 100);
        if ($level == '4') {
            $label = 'Client error response';
            $className = HttpRequestInvalidArgumentException::class;
        } elseif ($level == '5') {
            $label = 'Server error response';
            $className = HttpRequestIOException::class;
        } else {
            $label = 'Unsuccessful response';
            $className = __CLASS__;
        }

        $message = $label . ' [url] ' . $request->getUri()
            . ' [http method] ' . $request->getMethod()
            . ' [status code] ' . $response->getStatusCode()
            . ' [reason phrase] ' . $response->getReasonPhrase();

        return new $className($message, $request, $response, $previous, $ctx);
    }

    /**
     * Get the request that caused the exception
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the associated response
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Check if a response was received
     *
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }

    /**
     * Get contextual information about the error from the underlying handler.
     *
     * The contents of this array will vary depending on which handler you are
     * using. It may also be just an empty array. Relying on this data will
     * couple you to a specific handler, but can give more debug information
     * when needed.
     *
     * @return array
     */
    public function getHandlerContext()
    {
        return $this->handlerContext;
    }

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
