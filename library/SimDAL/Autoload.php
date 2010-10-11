<?php

class SimDAL_Autoload {
	
    public static function autoload($class) {
        //$path = dirname(dirname(__FILE__));
        //include $path . '/' . str_replace('_', '/', $class) . '.php';
        if (preg_match('/^([^ _]*)?(_[^ _]*)*$/', $class, $matches)) {
        	include str_replace('_', '/', $class) . '.php';
        	return true;
        }
        
        return false;
    }

}