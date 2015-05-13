<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Eva\EvaEngine\Mvc\Controller;

use Phalcon\Mvc\Controller;
use Eva\EvaEngine\Exception;
use Phalcon\Forms\Form;
use Phalcon\Mvc\Model;

/**
 * EvaEngine base controller
 * - Automatic json / jsonp support
 * - Redirect handler
 * - Json output convert for exception
 * @package Eva\EvaEngine\Mvc\Controller
 */
class ControllerBase extends Controller
{
    /**
     * Recommended Reason Phrases
     * @var array
     */
    protected $recommendedReasonPhrases = array(
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

    /**
     * @param $dispatcher
     */
    public function afterExecuteRoute($dispatcher)
    {
        if ($this instanceof JsonControllerInterface) {
            $this->response->setContentType('application/json', 'utf-8');
            $callback = $this->request->getQuery('callback');
            if ($callback) {
                $this->response->setContent($callback . '(' . $this->response->getContent() . ')');
            }
        }
    }

    /**
     * @param null $defaultRedirect
     * @param null $redirectType
     * @param bool $securityCheck
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception\InvalidArgumentException
     */
    public function redirectHandler($defaultRedirect = null, $redirectType = null, $securityCheck = false)
    {
        $redirectInputName = $redirectType ? "__redirect_$redirectType" : '__redirect';
        $formRedirect = $this->request->getPost($redirectInputName);
        $formRedirect = $formRedirect ?: $this->request->getPost('__redirect');

        $redirectUri = $formRedirect ?: $defaultRedirect;
        if (!$redirectUri) {
            throw new Exception\InvalidArgumentException(sprintf('No redirect url input'));
        }

        return $this->response->redirect($redirectUri);
    }

    /**
     * @param $code
     * @param $message
     * @param string $messageType
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function showErrorMessage($code, $message, $messageType = 'error')
    {
        if (!isset($this->recommendedReasonPhrases[$code])) {
            throw new Exception\InvalidArgumentException(sprintf('No http response code %s supported', $code));
        }

        $this->flashSession->$messageType($message->getMessage());
        $this->response->setStatusCode($code, $this->recommendedReasonPhrases[$code]);

        return $this;
    }

    /**
     * @param $code
     * @param $message
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception\InvalidArgumentException
     */
    public function showErrorMessageAsJson($code, $message)
    {
        if (!isset($this->recommendedReasonPhrases[$code])) {
            throw new Exception\InvalidArgumentException(sprintf('No http response code %s supported', $code));
        }

        $this->response->setContentType('application/json', 'utf-8');
        $this->response->setStatusCode($code, $this->recommendedReasonPhrases[$code]);

        return $this->response->setJsonContent(
            array(
                'errors' => array(
                    array(
                        'code' => $code,
                        'message' => $message
                    )
                ),
            )
        );
    }

    /**
     * @param $exception
     * @param null $messages
     * @param string $messageType
     * @return $this
     * @throws
     */
    public function showException($exception, $messages = null, $messageType = 'error')
    {
        $messageArray = array();
        if ($messages) {
            foreach ($messages as $message) {
                $this->flashSession->$messageType($message->getMessage());
                $messageArray[] = $message->getMessage();
            }
        }

        $logger = $this->getDI()->get('logException');
        $logger->log(
            implode('', $messageArray) . "\n" .
            get_class($exception) . ":" . $exception->getMessage() . "\n" .
            " File=" . $exception->getFile() . "\n" .
            " Line=" . $exception->getLine() . "\n" .
            $exception->getTraceAsString()
        );

        //Not eva exception, keep throw
        if (!($exception instanceof Exception\ExceptionInterface)) {
            throw $exception;
        }

        $this->response->setStatusCode($exception->getStatusCode(), $exception->getMessage());
        $this->flashSession->$messageType($exception->getMessage());

        return $this;
    }

    public function showExceptionAsJson($exception, $messages = null)
    {
        $this->response->setContentType('application/json', 'utf-8');
        if (!($exception instanceof Exception\ExceptionInterface)) {
            $this->response->setStatusCode('500', 'System Runtime Exception');

            return $this->response->setJsonContent(
                array(
                    'errors' => array(
                        array(
                            'code' => $exception->getCode(),
                            'message' => $exception->getMessage(),
                        )
                    ),
                )
            );
        }

        $this->response->setStatusCode($exception->getStatusCode(), $exception->getMessage());
        $errors = array();
        if ($messages) {
            foreach ($messages as $message) {
                $errors[] = array(
                    'code' => 0,
                    'message' => $message->getMessage(),
                );
            }
        }
        $errors[] = array(
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        );

        return $this->response->setJsonContent(
            array(
                'errors' => $errors
            )
        );
    }

    /**
     * @param $exception
     * @param null $messages
     * @param string $messageType
     */
    public function ignoreException($exception, $messages = null, $messageType = 'debug')
    {
        $messageArray = array();
        if ($messages) {
            foreach ($messages as $message) {
                $messageArray[] = $message->getMessage();
            }
        }

        $logger = $this->getDI()->get('logException');
        $logger->debug($exception);
        /*
        $logger->debug(
            implode('', $messageArray) . "\n" .
            get_class($exception) . ":" . $exception->getMessage(). "\n" .
            " File=" . $exception->getFile() . "\n" .
            " Line=", $exception->getLine() . "\n" .
            $exception->getTraceAsString()
        );
        */

    }

    /**
     * @param Model $model
     * @param string $messageType
     * @return $this
     */
    public function showModelMessages(Model $model, $messageType = 'warning')
    {
        $messages = $model->getMessages();
        if ($messages) {
            foreach ($messages as $message) {
                $this->flashSession->$messageType($message->getMessage());
            }
        }

        return $this;
    }


    /**
     * @param Model $model
     * @param string $messageType
     * @return $this
     */
    public function showModelMessagesAsJson(Model $model, $messageType = 'warning')
    {
        $messages = $model->getMessages();
        if ($messages) {
            foreach ($messages as $message) {
                $this->flashSession->$messageType($message->getMessage());
            }
        }

        return $this;
    }

    /**
     * @param Form $form
     * @param string $messageType
     * @return $this
     */
    public function showInvalidMessages(Form $form, $messageType = 'warning')
    {
        $messages = $form->getMessages();
        if ($messages) {
            foreach ($messages as $message) {
                $this->flashSession->$messageType($message->getMessage());
            }
        }

        return $this;
    }

    /**
     * @param Form $form
     * @param string $messageType
     * @return \Phalcon\Http\ResponseInterface
     */
    public function showInvalidMessagesAsJson(Form $form, $messageType = 'warning')
    {
        $messages = $form->getMessages();
        $content = array();
        foreach ($messages as $message) {
            $content[] = array(
                'code' => 10001,
                'message' => $message->getMessage(),
            );
        }
        $this->response->setStatusCode(400, $this->recommendedReasonPhrases[400]);

        return $this->response->setJsonContent(
            array(
                'errors' => $content
            )
        );
    }

    /**
     * @param $object
     * @param int $code
     * @return \Phalcon\Http\ResponseInterface
     */
    public function showResponseAsJson($object, $code = 200)
    {
        $this->response->setContentType('application/json', 'utf-8');

        return $this->response->setJsonContent($object);
    }

    /**
     * handler cross domain request
     *
     * @param string $allowCredentials Access-Control-Allow-Credentials
     * @param string $allowMethods Access-Control-Allow-Methods
     * @param string $allowHeaders Access-Control-Allow-Headers
     */
    public function cors(
        $allowCredentials = 'true',
        $allowMethods = '*',
        $allowHeaders = null
    ) {
        if (empty($_SERVER['HTTP_ORIGIN'])) {
            return;
        }
        if ($allowHeaders == null) {
            $allowHeaders = 'Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma,'
                . 'Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With';
        }
        $config = $this->getDI()->getConfig();
        $checked = false;
        // 白名单检测
        foreach ($config->cors as $domain) {
            if (ends_with($_SERVER['HTTP_ORIGIN'], $domain['domain'])) {
                $checked = true;
                break;
            }
        }
        if (!$checked) {
            return;
        }
        $this->response->setHeader('Access-Control-Allow-Credentials', (string)$allowCredentials);
        $this->response->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
        $this->response->setHeader('Access-Control-Allow-Methods', $allowMethods);
        $this->response->setHeader('Access-Control-Allow-Headers', $allowHeaders);
        if (strtoupper($this->request->getMethod()) == 'OPTIONS') {
            $this->response->send();

            return;
        }
    }

    public static function currentUrl()
    {
        return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' .
        "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    }
}
