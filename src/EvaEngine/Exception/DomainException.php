<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class DomainException extends LogicException
{
    protected $statusCode = 400;
}
