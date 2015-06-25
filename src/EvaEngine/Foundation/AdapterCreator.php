<?php
// +----------------------------------------------------------------------
// | EvaEngine
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/11 下午5:20
// +----------------------------------------------------------------------
// + AdapterCreator.php
// +----------------------------------------------------------------------
namespace Eva\EvaEngine\Foundation;

use Eva\EvaEngine\Exception\RuntimeException;

/**
 * 适配器创建器抽象类
 * @package Eva\EvaEngine\Foundation
 */
abstract class AdapterCreator
{
    /**
     * 获取 adapter 和类的映射关系
     * 参考：
     * ```php
     *  protected function getAdaptersMapping()
     *  {
     *      return array(
     *          'default' => array(
     *              'adapter1' => 'Some\Adapter\XXX1',
     *              'adapter2' => 'Some\Adapter\XXX2'
     *          )
     *      );
     *  }
     * ```
     * @return array
     */
    abstract protected function getAdaptersMapping();

    /**
     * @param string $adapter 适配器名称，可以为完整的类名
     * @param string $category 分类，可选
     * @return string 适配器类名
     * @throws RuntimeException
     */
    protected function getAdapterClass($adapter, $category = 'default')
    {
        $adaptersMapping = $this->getAdaptersMapping();
        $adapterClass = $adapter;
        $adapterName = strtolower($adapter);
        if (isset($adaptersMapping[$category]) && isset($adaptersMapping[$category][$adapterName])) {
            $adapterClass = $adaptersMapping[$category][$adapterName];
        }

        if (!class_exists($adapterClass)) {
            throw new RuntimeException(
                sprintf(
                    'Adapter class "%s" not found, adapter name: "%s"',
                    $adapterClass,
                    $adapter
                )
            );
        }

        return $adapterClass;
    }
}