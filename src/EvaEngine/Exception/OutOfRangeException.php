<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class OutOfRangeException extends LogicException
{
    protected $statusCode = 400;
}
