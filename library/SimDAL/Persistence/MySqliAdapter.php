<?php

class SimDAL_Persistence_MySqliAdapter extends SimDAL_Persistence_AdapterAbstract {
	
	private $_host;
	private $_username;
	private $_password;
	private $_database;
	private $_conn;
	private $_transaction = true;
	
	public function __construct($conf) {
		parent::__construct();
		$this->_host = $conf['host'];
		$this->_username = $conf['username'];
		$this->_password = $conf['password'];
		$this->_database = $conf['database'];
	}
	
	public function __destruct() {
		if (is_resource($this->_conn)) {
			mysqli_rollback($this->_conn);
			mysqli_close($this->_conn);
			$this->_conn = null;
		}
	}
	
	protected function _connect() {
		if (!is_null($this->_conn)) {
			return;
		}
		
		$this->_conn = mysqli_connect($this->_host, $this->_username, $this->_password);
		mysqli_select_db($this->_conn, $this->_database);
		mysqli_autocommit($this->_conn, false);
	}
	
	protected function _processGetAllQuery($class) {
		$table = $this->_getMapper()->getTable($class);
		
		return "SELECT * FROM `$table`";
	}
	
	protected function _processFindByIdQuery($table, $column, $id) {
		return "SELECT * FROM ".$this->_quoteIdentifier($table)." WHERE `$column` = '$id'";
	}
	
	protected function _processFindByColumnQuery($table, $key, $value, $limit) {
		$sql = "SELECT * FROM `$table` WHERE `{$key}` = $value";
		if (is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT $limit";
		}
		
		return $sql;
	}
	
	protected function _processFindByQuery($table, $where, $limit) {
		$sql = "SELECT * FROM `$table` WHERE ".implode(" AND ", $where);
		if (is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT $limit";
		}
		
		return $sql;
	}
	
	protected function _processFindByEither($table, $where, $limit) {
		if (!is_null($limit)) {
			$limit = " LIMIT $limit";
		}
		
		$sql = "SELECT * FROM `$table` WHERE ".implode(" OR ", $where)."$limit";
		
		return $sql;
	}
	
	protected function _returnResultRowsAsArray($sql) {
		$this->_connect();
		
		$query = mysql_query($this->_conn, $sql) or error_log(mysqli_error($this->_conn), 0);
		
		if ($query === false) {
			return false;
		}
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		mysql_free_result($query);
		
		return $rows;
	}
	
	protected function _processInsertQuery($class, $data) {
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO ".$this->_quoteIdentifier($table)." (`".implode('`,`',array_keys($data))."`) VALUES (".implode(',',$data).")";
		
		return $sql;
	}
	
	protected function _returnResultRows($sql, $class) {
		$this->_connect();
		
		$query = mysqli_query($this->_conn, $sql, MYSQLI_STORE_RESULT);
		
		if ($query === false) {
			return $this->_returnEntities(array(), $class);
		}
		
		$rows = array();
		while ($row = mysqli_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		mysqli_free_result($query);
		
		return $this->_returnEntities($rows, $class);
	}
	
	protected function _returnResultRow($sql, $class=null) {
		$this->_connect();
		
		if (!($query = mysqli_query($this->_conn, $sql))) {
			return null;
		}
		if (mysqli_num_rows($query) <= 0) {
			return null;
		}
		$row = mysqli_fetch_assoc($query);
		
		if (is_null($class)) {
			return $row;
		}
		
		mysqli_free_result($query);
		
		return $this->_returnEntity($row, $class);
	}
	
	public function query($sql) {
		
	}
	
	public function lastInsertId() {
		return mysqli_insert_id($this->_conn);
	}
	
	public function getAdapterError() {
		return mysqli_error($this->_conn);
	}
	
	public function escape($value, $type=null) {
		return mysqli_real_escape_string($this->_conn, $value);
	}
	
	protected function _whereRange($key, $values) {
		$where = "`{$key}` IN (".implode(",", $values).")";
		
		return $where;
	}
	
	public function startTransaction() {
		return mysqli_autocommit($this->_conn, false);
	}
	
	public function commitTransaction() {
		$result = mysqli_commit($this->_conn);
		mysqli_autocommit($this->_conn, true);
		
		return $result;
	}
	
	public function rollbackTransaction() {
		$result = mysqli_rollback($this->_conn);
		mysqli_autocommit($this->_conn, true);
		
		return $result;
	}
	
	protected function _quoteIdentifier($column) {
		$parts = explode('.', $column);
		if (count($parts) > 0) {
			$column = implode('`.`', $parts);
		}
		
		return "`$column`";
	}
	
	public function execute($sql) {
		$this->_connect();
		
		$result = mysqli_query($this->_conn, $sql);
		
		if ($result === false) {
			return false;
		}
		
		if ($result === true) {
			return mysqli_affected_rows($this->_conn);
		}
		
		return $result;
	}
	
	
	
}