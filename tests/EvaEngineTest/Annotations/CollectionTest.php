<?php

namespace Eva\EvaEngine\EvaEngineTest\Annotations;

use Eva\EvaEngine\Annotations\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCount()
    {
        $collection = new Collection([
            [
                'name' => 'foo'
            ],
            [
                'name' => 'bar'
            ]
        ]);
        $this->assertCount(2, $collection);
    }
}
