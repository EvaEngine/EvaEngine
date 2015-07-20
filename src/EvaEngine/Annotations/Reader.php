<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Annotations;

use Phalcon\Annotations\Reader as BaseReader;

class Reader extends BaseReader
{
    private $className;

    private $phalconParseResult;

    private function parseComment($docComment)
    {
        $comment = $this->removeCommentSeparators($docComment);


    }

    private function removeCommentSeparators($docComment)
    {

    }

    public function parse($className)
    {
        $annotations = [];
        $reflection = new \ReflectionClass($className);
        $comment = $reflection->getDocComment();
        /**
         * Read annotations from class
         */
        $classAnnotations = self::parseDocBlock($comment, $reflection->getFileName(), $reflection->getStartLine());
        $annotations["class"] = $classAnnotations;

        /**
         * Get the class properties
         */
        $properties = $reflection->getProperties();
        if (count($properties) > 0) {
            /**
             * Line declaration for properties isn't available
             */
            $line = 1;
            $annotationsProperties = [];
            foreach ($properties as $property) {
                /**
                 * Read comment from method
                 */
                $comment = $property->getDocComment();
                $propertyAnnotations = self::parseDocBlock($comment, $reflection->getFileName(), $line);
                if ($propertyAnnotations) {
                    $annotationsProperties[$property->getName()] = $propertyAnnotations;
                }
                //TODO: use real source file line here
                $line++;
            }

            if (count($annotationsProperties) > 0) {
                $annotations["properties"] = $annotationsProperties;
            }
        }

        /**
         * Get the class methods
         */
        $methods = $reflection->getMethods();
        if (count($methods) > 0) {
            $annotationsMethods = [];
            foreach ($methods as $method) {
                /**
                 * Read comment from method
                 */
                $comment = $method->getDocComment();
                if ($comment) {
                    $methodAnnotations = self::parseDocBlock($comment, $method->getFileName(), $method->getStartLine());
                    if ($methodAnnotations) {
                        $annotationsMethods[$method->getName()] = $methodAnnotations;
                    }
                }
            }

            if (count($annotationsMethods) > 0) {
                $annotations["methods"] = $annotationsMethods;
            }

        }

        return $annotations;
    }

    public static function parseDocBlock($docBlock, $file = null, $line = null)
    {
        p($docBlock);
        $res = parent::parseDocBlock($docBlock, $file, $line);
        return $res;
    }
}
