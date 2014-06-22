<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class VerifyFailedException extends LogicException
{
    protected $statusCode = 403;
}
