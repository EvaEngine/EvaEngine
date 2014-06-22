<?php

namespace Eva\EvaEngine\Mvc\Model\Validator;

use Phalcon\Mvc\Model\Validator;

class Between extends Validator 
{
    public function validate($model)
    {
        $field = $this->getOption('field');
        $minimum = $this->getOption('minimum');
        $maximum = $this->getOption('maximum');

        if($model->$field > $maximum || $model->$field < $minimum) {
            $this->appendMessage(sprintf('Field %s must between %s ~ %s', $field, $minimum, $maximum));
            return false;
        }
        return true;
    }

}
