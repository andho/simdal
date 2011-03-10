<?php

class SimDAL_Autoload {
	
    public static function autoload($class) {
        //$path = dirname(dirname(__FILE__));
        //include $path . '/' . str_replace('_', '/', $class) . '.php';
        if (preg_match('/^([^ _]*)?(_[^ _]*)*$/', $class, $matches)) {
	        $class_file = str_replace('_', '/', $class) . '.php';
        	include $class_file;
        	return true;
        }
        
        return false;
    }

}