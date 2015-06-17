<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/5/25 下午5:30
// +----------------------------------------------------------------------
// + DispatchListener.php
// +----------------------------------------------------------------------
namespace Eva\EvaEngine\Listeners;

class DispatchListener
{
    public function beforeException($event, $dispatcher, $exception)
    {
        //For fixing phalcon weird behavior https://github.com/phalcon/cphalcon/issues/2558
        throw $exception;
    }
}