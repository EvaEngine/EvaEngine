<?php

namespace Eva\EvaEngine\Cache\Backend;

class Memory extends \Phalcon\Cache\Backend
{
    public static $data = array();

    public function get($keyName, $lifetime = null)
    {
        if (!empty(Memory::$data[$keyName])) {
            return Memory::$data[$keyName];
        }
        return null;
    }

    public function exists($keyName = null, $lifetime = null)
    {
        if (!empty(Memory::$data[$keyName])) {
            return true;
        }
        return false;
    }

    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
    {
        Memory::$data[$keyName] = $content;
    }

    public function delete($keyName)
    {
        if (!empty(Memory::$data[$keyName])) {
            unset(Memory::$data[$keyName]);
        }
        return false;
    }

    public function queryKeys($prefix = null)
    {
        return array_keys(Memory::$data);
    }

    public function flush()
    {
        Memory::$data = array();
    }
}
