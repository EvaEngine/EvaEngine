<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class ResourceConflictException extends LogicException
{
    protected $statusCode = 409;
}
