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
        $output->writelnComment('用法:');
        $output->writeln('  console.php {Module}:{task名} [{action名} [参数1 ... 参数N]]     ');
//        $output->writeln("");
//        $output->writelnComment('默认可用命令:');
//        $output->writeList(array(
//            'list' => '不带参数时列出所有注册了命令的模块,当跟上模块名时,列出该模块下所有命令的详细说明',
//            'help' => 'help 模块名:taskName [命令] 可用查看task中所有或者单个命令的说明'
//        ));
//        $output->writelnComment('以下模块注册了命令:     ');
//        $output->writeList(array(
//            'test' => '12345',
//            'composer' => '123456',
//            'composer1234' => '123456',
//            'composer45678' => '123456',
//            'composer34' => '123456',
//            'composer2' => '123456',
//        ));
    }
    public function listAction($moduleName)
    {
        $this->output->writelnError("------------------------------");
        var_dump(func_get_args());
        $this->output->writelnError("------------------------------");

    }
    public function helpAction($params)
    {
        $this->output->writelnComment('你正在查找'.$params[0] .'的帮助');
    }
} 