<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Utils;

use Phalcon\Text;

class PhpStream
{
    protected $index = 0;
    protected $length = 0;
    protected $data = '';

    public function __construct()
    {
        if (file_exists($this->bufferFilename())) {
            $this->data = file_get_contents($this->bufferFilename());
        } else {
            $this->data = '';
        }

        $this->index = 0;
        $this->length = strlen($this->data);
    }

    protected function bufferFilename()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_input.txt';
    }

    public function streamOpen($path, $mode, $options, &$opened_path)
    {
        return true;
    }

    public function streamClose()
    {
    }

    public function streamStat()
    {
        return [];
    }

    public function streamFlush()
    {
        return true;
    }

    public function streamRead($count)
    {
        if (null === $this->length) {
            $this->length = strlen($this->data);
        }

        $length = min($count, $this->length - $this->index);
        $data = substr($this->data, $this->index);
        $this->index = $this->index + $length;

        return $data;
    }

    public function streamEof()
    {
        return ($this->index >= $this->length ? true : false);
    }

    public function streamWrite($data)
    {
        return file_put_contents($this->bufferFilename(), $data);
    }

    public function unlink()
    {
        if (file_exists($this->bufferFilename())) {
            unlink($this->bufferFilename());
        }

        $this->data = '';
        $this->index = 0;
        $this->length = 0;
    }

    public function __call($name, $arguments)
    {
        $method = Text::camelize($name);
        return call_user_func_array([$this, $method], $arguments);
    }
}
