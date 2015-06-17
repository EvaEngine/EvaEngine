<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/17 下午1:07
// +----------------------------------------------------------------------
// + Multiple.php
// +----------------------------------------------------------------------
namespace Eva\EvaEngine\Logger;

use Phalcon\Logger;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\Multiple as PhalconMultiple;

class Multiple extends PhalconMultiple
{
    protected $_loggers = array();

    public function push(AdapterInterface $logger, $type = Logger::SPECIAL)
    {
        $this->_loggers[$type][] = $logger;
    }

    public function log($type, $message = null, $context = null)
    {
        foreach ($this->_loggers as $handlerType => $loggers) {
            if ($type <= $handlerType) {
                /** @var AdapterInterface $logger */
                foreach ($loggers as $logger) {
                    $logger->log($type, $message, $context);
                }
            }
        }
    }

    public function setFormatter(Logger\FormatterInterface $formatter)
    {
        foreach ($this->_loggers as $handlerType => $loggers) {
            /** @var AdapterInterface $logger */
            foreach ($loggers as $logger) {
                $logger->setFormatter($formatter);
            }
        }
    }
}