<?php

$projectDir = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' ) . DIRECTORY_SEPARATOR;

$simdal_root = $projectDir . 'library';
$phpspec_root = $projectDir . '..' . DIRECTORY_SEPARATOR . 'PHPSpec';
$mockery_root = $projectDir . '..' . DIRECTORY_SEPARATOR . 'Mockery';

$paths = array(
	'SimDAL'=>$simdal_root,
	'PHPSpec'=>$phpspec_root,
	'Mockery'=>$mockery_root
);

set_include_path( implode( PATH_SEPARATOR, $paths ) . PATH_SEPARATOR . get_include_path() );

require_once 'PHPSpec.php';
require_once 'Mockery/Framework.php';

class Custom_Autoload
{
    public static function autoload($class)
    {
        //$path = dirname(dirname(__FILE__));
        //include $path . '/' . str_replace('_', '/', $class) . '.php';
        if (preg_match('/^([^ _]*)?(_[^ _]*)*$/', $class, $matches)) {
        	$file = str_replace('_', '/', $class) . '.php';
        	/*if (!is_file($file)) {
        		return false;
        	}*/
        	@include $file;
        	return true;
        }
        
        return false;
    }

}

spl_autoload_register(array('Custom_Autoload', 'autoload'));