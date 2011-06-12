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
 * 
 * @author Amjad Mohamed <andhos@gmail.com>
 */

class Simdal_Simdal extends Zend_Application_Resource_ResourceAbstract {
	
	private $adapterConfig = array();
	private $domainPath;
	
	public function setAdapterConfig($value) {
		$this->adapterConfig = $value;
	}
	
	public function setDomainPath($value) {
		$this->domainPath = $value;
	}
	
	public function init() {
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace('SimDAL_');
		SimDAL_Autoload::setDomainDirectory($this->domainPath);
		SimDAL_Autoload::loadNonDomainClasses(false);
		SimDAL_Session::factory(array('db'=>$this->adapterConfig));
		$autoloader->pushAutoloader(array('SimDAL_Autoload', 'autoload'));
	}
	
}
