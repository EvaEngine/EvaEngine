<?php
// +----------------------------------------------------------------------
// | wallstreetcn
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/17 下午6:54
// +----------------------------------------------------------------------
// + ApplicationInterface.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Foundation;


use Phalcon\DI\InjectionAwareInterface;

interface ApplicationInterface extends InjectionAwareInterface
{
    /**
     * @return $this
     */
    public function initializeErrorHandler();


    public function getName();

    /**
     * @return $this
     */
    public function initialize();

    public function fire();
}