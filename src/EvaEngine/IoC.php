<?php

namespace Eva\EvaEngine;

// +----------------------------------------------------------------------
// | [EvaEngine]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-8-16 11:25
// +----------------------------------------------------------------------
// + IoC.php
// +----------------------------------------------------------------------

use Phalcon\DI;

class IoC
{
    /**
     * @var DI
     */
    private static $di;

    public static function getDI()
    {
        return self::$di;

    }

    public static function setDI(DI $di)
    {
        self::$di = $di;
    }

    /**
     * Resolves the service based on its configuration
     *
     * @param string $name
     * @param array $parameters
     * @throws \RuntimeException
     * @return mixed
     */
    public static function get($name, $parameters = null)
    {
        if (self::$di == null) {
            throw new \RuntimeException('IoC container is null!');
        }
        return self::$di->get($name, $parameters);
    }

    /**
     * Registers a service in the services container
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean $shared
     * @throws \RuntimeException
     * @return \Phalcon\DI\ServiceInterface
     */
    public static function set($name, $definition, $shared = null)
    {
        if (self::$di == null) {
            throw new \RuntimeException('IoC container is null!');
        }
        self::$di->set($name, $definition, $shared);
    }
} 