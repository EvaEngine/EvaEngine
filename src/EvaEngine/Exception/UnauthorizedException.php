<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class UnauthorizedException extends LogicException
{
    protected $statusCode = 401;
}
