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

class SimDAL_Persistence_MySqliAdapter extends SimDAL_Persistence_DBAdapterAbstract {
	
	private $_host;
	private $_username;
	private $_password;
	private $_database;
	private $_conn;
	private $_transaction = true;
	
	public function __construct($mapper, $session, $conf) {
		if (!isset($conf['host'])) {
			throw new Exception("Database configuation doesn't specify database host");
		}
		if (!isset($conf['username'])) {
			throw new Exception("Database configuation doesn't specify database username");
		}
		if (!isset($conf['password'])) {
			throw new Exception("Database configuation doesn't specify database password");
		}
		if (!isset($conf['database'])) {
			throw new Exception("Database configuation doesn't specify database database");
		}
		
		parent::__construct($mapper, $session);
		$this->_host = $conf['host'];
		$this->_username = $conf['username'];
		$this->_password = $conf['password'];
		$this->_database = $conf['database'];
	}
	
	protected function _connect() {
		if (!is_null($this->_conn)) {
			return;
		}
		
		$this->_conn = mysqli_connect($this->_host, $this->_username, $this->_password);
		mysqli_select_db($this->_conn, $this->_database);
		mysqli_autocommit($this->_conn, false);
	}
	
	public function __destruct() {
		$this->_disconnect();
	}
	
	protected function _disconnect() {
		if (is_resource($this->_conn)) {
			$this->_rollbackTransaction();
			mysqli_close($this->_conn);
			$this->_conn = null;
		}
	}
	
	protected function _processGetAllQuery($class) {
		$table = $this->_getMapper()->getTable($class);
		
		return "SELECT * FROM `$table`";
	}
	
	protected function _processFindByIdQuery($class, $id) {
		$table = $this->_getMapper()->getTable($class);
		$property = $this->_getMapper()->getPrimaryKey($class);
		$column = $this->_getMapper()->getColumn($class, $property);
		$column = $column[0];
		
		$query = new SimDAL_Persistence_Query($this);
		$query->from($table);
		$query->where("$table.$column", $id);
		
		if ($this->_getMapper()->hasDescendants($class)) {
			foreach ($this->_getMapper()->getDescendants($class) as $descendantClass=>$descendant) {
				$fk = $this->_getMapper()->getDescendantColumn($class, $descendantClass, $descendant['foreignKey']);
				$fk = $fk[0];
				$pk = $this->_getMapper()->getColumn($class, $descendant['parentKey']);
				$pk = $pk[0];
				$query->join($descendant['table'], new SimDAL_Persistence_Query_Condition("$table.{$pk}", "{$descendant['table']}.{$fk}"));
			}
		}
		
		return $query->__toString();
		
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
		
		$query = mysqli_query($this->_conn, $sql) or error_log(mysqli_error($this->_conn), 0);
		
		if ($query === false) {
			return false;
		}
		
		$rows = array();
		while ($row = mysqli_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		mysqli_free_result($query);
		
		return $rows;
	}
	
	protected function _processInsertQuery(SimDAL_Mapper_Entity $entity, $data) {
		$table = $entity->getTable();
		
		$sql = "INSERT INTO ".$this->_quoteIdentifier($table)." (`".implode('`,`',array_keys($data))."`) VALUES (".implode(',',$data).")";
		
		return $sql;
	}
	
	protected function _returnResultRows($sql, $class, $lockRows = false) {
		$this->_connect();
		
		if ($lockRows) {
			$sql .= ' FOR UPDATE';
		}
		
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
	
	protected function _returnResultRow($sql, $class=null, $lockRows = false) {
		$this->_connect();
		
		if ($lockRows) {
			$sql .= ' FOR UPDATE';
		}
		
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
		$this->_connect();
		
		return mysqli_real_escape_string($this->_conn, $value);
	}
	
	protected function _whereRange($key, $values) {
		$where = "`{$key}` IN (".implode(",", $values).")";
		
		return $where;
	}
	
	public function startTransaction() {
		$this->_connect();
		return mysqli_autocommit($this->_conn, false);
	}
	
	public function commitTransaction() {
		$result = mysqli_commit($this->_conn);
		
		return $result;
	}
	
	public function rollbackTransaction() {
		$result = mysqli_rollback($this->_conn);
		
		return $result;
	}
	
	protected function _quoteIdentifier($column) {
		$parts = explode('.', $column);
		if (count($parts) > 0) {
			$column = implode('`.`', $parts);
		}
		
		return "`$column`";
	}
	
	public function executeQueryObject($query) {
		$sql = $this->_queryToString($query);
		
		return $this->execute($sql);
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
	
	protected function _queryToString(SimDAL_Query $query) {
		$adapter = new SimDAL_Query_TransformAdapter_Mysql();
		$adapter->queryToString($query);
	}
	
}