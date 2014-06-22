<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class BadMethodCallException extends LogicException
{
    protected $statusCode = 405;
}
