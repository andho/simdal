<?php

class SimDAL_Autoload {
	
	protected static $_domainDir = null;
	protected static $_mappers = array();
	
	public static function setDomainDirectory($dir) {
		if (!is_dir($dir)) {
			throw new Exception($dir . ' is not a valid directory');
		}
		$real_path = realpath($dir);
		if (!is_readable($dir)) {
			throw new Exception($dir . ' is not readable');
		}
		if (!is_writable($dir)) {
			throw new Exception($dir . ' is not writable');
		}
		
		self::$_domainDir = $real_path;
		
		if (!is_dir(self::getConfigDirectory())) {
			throw new Exception('The path ' .$dir . ' does not contain SimDAL configuration folder');
		}
	}
	
	protected static function getDomainDirectory() {
		return self::$_domainDir;
	}
	
	protected static function getConfigDirectory() {
		return self::$_domainDir . DIRECTORY_SEPARATOR . '.simdal' . DIRECTORY_SEPARATOR . 'config';
	}
	
	protected static function getProxyDirectory() {
		return self::$_domainDir . DIRECTORY_SEPARATOR . '.simdal' . DIRECTORY_SEPARATOR . 'proxies';
	}
	
	public static function registerMapper(SimDAL_Mapper $mapper) {
		self::$_mappers[] = $mapper;
	}
	
    public static function autoload($class) {
        if (is_null(self::getDomainDirectory())) {
        	throw new Exception('Domain Directory should be set before invoking the SimDAL_Autoloader');
        }
        
        if (preg_match('/^([^ _]*)?(_[^ _]*)*$/', $class, $matches)) {
	        $class_file = str_replace('_', '/', $class) . '.php';
        	if (is_file(self::getDomainDirectory() . DIRECTORY_SEPARATOR . $class_file)) {
	        	include self::getDomainDirectory() . DIRECTORY_SEPARATOR . $class_file;
	        	include self::getProxyDirectory() . DIRECTORY_SEPARATOR . $class_file;
	        	foreach (self::$_mappers as $mapper) {
	        		$mapper->addMappingForEntityClass($class, include( self::getProxyDirectory() . DIRECTORY_SEPARATOR . $class_file) );
	        	} 
	        	return true;
        	} else {
        		include $class_file;
        		return true;
        	}
        }
        
        return false;
    }

}