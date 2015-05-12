<?php

namespace Eva\EvaEngine\Tasks;

// +----------------------------------------------------------------------
// | [phalcon]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-7-17 15:21
// +----------------------------------------------------------------------
// + MainTask.php
// +----------------------------------------------------------------------

class MainTask extends TaskBase
{
    public function mainAction()
    {
        $output = $this->output;
        $output->writeln("");
        $output->writelnComment('usage:');
        $output->writeln('  ./console.php appName moduleName taskName[:actionName] [param1 ... paramN]     ');
    }

    public function listAction($moduleName)
    {
        $this->output->writelnError("------------------------------");
        var_dump(func_get_args());
        $this->output->writelnError("------------------------------");

    }

    public function helpAction($params)
    {
        $this->output->writelnComment('你正在查找' . $params[0] . '的帮助');
    }
}
