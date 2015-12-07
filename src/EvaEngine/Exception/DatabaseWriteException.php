<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Exception;

class DatabaseWriteException extends IOException implements DatabaseExceptionInterface
{
    /**
     * @var array
     */
    private $dbErrorMessages;

    /**
     * @return array
     */
    public function getDbErrorMessages()
    {
        return $this->dbErrorMessages;
    }

    public function __toString()
    {
        return implode('|', $this->getDbErrorMessages()) . '|' . parent::__toString();
    }

    public function __construct($message, array $errorMessages = [], $code = null, $previous = null, $statusCode = null)
    {
        $this->dbErrorMessages = $errorMessages;
        parent::__construct($message, $code, $previous, $statusCode);
    }
}
