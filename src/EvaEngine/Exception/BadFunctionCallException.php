<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class BadFunctionCallException extends LogicException
{
    protected $statusCode = 400;
}
