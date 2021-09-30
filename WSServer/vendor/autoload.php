<?php

// autoload.php generated by Composer

spl_autoload_register(function($class){

$file = dirname(__FILE__)."/".$class.'.php';
$file = str_replace("\\", "/", $file);
//echo "FILE : $file\r\n";	

if(file_exists($file))
{
	
	include_once $file;
}
});



require_once __DIR__ . '/composer' . '/autoload_real.php';




return ComposerAutoloaderInit0d62c72c2753a2d24e146d4b6a6a4b58::getLoader();