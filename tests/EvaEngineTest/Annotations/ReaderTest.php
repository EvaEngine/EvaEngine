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
 */
DOC
        );
        $this->assertEmpty($res);


        $res = Reader::parseComment(<<<DOC
/**
 * This is a property string
 * New line *
 *
 * @ foo
 * @foo (bar)
 * @return string
 * @foo bar(test)
 * @Simple
 * @SingleParam("Param")
 * @MultipleParams("First", Second, 1, 1.1, -10, false, true, null)
 * @NamedMultipleParams(first: "First", second: Second)
 */
DOC
        );
        $this->assertEquals(10, count($res));
        $annotation = $res[0];
        $this->assertEquals(Reader::ANNOTATION_TYPE_DESCRIPTION, $annotation['mainType']);
        $this->assertEmpty($annotation['name']);
        $this->assertEmpty($annotation['value']);
        $annotation = $res[1];
        $this->assertEquals(Reader::ANNOTATION_TYPE_DESCRIPTION, $annotation['mainType']);
        $this->assertEmpty($annotation['name']);
        $this->assertEmpty($annotation['value']);
        $annotation = $res[2];
        $this->assertEquals(Reader::ANNOTATION_TYPE_DESCRIPTION, $annotation['mainType']);
        $this->assertEmpty($annotation['name']);
        $this->assertEmpty($annotation['value']);
        $annotation = $res[3];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('foo', $annotation['name']);
        $this->assertEmpty($annotation['value']);
        $annotation = $res[4];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('return', $annotation['name']);
        $this->assertEquals('string', $annotation['value']);
        $annotation = $res[5];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('foo', $annotation['name']);
        $this->assertEquals('bar(test)', $annotation['value']);
        $annotation = $res[9];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('NamedMultipleParams', $annotation['name']);
        $this->assertEmpty($annotation['value']);

        $res = Reader::parseComment(<<<DOC
/**
 中文

 @Simple @SingleParam(
 "Param") @MultipleParams(         "First",
 Second, 1,    1.1
 ,-10,
 false,    true,
 null)
 */
DOC
        );
        $this->assertEquals(4, count($res));
        $annotation = $res[0];
        $this->assertEquals(Reader::ANNOTATION_TYPE_DESCRIPTION, $annotation['mainType']);
        $this->assertEmpty($annotation['name']);
        $this->assertEmpty($annotation['value']);
        $annotation = $res[1];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('Simple', $annotation['name']);
        $this->assertEmpty($annotation['value']);
        $annotation = $res[2];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('SingleParam', $annotation['name']);
        $this->assertEmpty($annotation['value']);

        $res = Reader::parseComment(<<<DOC
/** @Simple a good comment between annotations @SingleParam(
"Param") this is extra content @MultipleParams(         "First",
 Second, 1,    1.1  ,-10,
	 false,    true,
null) more content here */
DOC
        );

        $this->assertEquals(5, count($res));
        $annotation = $res[0];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('Simple', $annotation['name']);
        $annotation = $res[1];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('SingleParam', $annotation['name']);
        $annotation = $res[2];
        $this->assertEquals(Reader::ANNOTATION_TYPE_DESCRIPTION, $annotation['mainType']);
        $annotation = $res[3];
        $this->assertEquals(Reader::ANNOTATION_TYPE_ARGUMENT, $annotation['mainType']);
        $this->assertEquals('MultipleParams', $annotation['name']);
        $annotation = $res[4];
        $this->assertEquals(Reader::ANNOTATION_TYPE_DESCRIPTION, $annotation['mainType']);
    }

    public function testIncompleteDocBlock()
    {

    }

    public function testDocBlock()
    {
        $reader = new Reader();
        $res = $reader->parseDocBlock(<<<DOC
/**
 * This is a property string
 * New line *
 *
 * @ foo
 * @foo (bar)
 * @return string
 * @foo bar(test)
 * @Simple
 * @SingleParam("Param")
 * @MultipleParams("First", Second, 1, 1.1, -10, false, true, null)
 * @NamedMultipleParams(first: "First", second: Second)
 */
DOC
        );
        $this->assertEquals(10, count($res));
        $this->assertNotEmpty($res[9]);
    }
}
