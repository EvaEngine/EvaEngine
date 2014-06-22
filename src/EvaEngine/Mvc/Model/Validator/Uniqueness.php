<?php

namespace Eva\EvaEngine\Mvc\Model\Validator;

use Phalcon\Mvc\Model\Validator\Uniqueness as PhalconUniqueness;
use Phalcon\Mvc\Model\ValidatorInterface;

class Uniqueness extends PhalconUniqueness implements ValidatorInterface
{
    public function validate($model)
    {
        $conditions = $this->getOption('conditions');
        $bind = $this->getOption('bind');
        if(!$conditions && !$bind) {
            return parent::validate($model);
        }


        $operator = $this->getOption('operator');
        $operator = $operator ? $operator : 'AND';
        $field = $this->getOption('field');
        $conditionString = "$field = ?0 $operator ";
        $conditionString .= $conditions;
        $bindArray = array($model->$field);
        $bindArray += $bind;
        $item = $model->findFirst(array(
            'conditions' => $conditionString,
            'bind' => $bindArray
        ));

        if($item) {
            $this->appendMessage(sprintf('Field %s not unique', $field));
            return false;
        }
        return true;
    }

}
