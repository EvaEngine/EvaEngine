<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Exception;

use Eva\EvaEngine\Form;

class FormValidateException extends InvalidArgumentException
{
    /**
     * @var Form
     */
    private $form;

    public function getForm()
    {
        return $this->form;
    }

    public function getFormMessages()
    {
        $output = [];
        $messages = $this->form->getMessages();
        foreach ($messages as $message) {
            $output[] = $message->getMessage();
        }
        return $output;
    }

    public function __toString()
    {
        return implode('|', $this->getFormMessages()) . '|' . parent::__toString();
    }

    public function __construct(
        Form $form,
        $message = null,
        $code = null,
        \Exception $previous = null,
        $statusCode = null
    ) {
        $this->form = $form;
        $message = $message ?: sprintf('Form %s validate failed', get_class($form));
        parent::__construct($message, $code, $previous, $statusCode);
    }
}
