<?php

namespace Eva\EvaEngine\Mvc;

use Eva\EvaEngine\Exception;

class Url extends \Phalcon\Mvc\Url
{
    protected $version;

    protected $versionFile;

    public function getVersion()
    {
        if ($this->version) {
            return $this->version;
        }

        $version = date('Ymd');
        if ($this->versionFile && $fh = fopen($this->versionFile, 'r')) {
            $version = fread($fh, 10);
            fclose($fh);
        }
        return $this->version = $version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    public function setVersionFile($versionFile)
    {
        $this->versionFile = $versionFile;
        return $this;
    }

    public function getStatic($url = null)
    {
        return parent::getStatic($url) . '?' . $this->getVersion();
    }
}
