<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\EvaEngineTest;

use Eva\EvaEngine\Utils\PhpStream;

class PhpStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testPhpStream()
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', PhpStream::class);

        file_put_contents('php://input', [json_encode(['bar' => 'baz'])]);


        $data = file_get_contents('php://input');
        $this->assertEquals($data, json_encode(['bar' => 'baz']));

        $_POST['foo'] = 'bar';
        $this->assertNotEquals($_POST, $data);
    }
}
