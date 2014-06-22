<?php

namespace Eva\EvaEngine\Module;

interface StandardInterface
{
    public static function registerGlobalAutoloaders();

    public static function registerGlobalEventListeners();

    public static function registerGlobalViewHelpers();
}
