<?php

namespace Eva\EvaEngine\Error;

interface ErrorHandlerInterface
{
    public static function errorHandler($errno, $errstr, $errfile, $errline);

    public static function exceptionHandler(\Exception $e);

    public static function shutdownHandler();
}
