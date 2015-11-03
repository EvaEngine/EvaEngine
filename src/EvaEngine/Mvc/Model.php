<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Eva\EvaEngine\Mvc;

use Eva\EvaEngine\Exception\DatabaseWriteException;
use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultSet;
use Eva\EvaEngine\Mvc\Model\Manager as ModelManager;
use Phalcon\Mvc\Model as PhalconModel;
use Eva\EvaEngine\Form;

/**
 * EvaEngine Base Model
 * - Support master / slave db
 * - Support db table prefix
 * - Support inject ORM relationships
 * @package Eva\EvaEngine\Mvc
 */
class Model extends PhalconModel
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var bool
     */
    protected $useMasterSlave = true;

    /**
     * @var Form
     */
    protected $modelForm;

    /**
     * @var array
     */
    public static $injectRelations;

    /**
     * @param Form $form
     * @return $this
     */
    public function setModelForm(Form $form)
    {
        $this->modelForm = $form;
        return $this;
    }

    /**
     * @return Form
     */
    public function getModelForm()
    {
        return $this->modelForm;
    }

    /**
     * @param $tablePrefix
     * @return $this
     */
    public function setPrefix($tablePrefix)
    {
        $this->prefix = $tablePrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        if ($this->prefix) {
            return $this->prefix;
        }
        return $this->prefix = ModelManager::getDefaultPrefix();
    }

    /**
     * Get db table full name
     * @return string
     */
    public function getSource()
    {
        if (!$this->tableName) {
            $this->tableName = parent::getSource();
        }
        return $this->getPrefix() . $this->tableName;
    }

    /**
     * Dump model entity data as an array
     * @param array $dataStructure
     * @return array|null
     */
    public function dump(array $dataStructure = null)
    {
        $data = null;
        if (!$dataStructure) {
            return $data;
        }
        foreach ($dataStructure as $key => $subdata) {
            if (is_numeric($key)) {
                $data[$subdata] = $this->$subdata;
            } elseif (is_array($subdata)) {
                if (!empty($this->$key)) {
                    if ($this->$key instanceof SimpleResultSet || is_array($this->$key)) {
                        $subdatas = array();
                        foreach ($this->$key as $child) {
                            if (method_exists($child, 'dump')) {
                                $subdatas[] = $child->dump($subdata);
                            }
                        }
                        $data[$key] = $subdatas;
                    } elseif (method_exists($this->$key, 'dump')) {
                        $data[$key] = $this->$key->dump($subdata);
                    } else {
                        $data[$key] = null;
                    }
                } else {
                    $data[$key] = null;
                }

            } elseif (is_string($subdata)) {
                $data[$key] = $this->$subdata();
            }
        }

        return $data;
    }

    /**
     * @return $this
     */
    public function loadRelations()
    {
        $relations = $this->getDI()->getModuleManager()->getInjectRelations($this);
        if (!$relations) {
            return $this;
        }
        foreach ($relations as $relation) {
            $relationType = $relation['relationType'];
            call_user_func_array(array($this, $relationType), $relation['parameters']);
        }
        return $this;
    }

    /**
     * A wrapper for Phalcon save method, a DatabaseWriteException will be thrown
     * @return bool
     * @throws DatabaseWriteException
     */
    public function eSave()
    {
        if (!$this->save()) {
            throw new DatabaseWriteException(
                sprintf('Save failed for class %s', get_class($this)),
                $this->getMessages()
            );
        }
        return true;
    }

    /**
     * Open master / slave mode
     * Load ORM relationship injection
     */
    public function initialize()
    {
        if (true === $this->useMasterSlave) {
            $this->setWriteConnectionService('dbMaster');
            $this->setReadConnectionService('dbSlave');
        }

        $this->loadRelations();
    }
}
