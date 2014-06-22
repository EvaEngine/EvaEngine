<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class StandardException extends \Phalcon\Exception implements ExceptionInterface
{
    protected $statusCode = 500;

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function __construct($message, $code = 10000, $previous = null, $statusCode = null)
    {
        //Allow the third paramater to be statuscode
        if (is_numeric($previous)) {
            $statusCode = $previous;
            $previous = null;
        }

        if ($statusCode && is_numeric($statusCode) && $statusCode > 99 && $statusCode < 600) {
            $this->statusCode = $statusCode;
        }
        parent::__construct($message, $code, $previous);
    }

}
