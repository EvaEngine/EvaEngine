<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Mvc\Model;

use Phalcon\Mvc\Model\Manager as ModelManager;

/**
 * EvaEngine Model Manager, to inject master / slave db connections
 * @package Eva\EvaEngine\Mvc\Model
 */
class Manager extends ModelManager
{
    /**
     * @var string
     */
    protected static $defaultPrefix = 'eva_';

    /**
     * @param $tablePrefix
     */
    public static function setDefaultPrefix($tablePrefix)
    {
        self::$defaultPrefix = $tablePrefix;
    }

    /**
     * @return string
     */
    public static function getDefaultPrefix()
    {
        return self::$defaultPrefix;
    }

    /**
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Db\AdapterInterface
     */
    public function getReadConnection($model)
    {
        if ($this->getDI()->offsetExists($model->getReadConnectionService())) {
            $this->setReadConnectionService($model, $model->getReadConnectionService());
        } elseif ($this->getDI()->getDbSlave()) {
            $this->setReadConnectionService($model, 'dbSlave');
        }


        return parent::getReadConnection($model);
    }

    /**
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Db\AdapterInterface
     */
    public function getWriteConnection($model)
    {
        if ($this->getDI()->offsetExists($model->getWriteConnectionService())) {
            $this->setWriteConnectionService($model, $model->getWriteConnectionService());
        } elseif ($this->getDI()->getDbMaster()) {
            $this->setWriteConnectionService($model, 'dbMaster');
        }

        return parent::getWriteConnection($model);
    }
}
