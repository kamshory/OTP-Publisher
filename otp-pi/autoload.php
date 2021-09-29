<?php
spl_autoload_register(function($class){
    if(file_exists(dirname(__FILE__)."/".$class.'.php'))
    {
        include_once dirname(__FILE__)."/".$class.'.php';
    }
});