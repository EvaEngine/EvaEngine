<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\View\Helper;

use Eva\EvaEngine\Exception\InvalidArgumentException;
use Eva\EvaEngine\View\Helper\Placeholder\Container;

/**
 * Helper for passing data between otherwise segregated Views. It's called
 * Placeholder to make its typical usage obvious, but can be used just as easily
 * for non-Placeholder things. That said, the support for this is only
 * guaranteed to effect subsequently rendered templates, and of course Layouts.
 */
class Placeholder
{
    /**
     * Placeholder items
     *
     * @var array
     */
    protected $items = array();

    /**
     * Default container class
     * @var string
     */
    protected $containerClass = 'Eva\EvaEngine\View\Helper\Placeholder\Container';

    /**
     * Placeholder helper
     * @param null $name
     * @param null $default
     * @return Container
     * @throws InvalidArgumentException
     */
    public function block($name = null, $default = null)
    {
        if ($name == null) {
            throw new
            InvalidArgumentException('Placeholder: missing argument.  $name is required by placeholder($name)');
        }

        $name = (string) $name;
        $container = $this->getContainer($name);
        if ($default && !$container->getValue()) {
            $container->set($default);
        }
        return $container;
    }

    /**
     * createContainer
     *
     * @param  string $key
     * @param  array  $value
     * @return Container
     */
    public function createContainer($key, array $value = array())
    {
        $key = (string) $key;

        $this->items[$key] = new $this->containerClass($value);
        return $this->items[$key];
    }

    /**
     * Retrieve a placeholder container
     *
     * @param  string $key
     * @return Container
     */
    public function getContainer($key)
    {
        $key = (string) $key;
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        $container = $this->createContainer($key);

        return $container;
    }

    /**
     * Does a particular container exist?
     *
     * @param  string $key
     * @return bool
     */
    public function containerExists($key)
    {
        $key = (string) $key;
        $return =  array_key_exists($key, $this->items);
        return $return;
    }
}
