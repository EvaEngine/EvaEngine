<?php

namespace Eva\EvaEngine\EvaEngineTest\Annotations;

use Eva\EvaEngine\Annotations\Reader;
use Eva\EvaEngine\Annotations\Reflection;

require_once __DIR__ . '/TestClass.php';

class ReflectionTest extends \PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $reflection = new Reflection();

        $classAnnotations = $reflection->getClassAnnotations();
        $this->assertEquals($classAnnotations, null);

        $methodsAnnotations = $reflection->getMethodsAnnotations();
        $this->assertEquals($methodsAnnotations, null);

        $propertiesAnnotations = $reflection->getPropertiesAnnotations();
        $this->assertEquals($propertiesAnnotations, null);
    }

    public function testRealClass()
    {
        $reader = new Reader();
        $reflectData = $reader->parse('TestClass');
        $reflection = new Reflection($reflectData);

        $classAnnotations = $reflection->getClassAnnotations();
        $this->assertNotEmpty($classAnnotations);
        $this->assertInstanceOf('Eva\EvaEngine\Annotations\Collection', $classAnnotations);
        $methodsAnnotations = $reflection->getMethodsAnnotations();
        $this->assertNotEmpty($methodsAnnotations);
        $this->assertArrayHasKey('testMethod1', $methodsAnnotations);
        $this->assertInstanceOf('Eva\EvaEngine\Annotations\Collection', $methodsAnnotations['testMethod1']);
        $propertiesAnnotations = $reflection->getPropertiesAnnotations();
        $this->assertNotEmpty($propertiesAnnotations);
        $this->assertArrayHasKey('testProp1', $propertiesAnnotations);
        $this->assertInstanceOf('Eva\EvaEngine\Annotations\Collection', $propertiesAnnotations['testProp1']);
    }
}
