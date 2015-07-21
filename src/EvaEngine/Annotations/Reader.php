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
    private static $docStack = [];

    const ANNOTATION_TYPE_DESCRIPTION = 'description';

    const ANNOTATION_TYPE_ARGUMENT = 'argument';

    private static function pushToDocStack($type, array $strArray)
    {
        $annotationString = implode('', $strArray);
        //Blank or \n
        if (!trim($annotationString)) {
            return self::$docStack;
        }

        $argumentName = '';
        $argumentString = '';
        $argumentValue = '';
        if ($annotationString && $type === self::ANNOTATION_TYPE_ARGUMENT) {
            $argumentString = ltrim($annotationString, '@');
            $argument = explode('(', $argumentString);

            if (count($argument) <= 1) {
                //Annotation format as @foo bar => name:foo value: bar
                $argument = explode(' ', $argument[0]);
                $argumentName = array_shift($argument);
                $argumentValue = implode(' ', $argument);
            } else {
                if (false !== strpos(trim($argument[0]), ' ')) {
                    //Annotation format as @foo bar(test) => name:foo value: bar(test)
                    $argument = explode(' ', $argumentString);

                    $argumentName = array_shift($argument);
                    $argumentValue = implode(' ', $argument);
                } else {
                    //Annotation format as @foo(bar) => name:foo value:null
                    //Annotation format as @foo (test) => name:foo value:null
                    $argumentName = trim($argument[0]);
                    $annotationString = implode('(', $argument);
                }
            }
        }

        self::$docStack[] = [
            'mainType' => $type,
            'name' => $argumentName,
            'value' => trim($argumentValue),
            'rawString' => $annotationString,
            //Phalcon default fields: name / type / file / line / arguments(optional)
            'type' => null,
            'arguments' => null,
            'file' => null,
            'line' => null,
        ];
    }

    public static function parseComment($docComment)
    {
        self::$docStack = [];
        $docComment = self::removeCommentSeparators($docComment);
        if (!$docComment) {
            return [];
        }
        //TODO:: UTF8 support
        //$letters = preg_split('//u', $docComment, null, PREG_SPLIT_NO_EMPTY);
        $letters = str_split($docComment);
        $stack = [];
        $stackType = self::ANNOTATION_TYPE_DESCRIPTION;
        $stackDeepth = 0;
        $docLength = count($letters) - 1;

        foreach ($letters as $i => $letter) {
            $nextLetter = isset($letters[$i + 1]) ? $letters[$i + 1] : null;

            if (!$stack && $letter === '@' && $nextLetter !== ' ') {
                //Annotation `@ foo bar(test)` will consider as description
                $stackType = self::ANNOTATION_TYPE_ARGUMENT;
            }

            if ($stackType === self::ANNOTATION_TYPE_ARGUMENT && $letter === '(') {
                $stackDeepth++;
            }
            if ($stackType === self::ANNOTATION_TYPE_ARGUMENT && $letter === ')') {
                $stackDeepth--;
            }

            $stack[] = $letter;

            //For DEBUG:
            //echo sprintf("letter:%s, type:%s, nextLetter:%s, deep: %s\n",
            // $letter, $stackType, $nextLetter, $stackDeepth);

            if ((
                    $letter === "\n" && $stackType === self::ANNOTATION_TYPE_DESCRIPTION
                ) || (
                    $nextLetter === '@' && $stackType === self::ANNOTATION_TYPE_DESCRIPTION
                ) ||
                //check last stack
                $i === $docLength
                || (
                    //for case: `@foo() some test @bar()`
                    $nextLetter === "@" && $stackDeepth === 0 &&
                    $stackType === self::ANNOTATION_TYPE_ARGUMENT
                ) || (
                    //for case: `@foo() some text`
                    //for case: `@foo some text\n`
                    true === in_array($letter, ["\n", ')']) && $stackDeepth === 0 &&
                    $stackType === self::ANNOTATION_TYPE_ARGUMENT && $stackDeepth === 0
                )
            ) {
                self::pushToDocStack($stackType, $stack);
                //Reset stack
                $stack = [];
                $stackType = self::ANNOTATION_TYPE_DESCRIPTION;
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
        $baseParseResults = parent::parseDocBlock($docBlock, $file, $line);
        $advanceParseResults = self::parseComment($docBlock);
        $argumentIndex = 0;
        foreach ($advanceParseResults as $key => $res) {
            if ($res['mainType'] !== self::ANNOTATION_TYPE_ARGUMENT) {
                continue;
            }
            if (isset($baseParseResults[$argumentIndex]['name']) &&
                $baseParseResults[$argumentIndex]['name'] == $res['name']
            ) {
                $advanceParseResults[$key] = array_merge($res, $baseParseResults[$argumentIndex]);
            }
            $argumentIndex++;
        }
        return $advanceParseResults;
    }
}
