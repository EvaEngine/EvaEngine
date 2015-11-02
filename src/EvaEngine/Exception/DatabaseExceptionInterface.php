<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Exception;

/**
 * Interface DatabaseExceptionInterface
 * @package Eva\EvaEngine\Exception
 */
interface DatabaseExceptionInterface
{
    /**
     * @return Array
     */
    public function getDbErrorMessages();
}
