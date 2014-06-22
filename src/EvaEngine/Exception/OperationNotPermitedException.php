<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class OperationNotPermitedException extends LogicException
{
    protected $statusCode = 403;
}
