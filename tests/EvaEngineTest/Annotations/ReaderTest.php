<?php

namespace Eva\EvaEngine\EvaEngineTest\Annotations;

use Eva\EvaEngine\Annotations\Reader;
use Phalcon\Http\Request;

class ReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testRemoveEmpty()
    {
        $res = Reader::removeCommentSeparators(<<<DOC
/*
 * Description
 */
DOC
        );

        $this->assertEquals('', $res);
    }

    public function testRemoveNormal()
    {
        $res = Reader::removeCommentSeparators(<<<DOC
/**
 * Description
 */
DOC
        );
        $this->assertEquals('Description', $res);

        $res = Reader::removeCommentSeparators(<<<DOC
/**
 * * Description *
 *   Description2
 */
DOC
        );
        $this->assertEquals("Description *\nDescription2", $res);

        $res = Reader::removeCommentSeparators(<<<DOC
/**
 * Description

 * After blank line
   No star line
 */
DOC
        );
        $this->assertEquals("Description\nAfter blank line\nNo star line", $res);

    }

    public function testDocCommentParse()
    {
        $res = Reader::parseComment(<<<DOC
/**
 * This is a property string
 *
 * @return string
 * @Simple
 * @SingleParam("Param")
 * @MultipleParams("First", Second, 1, 1.1, -10, false, true, null)
 * @NamedMultipleParams(first: "First", second: Second)
 */
DOC
        );
        $this->assertEquals(6, count($res));

    }
}
