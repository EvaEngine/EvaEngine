<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Validator;
use Phalcon\Mvc\Model\ValidatorInterface;

/**
 * Class Between
 * @package Eva\EvaEngine\Mvc\Model\Validator
 */
class Between extends Validator implements ValidatorInterface
{
    /**
     * @param EntityInterface $model
     * @return bool
     */
    public function validate(EntityInterface $model)
    {
        $field = $this->getOption('field');
        $minimum = $this->getOption('minimum');
        $maximum = $this->getOption('maximum');

        if ($model->$field > $maximum || $model->$field < $minimum) {
            $this->appendMessage(sprintf('Field %s must between %s ~ %s', $field, $minimum, $maximum));
            return false;
        }
        return true;
    }
}
