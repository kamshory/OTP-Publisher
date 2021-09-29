<?php
class ConfigLoader{
    public function __construct()
    {

    }
    public function load()
    {
        $file = dirname(__FILE__)."/config.ini";
        if(file_exists($file))
        {
            $ini_array = parse_ini_file($file, true);
        }
        else
        {
            $ini_array = array();
        }
        return $ini_array;
    }
}