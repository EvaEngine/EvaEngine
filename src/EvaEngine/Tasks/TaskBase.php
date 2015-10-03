<?php

namespace Eva\EvaEngine\Tasks;

// +----------------------------------------------------------------------
// | [phalcon]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-7-17 15:22
// +----------------------------------------------------------------------
// + TaskBase.php
// +----------------------------------------------------------------------

use Eva\EvaEngine\CLI\Output\ConsoleOutput;
use Phalcon\CLI\Task;

class TaskBase extends Task
{
    /**
     * @var ConsoleOutput
     */
    protected $_output;

    public function __get($name) {

        //Phalcon 2.0 make __construct as final method, here use magic method to compatible
        if ($name === 'output') {
            return $this->_output ?: $this->_output = new ConsoleOutput();
        }
    }

}
