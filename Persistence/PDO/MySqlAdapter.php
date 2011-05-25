<?php

class SimDAL_Persistence_PDO_MySqlAdapter extends SimDAL_Persistence_PDO_PDOAbstract {
	
	private $_host;
	private $_database;
	private $_username;
	private $_password;
	private $_options;
	
	public function __construct(SimDAL_Mapper $mapper, SimDAL_Session $session, $conf) {
		parent::__construct($mapper, $session);
		
		if ($conf instanceof PDO) {
			$this->_conn = $conf;
		} else {
			$this->_host = $conf['host'];
			$this->_username = $conf['username'];
			$this->_password = $conf['password'];
			$this->_database = $conf['database'];
			$this->_options = isset($conf['options']) ? $conf['options'] : array();
		}
	}
	
	public function _connect() {
		if (!is_null($this->_conn)) {
			return;
		}
		
		$this->_conn = new PDO('mysql:host=' . $this->_host . '; dbname=' . $this->_database, $this->_username, $this->_password, $this->_options);
	}
	
	public function _disconnect() {
		if (!is_null($this->_conn)) {
			$this->_conn->rollBack();
			$this->_conn = null;
		}
	}
	
}