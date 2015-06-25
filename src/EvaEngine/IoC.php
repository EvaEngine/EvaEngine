<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine;

use Eva\EvaEngine\Exception\RuntimeException;
use Phalcon\DI;

/**
 * Class IoC
 * @package Eva\EvaEngine
 */
class IoC
{

    /**
     * @return DI
     */
    public static function getDI()
    {
        return \Phalcon\DI::getDefault();
    }


    /**
     * Resolves the service based on its configuration
     *
     * @param  string $name
     * @param  array $parameters
     * @throws RuntimeException
     * @return mixed
     */
    public static function get($name, $parameters = null)
    {
        return self::getDI()->get($name);
    }
}
