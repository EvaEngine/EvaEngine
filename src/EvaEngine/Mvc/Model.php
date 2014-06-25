<?php

namespace Eva\EvaEngine\Mvc;

use Phalcon\Mvc\Model\Resultset\Simple as SimpleResultSet;
use Eva\EvaEngine\Mvc\Model\Manager as ModelManager;


class Model extends \Phalcon\Mvc\Model
{
    protected $prefix;

    protected $tableName;

    protected $useMasterSlave = true;

    protected $modelForm;

    public static $injectRelations;

    public function setModelForm($form)
    {
        $this->modelForm = $form;
        return $this;
    }

    public function getModelForm()
    {
        return $this->modelForm;
    }

    public function setPrefix($tablePrefix)
    {
        $this->prefix = $tablePrefix;
        return $htis;
    }

    public function getPrefix()
    {
        if($this->prefix) {
            return $this->prefix;
        }
        return $this->prefix = ModelManager::getDefaultPrefix();
    }

    public function getSource()
    {
        return $this->getPrefix() . $this->tableName;
    }

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
                            if(method_exists($child, 'dump')) {
                                $subdatas[] = $child->dump($subdata);
                            }
                        }
                        $data[$key] = $subdatas;
                    } elseif(method_exists($this->$key, 'dump')) {
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

    public function loadRelations()
    {
        $relations = $this->getDI()->getModuleManager()->getInjectRelations($this);
        if(!$relations) {
            return $this;
        }
        foreach($relations as $relation) {
            $relationType = $relation['relationType'];
            call_user_func_array(array($this, $relationType), $relation['parameters']);
        }
        return $this;
    }

    public function initialize()
    {
        if (true === $this->useMasterSlave) {
            $this->setWriteConnectionService('dbMaster');
            $this->setReadConnectionService('dbSlave');
        }

        $this->loadRelations();
    }
}
