<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/11 下午4:53
// +----------------------------------------------------------------------
// + DbListener.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Listeners;

use Eva\EvaEngine\IoC;
use Phalcon\Logger;

class DbQueryLoggingListener
{
    public function beforeQuery($event, $dbAdapter)
    {
        $config = IoC::get('config');
        // 数据查询日志记录仅在 debug 模式下有效
        if (!$config->debug) {
            return;
        }
        $sqlVariables = $dbAdapter->getSQLVariables();
        $logger = IoC::get('dbQueryLogger');
        if (count($sqlVariables)) {
            $query = str_replace(array('%', '?'), array('%%', "'%s'"), $dbAdapter->getSQLStatement());
            $query = vsprintf($query, $sqlVariables);
            //
            $logger->log($query, Logger::INFO);
        } else {
            $logger->log($dbAdapter->getSQLStatement(), Logger::INFO);
        }
    }
}