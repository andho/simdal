<?php

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
		
		$proxyFile = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'simdal_proxies.inc';
		if (!is_file($proxyFile)) {
			throw new Exception('Proxy file not found');
		} else if (!is_readable($proxyFile)) {
			throw new Exception('Unable to load proxy file');
		} else {
			include $proxyFile;
		}
		
		SimDAL_Entity::setDefaultMapper($this->_mapper);
	}
	
	protected function _setupDatabaseSettings($db) {
		if (!isset($db['host'])) {
			throw new Exception("Database configuation doesn't specify database host");
		}
		if (!isset($db['username'])) {
			throw new Exception("Database configuation doesn't specify database username");
		}
		if (!isset($db['password'])) {
			throw new Exception("Database configuation doesn't specify database password");
		}
		if (!isset($db['database'])) {
			throw new Exception("Database configuation doesn't specify database database");
		}
		
		$this->_db = $db;
	}
	
	/**
	 * @return SimDAL_Session
	 */
	public function getCurrentSession() {
		if (is_null($this->_session)) {
			$adapter_class = $this->_db['class'];
			$this->_session = new SimDAL_Session($this->_mapper, $adapter_class, $this->_db);
		}
		
		return $this->_session;
	}
	
}