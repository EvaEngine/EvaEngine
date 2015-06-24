<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Db;

use Phalcon\Db\Column as BaseColumn;
use Phalcon\Db\Adapter;

/**
 * Class Column
 * @package Eva\EvaEngine\Db
 */
class Column extends BaseColumn
{
    /**
     * @var string
     */
    protected $comment;

    /**
     * @var array
     */
    protected $enumerations;

    /**
     * @var boolean
     */
    protected $isEnum;

    /**
     * @return array
     */
    public function getEnumerations()
    {
        return $this->enumerations;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return bool
     */
    public function isEnum()
    {
        return $this->isEnum;
    }

    public function __construct($name, array $definition)
    {
        parent::__construct($name, $definition);

        if (isset($definition['isEnum'])) {
            $this->isEnum = $definition['isEnum'];
        }

        if (isset($definition['comment'])) {
            $this->comment = $definition['comment'];
        }

        if (isset($definition['enumerations'])) {
            $this->enumerations = $definition['enumerations'];
        }
    }
}
