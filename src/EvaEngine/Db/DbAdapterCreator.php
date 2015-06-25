<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/11 下午4:38
// +----------------------------------------------------------------------
// + DbAdapterCreator.php
// +----------------------------------------------------------------------
namespace Eva\EvaEngine\Db;

use Eva\EvaEngine\Foundation\AdapterCreator;
use Eva\EvaEngine\IoC;

class DbAdapterCreator extends AdapterCreator
{


    public function create($adapter, array $options, $eventsManager = null)
    {

        $adapterClass = $this->getAdapterClass($adapter);
        $options['charset'] = isset($options['charset']) && $options['charset'] ? $options['charset'] : 'utf8';
        /** @var \Phalcon\Db\Adapter\Pdo $dbAdapter */
        $dbAdapter = new $adapterClass($options);
        $dbAdapter->setEventsManager($eventsManager ? $eventsManager : eva_get('eventsManager'));

        return $dbAdapter;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdaptersMapping()
    {
        return array(
            'default' => array(
                'mysql' => 'Phalcon\Db\Adapter\Pdo\Mysql',
                'oracle' => 'Phalcon\Db\Adapter\Pdo\Oracle',
                'postgresql' => 'Phalcon\Db\Adapter\Pdo\Postgresql',
                'sqlite' => 'Phalcon\Db\Adapter\Pdo\Sqlite',
            )
        );
    }
}