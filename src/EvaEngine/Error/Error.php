<?php

namespace Eva\EvaEngine\Error;

use Eva\EvaEngine\Exception\ExceptionInterface;
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Dispatcher;

class Error
{
    /**
    * @var array
    */
    protected $attributes;

    /**
     * @var array Recommended Reason Phrases
     */
    protected $recommendedReasonPhrases = array(
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

    /**
    * Class constructor sets the attributes.
    *
    * @param array $options
    */
    public function __construct(array $options = array())
    {
        $defaults = array(
            'type' => -1,
            'message' => 'No error message',
            'file' => '',
            'line' => '',
            'exception' => null,
            'isException' => false,
            'isError' => false,
            'errorType' => '',
            'statusCode' => 500,
            'statusMessage' => 'Internal Server Error',
        );

        $options = array_merge($defaults, $options);

        $options['errorType'] = $this->getErrorType($options['type']);
        $exception = $options['exception'];

        if ($options['isException'] && $exception instanceof DispatcherException) {
            switch ($exception->getCode()) {
                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                $options['statusCode'] = 404;
                break;
                case Dispatcher::EXCEPTION_INVALID_PARAMS:
                $options['statusCode'] = 401;
                break;
                default:
                $options['statusCode'] = 500;
            }
        }

        if ($options['isException'] && $exception instanceof ExceptionInterface) {
            $options['statusCode'] = $exception->getStatusCode();
        }

        if ($options['isException']) {
            $options['message'] = $exception->getMessage();
        }

        $options['statusMessage'] = isset($this->recommendedReasonPhrases[$options['statusCode']]) ? $this->recommendedReasonPhrases[$options['statusCode']] : 'Internal Server Error';

        foreach ($options as $option => $value) {
            $this->attributes[$option] = $value;
        }
    }

    /**
    * Magic method to retrieve the attributes.
    *
    * @param string $method
    * @param array $args
    * @return mixed
    */
    public function __call($method, $args)
    {
        return isset($this->attributes[$method]) ? $this->attributes[$method] : null;
    }

    /**
    * Maps error code to a string.
    *
    * @param integer $code
    * @return string
    */
    public function getErrorType($code)
    {
        switch ($code) {
            case 0:
            return 'Uncaught exception';
            case E_ERROR:
            return 'E_ERROR';
            case E_WARNING:
            return 'E_WARNING';
            case E_PARSE:
            return 'E_PARSE';
            case E_NOTICE:
            return 'E_NOTICE';
            case E_CORE_ERROR:
            return 'E_CORE_ERROR';
            case E_CORE_WARNING:
            return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
            return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
            return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
            return 'E_USER_ERROR';
            case E_USER_WARNING:
            return 'E_USER_WARNING';
            case E_USER_NOTICE:
            return 'E_USER_NOTICE';
            case E_STRICT:
            return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
            return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
            return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
            return 'E_USER_DEPRECATED';
        }

        return $code;
    }

    public function __toString()
    {
        $errorOrException = $this->isException() ? 'EXCEPTION' : 'ERROR';

        return sprintf("[%s][%s][%s][%s][%s][%s]",
                        $errorOrException,
                        $this->errorType(),
                        $this->type(),
                        $this->message(),
                        $this->file(),
                        $this->line()
                    );
    }

}
