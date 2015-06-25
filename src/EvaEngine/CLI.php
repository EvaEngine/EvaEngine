<?php
// +----------------------------------------------------------------------
// | wallstreetcn
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/17 下午6:51
// +----------------------------------------------------------------------
// + CLI.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine;


use Symfony\Component\Console\Application;

class CLI extends Application implements \Phalcon\DI\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface
{
    protected $_dependencyInjector;

    protected $_eventsManager;

    protected $_modules;

    /**
     * Sets the events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     *
     * @return \Phalcon\Events\ManagerInterface
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function setDI($dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }
}