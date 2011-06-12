<?php

class SimDAL_Persistence_PDO_MySqlAdapter extends SimDAL_Persistence_PDO_PDOAbstract {
	
	private $_host;
	private $_database;
	private $_username;
	private $_password;
	private $_options;
	
	public function __construct(SimDAL_Mapper $mapper, $conf) {
		parent::__construct($mapper, $conf);
		
		if (isset($conf['connection']) && $conf['connection'] instanceof PDO) {
			$this->_conn = $conf['connection'];
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
		$this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->_conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}
	
	public function _disconnect() {
		if (!is_null($this->_conn)) {
			$this->_conn->rollBack();
			$this->_conn = null;
		}
	}
	
	public function startTransaction() {
		if (is_null($this->_conn)) {
			$this->_connect();
		}
		
		$this->_conn->beginTransaction();
		$this->_transaction = true;
	}
	
	public function commitTransaction() {
		if (!is_null($this->_conn) && $this->_transaction = true) {
			$this->_conn->commit();
			if (!$this->_auto_commit) {
				$this->_conn->beginTransaction();
			} else {
				$this->_transaction = false;
			}
		}
	}
	
	public function rollbackTransaction() {
		if (!is_null($this->_conn) && $this->_transaction = true) {
			$this->_conn->rollBack();
			
			if (!$this->_auto_commit) {
				$this->_conn->beginTransaction();
			} else {
				$this->_transaction = false;
			}
		}
	}
	
	public function getAdapterError() {
		if (is_null($this->_conn)) {
			return null;
		}
		
		return $this->_conn->errorCode() . ': ' . $this->_conn->errorInfo();
	}
	
	public function execute($sql, $bind_params = array()) {
		$this->_connect();
		
		if (!$sql instanceof PDOStatement) {
			$stmnt = $this->_conn->prepare($sql);
		} else {
			$stmnt = $sql;
		}
		
		if (is_array($bind_params) && count($bind_params) > 0) {
			foreach ($bind_params as $key=>$value) {
				if (!$stmnt->bindValue(($key+1), $value)) {
					throw new SimDAL_Persistence_AdapterException($this, 'Cannot bind param ' . $key . ' to value ' . $value);
				}
			}
		}
		
		if (!$stmnt->execute()) {
			throw new SimDAL_Persistence_AdapterException($this, "DB error");
		}
		
		return $stmnt;
	}
	
	public function quoteIdentifier($column) {
		$parts = explode('.', $column);
		if (count($parts) > 0) {
			$column = implode('`.`', $parts);
		}
		
		return "`$column`";
	}

	public function escape($value) {
		$this->_connect();
		$value = $this->_conn->quote($value);
		$value = trim($value, "'");
		return $value;
	}
	
	public function lastInsertId() {
		if (is_null($this->_conn)) {
			return null;
		}
		
		return $this->_conn->lastInsertId();
	}
	
	protected function _queryToString(SimDAL_Query $query) {
		$adapter = new SimDAL_Query_TransformAdapter_MySqlStatement($this);
		return $adapter->queryToString($query);
	}
	
}