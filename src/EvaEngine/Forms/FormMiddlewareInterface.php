<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Forms;

use Phalcon\Forms\Element;

/**
 * Interface FormMiddleWareInterface
 *
 * @package Eva\EvaEngine\Forms
 */
interface FormMiddleWareInterface
{
    /**
     * @param Element $element
     * @return Element
     */
    public function pipeElement(Element $element);
}
