<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Error;

use Phalcon\DI;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\AdapterInterface as LoggerInterface;
use Phalcon\Events\Manager as EventsManager;

/**
 * ErrorHandler for http request
 * @package Eva\EvaEngine\Error
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @var string
     */
    protected static $errorController = 'error';

    /**
     * @var string
     */
    protected static $errorControllerNamespace = 'Eva\EvaEngine\Mvc\Controller';

    /**
     * @var string
     */
    protected static $errorControllerAction = 'index';

    /**
     * @var bool|LoggerInterface
     */
    protected static $logger = false;

    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return void
     */
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

    /**
     * @param \Exception $e
     * @return void
     */
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

    /**
     * Error handler callback for php shutdown processing
     */
    public static function shutdownHandler()
    {
        if (!is_null($options = error_get_last())) {
            static::errorProcess(new Error($options));
        }
    }

    /**
     * @param $controller
     */
    public static function setErrorController($controller)
    {
        static::$errorController = $controller;
    }

    /**
     * @return string
     */
    public static function getErrorController()
    {
        return static::$errorController;
    }

    /**
     * @param $controllerNamespace
     */
    public static function setErrorControllerNamespace($controllerNamespace)
    {
        static::$errorControllerNamespace = $controllerNamespace;
    }

    /**
     * @return string
     */
    public static function getErrorControllerNamespace()
    {
        return static::$errorControllerNamespace;
    }

    /**
     * @param $action
     */
    public static function setErrorControllerAction($action)
    {
        static::$errorControllerAction = $action;
    }

    /**
     * @return string
     */
    public static function getErrorControllerAction()
    {
        return static::$errorControllerAction;
    }

    /**
     * @return null|LoggerInterface
     */
    public static function getLogger()
    {
        if (static::$logger !== false) {
            return static::$logger;
        }

        $di = DI::getDefault();
        $config = $di->getConfig();

        if (!isset($config->error->disableLog)
            || (isset($config->error->disableLog) && $config->error->disableLog)
            || empty($config->error->logPath)
        ) {
            return static::$logger = null;
        }

        static::$logger = new FileLogger($config->error->logPath . '/' . 'system_error.log');

        return static::$logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return mixed
     */
    public static function setLogger(LoggerInterface $logger)
    {
        static::$logger = $logger;
        return self;
    }

    /**
     * @param Error $error
     * @return LoggerInterface
     */
    protected static function logError(Error $error)
    {
        $logger = static::getLogger();
        if (!$logger) {
            return null;
        }

        $logLevel = $error->logLevel();
        return $logger->log($logLevel, $error);
    }

    /**
     * Error process for default error handler. below steps will be processed:
     * - save error to log
     * - collected error controller info
     * - re dispatch error controller
     * @param Error $error
     * @return mixed
     */
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
            return false;
        }

        $di = DI::getDefault();
        /**
 * @var \Phalcon\Dispatcher $dispatcher
*/
        $dispatcher = $di->getDispatcher();
        //Clear old eventsmanager to void trigger dispatch again
        $dispatcher->setEventsManager(new EventsManager());

        /**
 * @var \Eva\EvaEngine\Mvc\View $view
*/
        $view = $di->getView();
        /**
 * @var \Phalcon\Http\Response $response
*/
        $response = $di->getResponse();
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
