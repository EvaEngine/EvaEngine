<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class InvalidArgumentException extends LogicException
{
    protected $statusCode = 400;
}
