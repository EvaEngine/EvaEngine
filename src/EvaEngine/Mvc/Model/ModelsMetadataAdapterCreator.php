<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/12 下午2:54
// +----------------------------------------------------------------------
// + ModelsMetadataAdapterCreator.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Mvc\Model;


use Eva\EvaEngine\Foundation\AdapterCreator;

class ModelsMetadataAdapterCreator extends AdapterCreator
{

    /**
     * {@inheritdoc}
     */
    protected function getAdaptersMapping()
    {
        return array(
            'default' => array(
                'apc' => 'Phalcon\Mvc\Model\MetaData\Apc',
                'files' => 'Phalcon\Mvc\Model\MetaData\Files',
                'memory' => 'Phalcon\Mvc\Model\MetaData\Memory',
                'xcache' => 'Phalcon\Mvc\Model\MetaData\Xcache',
                'memcache' => 'Phalcon\Mvc\Model\MetaData\Memcache',
                'redis' => 'Phalcon\Mvc\Model\MetaData\Redis',
                'wincache' => 'Phalcon\Mvc\Model\MetaData\Wincache',
            )
        );
    }

    public function create($adapter, array $options = array())
    {
        $adapterClass = $this->getAdapterClass($adapter);

        return new $adapterClass($options);
    }
}