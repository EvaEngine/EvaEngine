<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Module;

/**
 * Interface StandardInterface
 * @package Eva\EvaEngine\Module
 */
interface StandardInterface
{
    /**
     * @return void
     */
    public static function registerGlobalAutoloaders();

    /**
     * @return void
     */
    public static function registerGlobalEventListeners();

    /**
     * @return void
     */
    public static function registerGlobalViewHelpers();

    /**
     * @return void
     */
    public static function registerGlobalRelations();
}
