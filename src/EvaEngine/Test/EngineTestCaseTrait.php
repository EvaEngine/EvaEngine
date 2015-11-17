<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Test;

use Eva\EvaEngine\Engine;
use Exception;

/**
 * Class TestCase
 * @package Eva\EvaEngine\Test
 */
trait EngineTestCaseTrait
{
    /**
     * @var Engine
     */
    protected $engine;

    public function setUp()
    {
        $this->initEngine();
    }

    protected function initEngine()
    {
        $engine = new Engine(getenv('APPLICATION_ROOT'), getenv('APPLICATION_NAME'));
        $engine
            ->loadModules(include getenv('APPLICATION_ROOT') . '/config/modules.' . getenv('APPLICATION_NAME') . '.php')
            ->bootstrap();
        $this->engine = $engine;
    }


    /**
     * @param callable $test
     * @param string|null $expectedExceptionClass
     * @param int|string|null $expectedCode
     * @param string|null $expectedMessage
     */
    public static function assertException(
        callable $test,
        $expectedExceptionClass = Exception::class,
        $expectedCode = null,
        $expectedMessage = null
    ) {
        $expectedExceptionClass = self::fixExceptionClass($expectedExceptionClass);
        try {
            $test();
        } catch (Exception $exception) {
            self::checkExceptionInstanceOf($exception, $expectedExceptionClass);
            self::checkExceptionCode($exception, $expectedCode);
            self::checkExceptionMessage($exception, $expectedMessage);
            return;
        }
        self::failAssertingException($expectedExceptionClass);
    }

    /**
     * @param string $exceptionClass
     * @return string
     */
    private static function fixExceptionClass($exceptionClass)
    {
        if ($exceptionClass === null) {
            $exceptionClass = Exception::class;
        } elseif (!is_string($exceptionClass)) {
            throw new \InvalidArgumentException(
                'Expected exception class must be string or null, %s given.',
                gettype($exceptionClass)
            );
        } else {
            try {
                $reflection = new \ReflectionClass($exceptionClass);
            } catch (\ReflectionException $e) {
                \PHPUnit_Framework_TestCase::fail(sprintf(
                    'An exception of type "%s" does not exist.',
                    $exceptionClass
                ));
            }
            $exceptionClass = $reflection->getName();
            if (!$reflection->isInterface() && $exceptionClass !== Exception::class
                && !$reflection->isSubclassOf(Exception::class)
            ) {
                \PHPUnit_Framework_TestCase::fail(sprintf('A class "%s" is not an Exception.', $exceptionClass));
            }
        }
        return $exceptionClass;
    }

    /**
     * @param \Exception $exception
     * @param string $expectedExceptionClass
     */
    private static function checkExceptionInstanceOf(Exception $exception, $expectedExceptionClass)
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $details = '';
        if ($message !== '' && $code !== 0) {
            $details = sprintf(' (code was %s, message was "%s")', $code, $message);
            // code might be string also, e.g. in PDOException
        } elseif ($message !== '') {
            $details = sprintf(' (message was "%s")', $message);
        } elseif ($code !== 0) {
            $details = sprintf(' (code was %s)', $code);
        }
        $errorMessage = sprintf('Failed asserting the class of an exception%s.', $details);
        \PHPUnit_Framework_TestCase::assertInstanceOf($expectedExceptionClass, $exception, $errorMessage);
    }

    /**
     * @param \Exception $exception
     * @param int|string|null $expectedCode
     */
    private static function checkExceptionCode(Exception $exception, $expectedCode)
    {
        if ($expectedCode !== null) {
            \PHPUnit_Framework_TestCase::assertEquals(
                $expectedCode,
                $exception->getCode(),
                sprintf('Failed asserting the code of thrown %s.', get_class($exception))
            );
        }
    }

    /**
     * @param \Exception $exception
     * @param string|null $expectedMessage
     */
    private static function checkExceptionMessage(Exception $exception, $expectedMessage)
    {
        if ($expectedMessage !== null) {
            \PHPUnit_Framework_TestCase::assertContains(
                $expectedMessage,
                $exception->getMessage(),
                sprintf('Failed asserting the message of thrown %s.', get_class($exception))
            );
        }
    }

    /**
     * @param string $expectedExceptionClass
     */
    private static function failAssertingException($expectedExceptionClass)
    {
        $details = '';
        if ($expectedExceptionClass !== Exception::class) {
            $details = sprintf(' of type %s', $expectedExceptionClass);
        }
        $errorMessage = sprintf('Failed asserting that Exception%s was thrown.', $details);
        \PHPUnit_Framework_TestCase::fail($errorMessage);
    }

    protected function tearDown()
    {
        $di = $this->engine->getDI();
        $di::reset();
        parent::tearDown();
    }
}
