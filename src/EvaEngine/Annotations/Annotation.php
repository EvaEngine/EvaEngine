<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Annotations;

use Phalcon\Annotations\Annotation as BaseAnnotation;

class Annotation extends BaseAnnotation
{
    const TYPE_DESCRIPTION = 'desc';

    const TYPE_ARGUMENT = 'arg';

    protected $value;

    protected $type;

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function isDescription()
    {
        return $this->type === self::TYPE_DESCRIPTION;
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function setExprArguments($exprArgument)
    {
        $this->_exprArguments = $exprArgument;
        return $this;
    }

    public function setArguments($arguments)
    {
        $this->_arguments = $arguments;
        return $this;
    }

    public function __construct(array $reflectionData)
    {
        parent::__construct($reflectionData);
        if (!empty($reflectionData['mainType'])) {
            $this->type = $reflectionData['mainType'];
        }

        if (!empty($reflectionData['value'])) {
            $this->value = $reflectionData['value'];
        }
    }

    public function __toString()
    {
        if ($this->isDescription()) {
            return " * {$this->getValue()}\n";
        }

        if ($value = $this->getValue()) {
            return " * @{$this->getName()} {$this->getValue()}\n";
        }


        $docComment = " * @{$this->getName()}(\n";
        if ($this->numberArguments() > 0) {
            $arguments = $this->getArguments();
            $argumentsArray = [];
            foreach ($arguments as $key => $value) {
                $argumentsArray[] = " *  $key=\"$value\"";
            }
            $docComment .= implode(",\n", $argumentsArray) . "\n * )\n";
        }
        $docComment .= "\n";
        return $docComment;
    }
}
