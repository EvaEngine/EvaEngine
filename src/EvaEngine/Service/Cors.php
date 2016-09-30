<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Service;


use Phalcon\DI\InjectionAwareInterface;

class Cors implements InjectionAwareInterface
{

    protected $_di;

    protected $config;
    
    public function __construct($config)
    {
        $this->setConfig($config);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setDI($di)
    {
        $this->_di = $di;
    }

    public function getDI()
    {
        return $this->_di;
    }

    public function simpleRequests()
    {
        if ($this->ifHttpOriginIsInTheWhiteList()) {
            $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
        }
    }

    public function preflightedRequests(
        $allowCredentials = 'true',
        $allowMethods = 'GET, POST, PUT, DELETE, OPTIONS',
        $allowHeaders = 'Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma,'
        . 'Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With, Content-Type'
    )
    {
        if ($this->ifHttpOriginIsInTheWhiteList()) {
            $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Credentials', (string)$allowCredentials);
            $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
            $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Methods', $allowMethods);
            $this->getDI()->getResponse()->setHeader('Access-Control-Allow-Headers', $allowHeaders);
            if (strtoupper($this->request->getMethod()) == 'OPTIONS') {
                $this->response->send();
                exit();
            }
        }
    }

    protected function ifHttpOriginIsInTheWhiteList()
    {

        $checked = false;
        foreach ($this->config as $domain) {
            if (ends_with($_SERVER['HTTP_ORIGIN'], $domain['domain'])) {
                $checked = true;
            }
        }
        return $checked;
    }

}