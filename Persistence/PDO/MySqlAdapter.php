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
	
	public function startTransaction() {
		if (is_null($this->_conn)) {
			$this->_connect();
		}
		
		$this->_conn->beginTransaction();
	}
	
	public function commitTransaction() {
		if (!is_null($this->_conn)) {
			$this->_conn->commit();
		}
	}
	
	public function rollbackTransaction() {
		if (!is_null($this->_conn)) {
			$this->_conn->rollBack();
		}
	}
	
	public function getAdapterError() {
		if (is_null($this->_conn)) {
			return null;
		}
		
		return $this->_conn->errorCode() . ': ' . $this->_conn->errorInfo();
	}
	
	public function execute($sql) {
		$this->_connect();
		
		if (!$sql instanceof PDOStatement) {
			$stmnt = $this->_conn->prepare($sql);
		}
		
		if (!$stmnt->execute()) {
			throw new SimDAL_Persistence_AdapterException("DB error");
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
		
		return $this->_conn->quote($value);
	}
	
	protected function _queryToString(SimDAL_Query $query) {
		$adapter = new SimDAL_Query_TransformAdapter_MySql($this);
		return $adapter->queryToString($query);
	}
	
	protected function _returnResultRows($sql, $class, $lockRows = false) {
		$stmnt = $this->execute($sql);
		
		$rows = $stmnt->fetchAll(PDO::FETCH_ASSOC);
		
		$stmnt->closeCursor();
		
		return $this->_returnEntities($rows, $class);
	}
	
	protected function _returnResultRow($sql, $class=null, $lockRows = false) {
		$stmnt = $this->execute($sql);
		
		$row = $stmnt->fetch(PDO::FETCH_ASSOC);
		
		$stmnt->closeCursor();
		
		if (is_null($class)) {
			return $row;
		}
		
		return $this->_returnEntity($row, $class);
	}
	
}