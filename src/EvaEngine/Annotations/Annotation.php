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

    public function getValue()
    {
        return $this->value;
    }

    public function isDescription()
    {
        return $this->type === self::TYPE_DESCRIPTION;
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
}
