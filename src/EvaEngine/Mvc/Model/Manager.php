<?php

namespace Eva\EvaEngine\Mvc\Model;

class Manager extends \Phalcon\Mvc\Model\Manager
{
    protected static $defaultPrefix = 'eva_';

    public static function setDefaultPrefix($tablePrefix)
    {
        self::$defaultPrefix = $tablePrefix;
    }

    public static function getDefaultPrefix()
    {
        return self::$defaultPrefix;
    }


    public function getReadConnection($model)
    {
        if ($this->getDI()->get('dbSlave')) {
            $this->setReadConnectionService($model, 'dbSlave');
        }

        return parent::getReadConnection($model);
    }

    public function getWriteConnection($model)
    {
        if ($this->getDI()->get('dbMaster')) {
            $this->setWriteConnectionService($model, 'dbMaster');
        }

        return parent::getWriteConnection($model);
    }
}
