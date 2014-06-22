<?php

namespace Eva\EvaEngine\Cache\Backend;

class Disable extends \Phalcon\Cache\Backend
{
    public function get($keyName, $lifetime = null)
    {
        return null;
    }

    public function exists($keyName = NULL, $lifetime = NULL)
    {
        return false;
    }

    public function save ($keyName = NULL, $content = NULL, $lifetime = NULL, $stopBuffer = NULL)
    {
    }

    public function delete($keyName)
    {
    }

    public function queryKeys($prefix = null)
    {
    }

    public function flush ()
    {
    }
}
