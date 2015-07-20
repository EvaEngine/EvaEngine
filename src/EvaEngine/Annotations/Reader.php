<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Annotations;

use Phalcon\Annotations\Reader as BaseReader;
use Phalcon\Text;

class Reader extends BaseReader
{
    private $className;

    private $phalconParseResult;

    private static $docStack = [];

    private static function pushToDocStack($type, array $strArray)
    {
        self::$docStack[] = [
            'type' => $type,
            'string' => implode('', $strArray),
        ];
    }

    public static function parseComment($docComment)
    {
        $docComment = self::removeCommentSeparators($docComment);
        $letters = preg_split('//u', $docComment, null, PREG_SPLIT_NO_EMPTY);
        $stack = [];
        $stackType = 'description';
        $stackDeepth = 0;
        $docLength = count($letters) - 1;

        foreach ($letters as $i => $letter) {
            if (!$stack && $letter === '@') {
                $stackType = 'argument';
            }

            if ($stackType === 'argument' && $letter === '(') {
                $stackDeepth++;
            }

            if ($stackType === 'argument' && $letter === ')') {
                $stackDeepth--;
            }

            $stack[] = $letter;
            $nextLetter = isset($letters[$i + 1]) ? $letters[$i + 1] : null;

            if (($stackType === "description" && $letter === "\n")
                || ($stackType === 'description' && $nextLetter === '@')
                || ($stackType === 'argument' && $stackDeepth === 0 && $letter === "\n")
                || ($stackType === 'argument' && $stackDeepth === 0 && $nextLetter === "@")
                || $i === $docLength
            ) {
                self::pushToDocStack($stackType, $stack);
                //Reset stack
                $stack = [];
                $stackType = 'description';
                $stackDeepth = 0;
            }
        }

        return self::$docStack;
    }

    public static function removeCommentSeparators($docComment)
    {
        if (!Text::startsWith($docComment, '/**') || !Text::endsWith($docComment, '*/')) {
            return '';
        }
        $docComment = substr($docComment, 3, -2);
        $lines = explode("\n", $docComment);
        foreach ($lines as $key => $line) {
            $line = trim($line);
            if (!$line) {
                unset($lines[$key]);
                continue;
            }
            $lines[$key] = ltrim($line, '* ');
        }
        return implode("\n", $lines);
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
        $res = parent::parseDocBlock($docBlock, $file, $line);
        return $res;
    }
}
