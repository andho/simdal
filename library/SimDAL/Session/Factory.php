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

class SimDAL_Session_Factory {
	
	protected $_db;
	protected $_mapper;
	protected $_session;
	
	public function __construct($conf) {
		if (!isset($conf['db'])) {
			throw new Exception("SimDAL configuration doesn't have Database configuration options");
		}
		$this->_setupDatabaseSettings($conf['db']);
		
		if (!isset($conf['map'])) {
			throw new Exception("SimDAL configuration doesn't have mapper configuration");
		}
		$this->_mapper = new SimDAL_Mapper($conf['map']);
	}
	
	protected function _setupDatabaseSettings($db) {
		$this->_db = $db;
	}
	
	/**
	 * @return SimDAL_Session
	 */
	public function getCurrentSession() {
		if (is_null($this->_session)) {
			$this->_session = $this->getNewSession();
		}
		
		return $this->_session;
	}
	
	/**
	 * @return SimDAL_Session
	 */
	public function getNewSession() {
		$proxyFile = DOMAIN_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'simdal_proxies.inc';
		if (!is_file($proxyFile)) {
			throw new Exception('Proxy file not found');
		} else if (!is_readable($proxyFile)) {
			throw new Exception('Unable to load proxy file');
		} else {
			include_once $proxyFile;
		}
	
		$adapter_class = $this->_db['class'];
		return new SimDAL_Session($this->_mapper, $adapter_class, $this->_db);
	}
	
	public function getMapper() {
		return $this->_mapper;
	}
	
}