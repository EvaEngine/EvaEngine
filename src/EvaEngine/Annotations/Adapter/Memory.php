<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Annotations\Adapter;

use Eva\EvaEngine\Annotations\Adapter as BaseAdapter;
use Phalcon\Annotations\AdapterInterface;
use Phalcon\Annotations\Reflection;

class Memory extends BaseAdapter implements AdapterInterface
{
    /**
     * Data
     * @var mixed
     */
    protected $_data;

    /**
     * Reads parsed annotations from memory
     *
     * @param string key
     * @return Reflection
     */
    public function read($key)
    {
        if (empty($data = $this->_data[strtolower($key)])) {
            return $data;
        }
        return false;
    }

    /**
     * Writes parsed annotations to memory
     */
    public function write($key, Reflection $data)
    {
        $lowercasedKey = strtolower($key);
        $this->_data[$lowercasedKey] = $data;
    }
}
