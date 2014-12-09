<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Error;

/**
 * EvaEngine Error interface
 * @package Eva\EvaEngine\Error
 */
interface ErrorHandlerInterface
{
    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return mixed
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline);

    /**
     * @param \Exception $e
     * @return mixed
     */
    public static function exceptionHandler(\Exception $e);

    /**
     * @return mixed
     */
    public static function shutdownHandler();
}
