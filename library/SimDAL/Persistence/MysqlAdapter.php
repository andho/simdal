<?php

class SimDAL_Persistence_MysqlAdapter extends SimDAL_Persistence_AdapterAbstract {
	
	private $_host;
	private $_username;
	private $_password;
	private $_database;
	private $_conn;
	private $_transaction = false;
	
	static protected $_verbose = false;
	
	static public function setVerbose($verbose = true) {
		self::$_verbose = $verbose;
	}
	
	public function __construct($host, $username, $password, $database) {
		parent::__construct();
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
	
	public function _insert($entity) {
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$row = $this->_arrayForStorageFromEntity($entity, false, true);
		$sql = $this->_processInsertQuery($class, $row);
		if (($result = $this->execute($sql)) === false) {
			$this->_setError($this->getAdapterError());
			return false;
		}
		
		$id = $this->lastInsertId();
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$entity->$pk = $id;
		
		return true;
	}
	
	public function _update($entity) {
		$row = $this->getUnitOfWork()->getChanges($entity);
		if (count($row) <= 0) {
			return true;
		}
		
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$pk = $this->_getMapper()->getPrimaryKey($class);
			
		$sql = $this->_processUpdateQuery($class, $row, $entity->$pk);
		$result = $this->execute($sql);
		if ($result === false) {
			$this->_errorMessages['dberror'] = $this->getError();
			return false;
		}
				
		return true;
	}
	
	public function _delete($table, $id) {
		$this->_connect();
		
		$sql = "DELETE FROM `$table` WHERE `id`=$id";
		
		mysql_query($sql, $this->_conn);
		
		return mysql_affected_rows($this->_conn);
	}
	
	public function deleteMultiple($class, $keys) {
		if (empty($keys)) {
			return true;
		}
		
		$sql = $this->_processMultipleDeleteQueries($class, $keys);
		
		$result = $this->execute($sql);
		
		if ($result === false) {
			$this->_errorMessages['dberror'] = $this->getAdapterError();
			return false;
		}
		
		return true;
	}
	
	public function updateMultiple($class, $data) {
		
		foreach ($data as $id=>$entity) {
			$row = $this->getUnitOfWork()->getChanges($entity);
			if (count($row) <= 0) {
				return true;
			}
			
			$sql = $this->_processUpdateQuery($class, $row, $id);
			$result = $this->execute($sql);
			if ($result === false) {
				$this->_errorMessages['dberror'] = $this->getAdapterError();
				return false;
			}
			$this->_resolveEntityDependencies($entity);
		}
		
		return true;
	}
	
	public function insertMultiple($class, $data) {
		if (!is_array($data) || count($data) <= 0) {
			return true;
		}
 		foreach ($data as $entity) {
			$row = $this->_arrayForStorageFromEntity($entity, false, true);
			$sql = $this->_processInsertQuery($class, $row);
			if (($result = $this->execute($sql)) === false) {
				$this->_setError($this->getAdapterError());
				return false;
			}
			$id = $this->lastInsertId();
			$entity->id = $id;
			$this->_resolveEntityDependencies($entity);
			$this->getUnitOfWork()->updateCleanEntity($entity);
		}
		
		return true;
	}
	
	public function findById($class, $id) {
		$entity = $this->getUnitOfWork()->getLoaded($class, $id);
		if (!is_null($entity)) {
			return $entity;
		}
		$table = $this->_getMapper()->getTable($class);
		$property = $this->_getMapper()->getPrimaryKey($class);
		$column = $this->_getMapper()->getColumn($class, $property);
		$column = $column[0];
		$this->_connect();
		
		$sql = "SELECT * FROM ".$this->_quoteIdentifier($table)." WHERE `$column` = $id";
		
		return $this->_returnResultRow($sql, $class);
	}
	
	public function findByColumn($class, $value, $column, $limit=1) {
		$table = $this->_getMapper()->getTable($class);
		$property = $this->_getMapper()->getColumn($class, $column);
		$this->_connect();
		
		if (is_string($value)) {
			$value = "'$value'";
		}
		
		$sql = "SELECT * FROM `$table` WHERE `{$property[0]}` = $value";
		if (is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT $limit";
		}
		
		if ($limit == 1) {
			return $this->_returnResultRow($sql, $class);
		}
		
		return $this->_returnResultRows($sql, $class);
	}
	
	public function findBy($class, array $keyValuePairs, $limit=1) {
		$table = $this->_getMapper()->getTable($class);
		$this->_connect();
		
		if (count($keyValuePairs) == 0) {
			return false;
		}
		
		$where = array();
		foreach ($keyValuePairs as $key=>$value) {
			$column = $this->_getMapper()->getColumn($class, $key);
			$where[] = "`{$column[0]}` = '$value'";
		}
		
		$sql = "SELECT * FROM `$table` WHERE ".implode(" AND ", $where);
		if (is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT $limit";
		}
		
		if ($limit == 1) {
			return $this->_returnResultRow($sql, $class);
		}
		
		return $this->_returnResultRows($sql, $class);
	}
	
	public function findByEither($class, array $keyValuePairs, $limit=1) {
		$table = $this->_getMapper()->getTable($class);
		$this->_connect();
		
		if (count($keyValuePairs) == 0) {
			return false;
		}
		
		$where = array();
		foreach ($keyValuePairs as $key=>$value) {
			$column = $this->_getMapper()->getColumn($class, $key);
			$where[] = "`{$column[0]}` = '$value'";
		}
		
		if (!is_null($limit)) {
			$limit = " LIMIT $limit";
		}
		
		$sql = "SELECT * FROM `$table` WHERE ".implode(" OR ", $where)."$limit";
		
		return $this->_returnResultRows($sql, $class);
	}
	
	protected function _returnResultRows($sql, $class) {
		$this->_connect();
		
		$query = mysql_query($sql, $this->_conn);
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		return $this->_returnEntities($rows, $class);
	}
	
	protected function _returnResultRow($sql, $class=null) {
		$this->_connect();
		
		$query = mysql_query($sql, $this->_conn);
		if (mysql_num_rows($query) <= 0) {
			return null;
		}
		$row = mysql_fetch_assoc($query);
		
		if (is_null($class)) {
			return $row;
		}
		
		return $this->_returnEntity($row, $class);
	}
	
	public function query($sql) {
		
	}
	
	public function lastInsertId() {
		return mysql_insert_id($this->_conn);
	}
	
	public function getAdapterError() {
		return mysql_error($this->_conn);
	}
	
	public function getError() {
		if (self::$_verbose) {
			return $this->getAdapterError();
		} else {
			return "There was an error saving to the database";
		}
	}

	protected function _processMultipleDeleteQueries($class, $keys) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		foreach ($keys as $key=>$value) {
			if (is_numeric($key)) {
				if (!isset($where['byid'])) $where['byid'] = array();
				$where['byid'][] = $value;
			} else {
				$where[$key] = $value;
			}
		}
		
		$wherecolumns = array();
		if (isset($where['byid'])) {
			$whereid = "`$pk` IN (".implode(",", $where['byid']).")";
			$wherecolumns['byid'] = $whereid;
		}
		
		foreach ($where as $key=>$value) {
			if ($key == 'byid') {
				continue;
			}
			$column = $this->_getMapper()->getColumn($class, $key);
			$wherecolumns[$key] = "`{$column[0]}` IN (".implode(",", $this->_transformRow($value, $class, $key)).")";
		}
		
		$where = implode(" OR ", $wherecolumns); 
		
		$sql = "DELETE FROM `$table` WHERE $where";
		
		return $sql;
	}
	
	protected function _processUpdateQuery($class, $data, $id) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$pk = $this->_getMapper()->getColumn($class, $pk);
		$pk = $pk[0];
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "UPDATE ".$this->_quoteIdentifier($table)." SET ";
		
		$columns = $this->_getMapper()->getColumnData($class);
		
		foreach ($data as $key=>$value) {
			$sql .= $this->_quoteIdentifier($columns[$key][0])." = ".$this->_transformData($key, $value, $class) . ",";
		}
		$sql = substr($sql,0,-1) . " WHERE `$pk`=$id";
		
		return $sql;
	}
	
	protected function _processInsertQuery($class, $data) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO ".$this->_quoteIdentifier($table)." (`".implode('`,`',array_keys($data))."`) VALUES (".implode(',',$data).")";
		
		return $sql;
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
		$parts = explode('.', $column);
		if (count($parts) > 0) {
			$column = implode('`.`', $parts);
		}
		return "`$column`";
	}
	
	public function execute($sql) {
		$this->_connect();
		
		$result = mysql_query($sql, $this->_conn);
		
		if ($result === false) {
			return false;
		}
		
		if ($result === true) {
			return mysql_affected_rows($this->_conn);
		}
		
		return $result;
	}
	
}