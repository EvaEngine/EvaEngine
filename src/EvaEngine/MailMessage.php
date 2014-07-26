<?php

namespace Eva\EvaEngine;

use Phalcon\Mvc\View;

class MailMessage implements \Phalcon\DI\InjectionAwareInterface
{
    protected $di;

    protected $message;

    protected $from;

    protected $to;

    protected $subject;

    protected $attachments;

    protected $layout;

    protected $template;

    protected $parameters;

    protected $inlineSubject = true;

    protected $htmlFormat = true;

    public function setDI($di)
    {
        $this->di = $di;
    }

    public function getDI()
    {
        return $this->di;
    }

    public function inlineSubject($inlineSubject = true)
    {
        $this->inlineSubject = $inlineSubject;

        return $this;
    }

    public function htmlFormat($htmlFormat = true)
    {
        $this->htmlFormat = $htmlFormat;

        return $this;
    }

    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    public function getFrom()
    {
        if ($this->from) {
            return $this->from;
        }

        $this->from = $this->getDI()->getConfig()->mailer->defaultFrom;

        return $this->from;
    }

    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function assign(array $parameters = array())
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function render()
    {
        $view = new \Phalcon\Mvc\View\Simple();
        $template = $this->getTemplate();
        $view->setViewsDir(dirname($template) . '/');
        $filename = basename($template);
        $file = explode('.', $filename);
        array_pop($file);
        $file = implode('.', $file);

        return $view->render($file, $this->getParameters());
    }

    public function initialize()
    {
        $message = \Swift_Message::newInstance();
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

    public function toSystemUrl($path)
    {
        return $this->getDI()->getConfig()->mailer->systemPath . $path;
    }

    public function toStaticUrl($path)
    {
        return $this->getDI()->getConfig()->mailer->staticPath . $path;
    }

    public function getMessage()
    {
        $this->initialize();

        return $this->message;
    }
}
