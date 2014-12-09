<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Eva\EvaEngine\Exception;

/**
 * Basic Exception Interface, defined http status code as a part of exception
 * @package Eva\EvaEngine\Exception
 */
interface ExceptionInterface
{
    /**
     * @return int
     */
    public function getStatusCode();
}
