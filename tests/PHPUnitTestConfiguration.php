<?php

$projectDir = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' ) . DIRECTORY_SEPARATOR;

$simdal_root = $projectDir . 'library';
$test_project = $projectDir . 'tests' . DIRECTORY_SEPARATOR . 'TestSample';
$phpunit_root = $projectDir . '..' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'PHPUnit';


$paths = array(
	'SimDAL'=>$simdal_root,
	'TestProject'=>$test_project,
	'PHPUnit'=>$phpunit_root
);

require_once 'PHPUnit/Framework.php';

set_include_path( implode( PATH_SEPARATOR, $paths ) . PATH_SEPARATOR . get_include_path() );

class Custom_Autoload
{
    public static function autoload($class)
    {
        //$path = dirname(dirname(__FILE__));
        //include $path . '/' . str_replace('_', '/', $class) . '.php';
        if (preg_match('/^([^ _]*)?(_[^ _]*)*$/', $class, $matches)) {
        	include str_replace('_', '/', $class) . '.php';
        	return true;
        }
        
        return false;
    }

}

spl_autoload_register(array('Custom_Autoload', 'autoload'));