<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class LengthException extends LogicException
{
    protected $statusCode = 400;
}
