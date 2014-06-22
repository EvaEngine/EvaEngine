<?php
/**
 * Eva\EvaEngine
 */

namespace Eva\EvaEngine\Exception;

class IOException extends RuntimeException
{
    protected $statusCode = 500;
}
