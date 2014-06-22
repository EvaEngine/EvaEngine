<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class ResourceExpiredException extends LogicException
{
    protected $statusCode = 403;
}
