<?php

class SimDAL_Persistence_MysqlAdapter extends SimDAL_Persitence_AdapterAbstract {
	
	private $_host;
	private $_username;
	private $_password;
	private $_database;
	private $_conn;
	private $_transaction = false;
	
	public function __construct($host, $username, $password, $database) {
		$this->_host = $host;
		$this->_username = $username;
		$this->_password = $password;
		$this->_database = $database;
	}
	
	protected function _connect() {
		if (!is_null($this->_conn)) {
			return true;
		}
		
		$this->_conn = mysql_pconnect($this->_host, $this->_username, $this->_password);
		mysql_select_db($this->_database);
	}
	
	public function _insert($table, $data) {
		$this->_connect();
		
		/*$multiple = false;
		
		if (isset($data[0]) AND is_array($data[0])) {
			$multiple = true;
		}
		
		if (!$multiple) {*/
			$sql = "INSERT INTO `$table` (`" . implode("`,`", array_keys($data)) . "`) VALUES ('" . implode("','", $data) . "')";
		/*} else {
			$sql = "INSERT INTO `$table` (`" . implode("`,`", array_keys($data[0])) . "`) VALUES ";
			
			foreach ($data as $row) {
				$sql .= "('" . implode("','", $row) . "'),";
			}
			$sql = substr($sql, 0, -1);
		}*/
			
		if (!mysql_query($sql, $this->_conn)) {
			return false;
		}
		
		return mysql_insert_id($this->_conn);
	}
	
	public function _update($table, $data, $id, $column='id') {
		$this->_connect();
		
		$sql = "UPDATE `$table` SET ";
		foreach ($data as $key=>$value) {
			$sql .= "`$key`='$value',";
		}
		$sql = substr($sql, 0, -1) . " WHERE `$column`=$id";
		
		mysql_query($sql, $this->_conn);
		
		return mysql_affected_rows($this->_conn);
	}
	
	public function _delete($table, $id) {
		$this->_connect();
		
		$sql = "DELETE FROM `$table` WHERE `id`=$id";
		
		mysql_query($sql, $this->_conn);
		
		return mysql_affected_rows($this->_conn);
	}
	
	public function deleteMultiple($class, $keys) {
		$sql = $this->_processMultipleDeleteQuery($class, $keys);
		
		return $this->execute($sql);
	}
	
	public function updateMultiple($class, $data) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		foreach ($data as $id=>$row) {
			$sql = $this->_processUpdateQuery($table, $row, $id, $pk);
			$result = $this->execute($sql);
		}
	}
	
	public function insertMultiple($class, $data) {
		foreach ($data as $entity) {
			$row = $this->_arrayForStorageFromEntity($entity, false, true);
			$sql = $this->_processInsertQuery($class, $row);
			$result = $this->execute($sql);
			$id = $this->lastInsertedId();
			$entity->id = $id;
		}
	}
	
	public function findById($table, $id, $column = 'id') {
		$this->_connect();
		
		$sql = "SELECT * FROM `$table` WHERE `$column` = $id";
		$query = mysql_query($sql, $this->_conn);
		if (mysql_num_rows($query) <= 0) {
			return null;
		}
		$row = mysql_fetch_assoc($query);
		
		return $row;
	}
	
	public function findByColumn($table, $value, $column, $limit=1) {
		$this->_connect();
		
		if (is_string($value)) {
			$value = "'$value'";
		}
		
		$sql = "SELECT * FROM `$table` WHERE `$column` = $value";
		if (is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT $limit";
		}
		
		$query = mysql_query($sql, $this->_conn);
		if (mysql_num_rows($query) <= 0) {
			return null;
		}
	
		if (is_numeric($limit) && $limit > 0) {
			$row = mysql_fetch_assoc($query);
			
			return $row;
		}
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		return $rows;
	}
	
	public function findBy($table, array $keyValues, $limit=1) {
		$this->_connect();
		
		if (count($keyValues) == 0) {
			return false;
		}
		
		$where = array();
		foreach ($keyValues as $key=>$value) {
			$where[] = "`$key` = '$value'";
		}
		
		$sql = "SELECT * FROM `$table` WHERE ".implode(" AND ", $where)."$limit";
		
		$query = mysql_query($sql, $this->_conn);
		if (mysql_num_rows($query) <= 0) {
			return null;
		}
		
		if (is_numeric($limit) && $limit > 0) {
			$row = mysql_fetch_assoc($query);
			
			return $row;
		}
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		return $rows;
	}
	
	public function findByEither($table, array $keyValues, $limit1) {
		$this->_connect();
		
		if (count($keyValues) == 0) {
			return false;
		}
		
		$where = array();
		foreach ($keyValues as $key=>$value) {
			$where[] = "`$key` = '$value'";
		}
		
		$sql = "SELECT * FROM `$table` WHERE ".implode(" OR ", $where)."$limit";
		
		$query = mysql_query($sql, $this->_conn);
		if (mysql_num_rows($query) <= 0) {
			return null;
		}
		
		if (is_numeric($limit) && $limit > 0) {
			$row = mysql_fetch_assoc($query);
			
			return $row;
		}
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		return $rows;
	}
	
	public function query($sql) {
		
	}
	
	public function lastInsertId() {
		return mysql_insert_id($this->_conn);
	}
	
	public function getError() {
		return mysql_error($this->_conn);
	}

	protected function _processMultipleDeleteQuery($class, $keys) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "DELETE FROM `$table` WHERE `$pk` IN (".implode(",", $keys).")";
		
		return $sql;
	}
	
	protected function _processUpdateQuery($table, $data, $id, $pk) {
		$sql = "UPDATE `$table` SET ";
		foreach ($data as $key=>$value) {
			$sql .= $this->_quoteIdentifier($key)." = ".$this->_transformData($key, $value, $class) . ",";
		}
		$sql = substr($sql,0,-1) . " WHERE `$pk`=$id";
		
		return $sql;
	}
	
	protected function _processInsertQuery($class, $data) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO `$table` (`".implode('`,`',array_keys($data))."`) VALUES (".implode('`,`',$this->_transformRow($data, $class)).")";
		
		return $this->execute($sql);
	}
	
	protected function _processMultipleInsertQueries($class, $data) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO `$table` (`".implode('`,`',$this->_getMapper()->getColumnData($class))."`) VALUES ";
		
		foreach ($data as $row) {
			$sql .= "(" . implode(',', $this->_transaformRow($row, $class)) . "),";
		}
		$sql = substr($sql,0,-1);
		
		return $sql;
	}
	
	protected function _quoteIdentifier($column) {
		return "`$column`";
	}
	
	public function execute($sql) {
		$this->_connect();
		
		$result = mysql_query($sql, $this->_conn);
		
		if ($result === true || $result === false) {
			return mysql_affected_rows($this->_conn);
		}
		
		return $result;
	}
	
}