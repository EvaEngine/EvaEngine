<?php

namespace Eva\EvaEngine\Cache\Backend;

class Disable extends \Phalcon\Cache\Backend
{
    public function get($keyName, $lifetime = null)
    {
        return null;
    }

    public function exists($keyName = null, $lifetime = null)
    {
        return false;
    }

    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
    {
    }

    public function delete($keyName)
    {
    }

    public function queryKeys($prefix = null)
    {
    }

    public function flush()
    {
    }
}
