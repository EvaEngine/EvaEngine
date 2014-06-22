<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class ResourceNotFoundException extends LogicException
{
    protected $statusCode = 404;
}
