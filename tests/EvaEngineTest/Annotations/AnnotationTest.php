<?php

namespace Eva\EvaEngine\EvaEngineTest\Annotations;


use Eva\EvaEngine\Annotations\Annotation;

class AnnotationTest extends \PHPUnit_Framework_TestCase
{
    public function testType()
    {
        //TODO: help phalcon fix no name input `Undefined index: name`
        $annotation = new Annotation([
            'name' => 'foo',
            'mainType' => Annotation::TYPE_DESCRIPTION,
            'value' => 'bar',
        ]);
        $this->assertEquals(Annotation::TYPE_DESCRIPTION, $annotation->getType());
        $this->assertEquals('bar', $annotation->getValue());
        $this->assertTrue($annotation->isDescription());
    }
}
