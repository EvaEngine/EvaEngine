<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine;

use Phalcon\DiInterface;
use Phalcon\Mvc\View;
use Phalcon\DI\InjectionAwareInterface;
use Phalcon\Mvc\View\Simple as PhalconView;
use Swift_Message;

/**
 * An email sender based on Swift_Mail, support template
 * Class MailMessage
 * @package Eva\EvaEngine
 */
class MailMessage implements InjectionAwareInterface
{
    /**
     * @var \Phalcon\DiInterface
     */
    protected $di;

    /**
     * @var \Swift_Message
     */
    protected $message;

    /**
     * Email from
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $to;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $layout;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var bool
     */
    protected $inlineSubject = true;

    /**
     * @var bool
     */
    protected $htmlFormat = true;

    /**
     * @param \Phalcon\DiInterface $di
     */
    public function setDI(DiInterface $di)
    {
        $this->di = $di;
    }

    /**
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * @param bool $inlineSubject
     * @return $this
     */
    public function inlineSubject($inlineSubject = true)
    {
        $this->inlineSubject = $inlineSubject;
        return $this;
    }

    /**
     * @param bool $htmlFormat
     * @return $this
     */
    public function htmlFormat($htmlFormat = true)
    {
        $this->htmlFormat = $htmlFormat;
        return $this;
    }

    /**
     * @param $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        if ($this->from) {
            return $this->from;
        }

        $this->from = $this->getDI()->getConfig()->mailer->defaultFrom;
        return $this->from;
    }

    /**
     * @param $to
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return \Swift_Message
     */
    public function getMessage()
    {
        $this->initialize();
        return $this->message;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function assign(array $parameters = array())
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        $view = new PhalconView();
        $template = $this->getTemplate();
        $view->setViewsDir(dirname($template) . '/');
        $filename = basename($template);
        $file = explode('.', $filename);
        array_pop($file);
        $file = implode('.', $file);

        return $view->render($file, $this->getParameters());
    }


    /**
     * @param $path string
     * @return string
     */
    public function toSystemUrl($path)
    {
        return $this->getDI()->getConfig()->mailer->systemPath . $path;
    }

    /**
     * @param $path string
     * @return string
     */
    public function toStaticUrl($path)
    {
        return $this->getDI()->getConfig()->mailer->staticPath . $path;
    }



    /**
     * @return $this
     */
    public function initialize()
    {
        $message = Swift_Message::newInstance();
        $message->setFrom($this->getFrom());
        if ($this->template) {
            $template = $this->render();
            if ($this->inlineSubject) {
                $subject = strtok($template, "\n");
                $message->setSubject($subject);
                $count = 1;
                $template = trim(str_replace($subject, '', $template, $count), "\n\r\t");
            }

            if ($this->htmlFormat) {
                $message->setContentType('text/html');
            }
            $message->setBody($template);
        }
        if ($this->to) {
            $message->setTo($this->to);
        }
        $this->message = $message;
        return $this;
    }
}
