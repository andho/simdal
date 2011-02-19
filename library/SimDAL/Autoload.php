<?php
/**
 * SimDAL - Simple Domain Abstraction Library.
 * This library will help you to separate your domain logic from
 * your persistence logic and makes the persistence of your domain
 * objects transparent.
 * 
 * Copyright (C) 2011  Andho
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
	        $class_file = str_replace('_', '/', $class);
        	if (is_file(self::getDomainDirectory() . DIRECTORY_SEPARATOR . $class_file . '.php')) {
	        	include self::getDomainDirectory() . DIRECTORY_SEPARATOR . $class_file . '.php';
	        	include self::getProxyDirectory() . DIRECTORY_SEPARATOR . $class_file . '.inc';
	        	foreach (self::$_mappers as $mapper) {
	        		$mapper->addMappingForEntityClass($class, include( self::getConfigDirectory() . DIRECTORY_SEPARATOR . $class_file . '.php') );
	        	} 
	        	return true;
        	} else {
        		include $class_file . '.php';
        		return true;
        	}
        }
        
        return false;
    }

}