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
     * @var DI
     */
    private static $di;

    /**
     * @return DI
     */
    public static function getDI()
    {
        return self::$di;
    }

    /**
     * @param DI $di
     */
    public static function setDI(DI $di)
    {
        self::$di = $di;
    }

    /**
     * Resolves the service based on its configuration
     *
     * @param  string $name
     * @param  array  $parameters
     * @throws RuntimeException
     * @return mixed
     */
    public static function get($name, $parameters = null)
    {
        if (self::$di == null) {
            throw new RuntimeException('IoC container is null!');
        }
        return self::$di->get($name, $parameters);
    }

    /**
     * Registers a service in the services container
     *
     * @param  string  $name
     * @param  mixed   $definition
     * @param  boolean $shared
     * @throws RuntimeException
     * @return \Phalcon\DI\ServiceInterface
     */
    public static function set($name, $definition, $shared = null)
    {
        if (self::$di == null) {
            throw new RuntimeException('IoC container is null!');
        }
        self::$di->set($name, $definition, $shared);
    }
}
