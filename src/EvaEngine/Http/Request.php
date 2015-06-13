<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Http;

class Request extends \Phalcon\Http\Request
{
    /**
     * @var string
     */
    protected $_rawBody;

    /**
     * @var array
     */
    protected $_putCache;

    /**
     * @param $rawBody
     * @return $this
     */
    public function setRawBody($rawBody)
    {
        $this->_rawBody = $rawBody;
        return $this;
    }

    public function setPutCache(array $putCache)
    {
        $this->_putCache = $putCache;
        return $this;
    }
}
