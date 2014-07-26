<?php

namespace Eva\EvaEngine\Error;

use Phalcon\DI;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\AdapterInterface as LoggerInterface;

class ErrorHandler implements ErrorHandlerInterface
{
    protected static $errorController = 'error';

    protected static $errorControllerNamespace = 'Eva\EvaEngine\Mvc\Controller';

    protected static $errorControllerAction = 'index';

    protected static $errorLayout;

    protected static $errorTemplate;

    protected static $logger = false;

    //protected static $errorLevel

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!($errno & error_reporting())) {
            return;
        }

        $options = array(
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'isError' => true,
        );

        static::errorProcess(new Error($options));
    }

    public static function exceptionHandler(\Exception $e)
    {
        $options = array(
            'type' => $e->getCode(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'isException' => true,
            'exception' => $e,
        );

        static::errorProcess(new Error($options));
    }

    public static function shutdownHandler()
    {
        if (!is_null($options = error_get_last())) {
            static::errorProcess(new Error($options));
        }
    }

    public static function setErrorController($controller)
    {
        static::$errorController = $controller;
    }

    public static function getErrorController()
    {
        return static::$errorController;
    }

    public static function setErrorControllerNamespace($controllerNamespace)
    {
        static::$errorControllerNamespace = $controllerNamespace;
    }

    public static function getErrorControllerNamespace()
    {
        return static::$errorControllerNamespace;
    }

    public static function setErrorControllerAction($action)
    {
        static::$errorControllerAction = $action;
    }

    public static function getErrorControllerAction()
    {
        return static::$errorControllerAction;
    }

    public static function setErrorLayout()
    {
    }

    public static function setErrorTemplate()
    {
    }

    public static function getLogger()
    {
        if (static::$logger !== false) {
            return static::$logger;
        }

        $di = DI::getDefault();
        $config = $di->getConfig();

        if (!isset($config->error->disableLog) ||
            (isset($config->error->disableLog) && $config->error->disableLog) ||
            empty($config->error->logPath)
        ) {
            return static::$logger = null;
        }

        static::$logger = new FileLogger($config->error->logPath . '/' . 'system_error_' . date('Ymd') . '.log');

        return static::$logger;
    }

    public static function setLogger(LoggerInterface $logger)
    {
        static::$logger = $logger;
        return self;
    }

    protected static function logError(Error $error)
    {
        $logger = static::getLogger();
        if (!$logger) {
            return;
        }

        return $logger->log($error);
    }

    protected static function errorProcess(Error $error)
    {
        static::logError($error);
        $useErrorController = false;

        if ($error->isException()) {
            $useErrorController = true;
        } else {
            switch ($error->type()) {
                case E_WARNING:
                case E_NOTICE:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                case E_STRICT:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                case E_ALL:
                    break;
                default:
                    $useErrorController = true;
            }
        }

        if (!$useErrorController) {
            return;
        }

        $di = DI::getDefault();
        $dispatcher = $di->getShared('dispatcher');
        $view = $di->getShared('view');
        $response = $di->getShared('response');
        $response->setStatusCode($error->statusCode(), $error->statusMessage());

        $dispatcher->setNamespaceName(static::getErrorControllerNamespace());
        $dispatcher->setControllerName(static::getErrorController());
        $dispatcher->setActionName(static::getErrorControllerAction());
        $dispatcher->setParams(array('error' => $error));

        //Here need to clear last view output in pre controller
        $view->finish();
        $view->start();
        $dispatcher->dispatch();
        $view->render();
        $view->finish();

        return $response->setContent($view->getContent())->send();
    }
}
