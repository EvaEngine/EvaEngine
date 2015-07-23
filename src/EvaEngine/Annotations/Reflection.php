<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Annotations;

use Phalcon\Annotations\Reflection as BaseReflection;

class Reflection extends BaseReflection
{
    public function __construct(array $reflectionData = null)
    {
        $this->_reflectionData = $reflectionData;
    }

    /**
     * Returns the annotations found in the class docblock
     */
    public function getClassAnnotations()
    {
        if ($this->_classAnnotations) {
            return $this->_classAnnotations;
        }
        if (!empty($this->_reflectionData['class'])) {
            return $this->_classAnnotations = new Collection($this->_reflectionData['class']);
        }
        return false;
    }

    /**
     * Returns the annotations found in the methods' docblocks
     */
    public function getMethodsAnnotations()
    {
        if ($this->_methodAnnotations) {
            return $this->_methodAnnotations;
        }

        if (!empty($this->_reflectionData['methods'])) {
            $methods = $this->_reflectionData['methods'];
            $collections = [];
            foreach ($methods as $key => $method) {
                $collections[$key] = new Collection($method);
            }
            return $this->_methodAnnotations = $collections;
        }
        return false;
    }

    /**
     * Returns the annotations found in the properties' docblocks
     */
    public function getPropertiesAnnotations()
    {
        if ($this->_propertyAnnotations) {
            return $this->_propertyAnnotations;
        }

        if (!empty($this->_reflectionData['properties'])) {
            $collections = [];
            $properties = $this->_reflectionData['properties'];
            foreach ($properties as $key => $property) {
                $collections[$key] = new Collection($property);
            }
            return $this->_propertyAnnotations = $collections;
        }
        return false;
    }
}
