<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Annotations;

use Phalcon\Annotations\Adapter as BaseAdapter;

class Adapter extends BaseAdapter
{
    /**
     * Parses or retrieves all the annotations found in a class
     *
     * @param object|string $className
     * @return Reflection
     */
    public function get($className)
    {
        /**
         * Get the class name if it's an object
         */
        if (gettype($className) === 'object') {
            $realClassName = get_class($className);
        } else {
            $realClassName = $className;

        }

        $annotations = $this->_annotations;
        if (isset($annotations[$realClassName])) {
            return $annotations[$realClassName];
        }

        /**
         * Try to read the annotations from the adapter
         */
        $classAnnotations = $this->read($realClassName);

        if (!$classAnnotations) {
            /**
             * Get the annotations reader
             */
            $reader = $this->getReader();
            $parsedAnnotations = $reader->parse($realClassName);

            /**
             * If the reader returns a
             */
            if (is_array($parsedAnnotations)) {
                $classAnnotations = new Reflection($parsedAnnotations);
                $this->_annotations[$realClassName] = $classAnnotations;
                $this->write($realClassName, $classAnnotations);
            }
        }

        return $classAnnotations;
    }
}
