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

class SimDAL_Persistence_MysqlAdapter extends SimDAL_Persistence_AdapterAbstract {
	
	private $_host;
	private $_username;
	private $_password;
	private $_database;
	private $_conn;
	protected $_transaction = false;
	
	static protected $_verbose = false;
	
	static public function setVerbose($verbose = true) {
		self::$_verbose = $verbose;
	}
	
	public function __construct($mapper, array $conf) {
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
		
		parent::__construct($mapper, $conf);
		$this->_host = $conf['host'];
		$this->_username = $conf['username'];
		$this->_password = $conf['password'];
		$this->_database = $conf['database'];
	}
	
	public function __destruct() {
		if (is_resource($this->_conn)) {
			mysql_close($this->_conn);
			$this->_conn = null;
		}
	}
	
	protected function _connect() {
		if (!is_null($this->_conn)) {
			return true;
		}
		
		$this->_conn = mysql_pconnect($this->_host, $this->_username, $this->_password);
		mysql_select_db($this->_database);
	}
	
	public function _delete($table, $id) {
		$this->_connect();
		
		$sql = "DELETE FROM `$table` WHERE `id`=$id";
		
		mysql_query($sql, $this->_conn);
		
		return mysql_affected_rows($this->_conn);
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
		
		$query = mysql_query($sql, $this->_conn) or error_log(mysql_error($this->_conn), 0);
		
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
	
	protected function _returnResultRows($sql, $class) {
		$this->_connect();
		
		$query = mysql_query($sql, $this->_conn) or error_log(mysql_error($this->_conn), 0);
		
		if ($query === false) {
			return false;
		}
		
		$rows = array();
		while ($row = mysql_fetch_assoc($query)) {
			$rows[] = $row;
		}
		
		mysql_free_result($query);
		
		return $this->_returnEntities($rows, $class);
	}
	
	protected function _returnResultRow($sql, $class=null) {
		$this->_connect();
		
		if (!($query = mysql_query($sql, $this->_conn))) {
			error_log(mysql_error($this->_conn), 0);
			return false;
		}
		if (mysql_num_rows($query) <= 0) {
			return null;
		}
		$row = mysql_fetch_assoc($query);
		
		if (is_null($class)) {
			return $row;
		}
		
		mysql_free_result($query);
		
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
	
	public function escape($value, $type=null) {
		$this->_connect();
		if ($type == 'binary') {
			$value = base64_encode($value);
		}
		return mysql_real_escape_string($value);
	}
	
	public function getError() {
		if (self::$_verbose) {
			return $this->getAdapterError();
		} else {
			return "There was an error saving to the database";
		}
	}

	protected function _whereRange($key, $values) {
		if (!is_array($values) && is_scalar($values)) {
			$values = array($values);
		}
		$where = "`{$key}` IN (".implode(",", $values).")";
		
		return $where;
	}
	
	protected function _processInsertQuery($class, $data) {
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO ".$this->_quoteIdentifier($table)." (`".implode('`,`',array_keys($data))."`) VALUES (".implode(',',$data).")";
		
		return $sql;
	}
	
	public function startTransaction() {
		return;
	}
	
	public function commitTransaction() {
		return;
	}
	
	public function rollbackTransaction() {
		return;
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
			error_log(mysql_error($this->_conn));
			return false;
		}
		
		if ($result === true) {
			return mysql_affected_rows($this->_conn);
		}
		
		return $result;
	}
	
	protected function _queryToString(SimDAL_Query $query) {
		$sql = 'SELECT * FROM ' . $query->getFrom();
		
		foreach ($query->getJoins() as $join) {
			$sql .= $join->getJoinType() . ' ' . $join->getTable() . ' ON ';
			foreach ($join->getWheres() as $where) {
				$method = '_process' . $where->getProcessMethod();
				$sql = $this->$method($where);
			}
		}
		
		$wheres = array();
		foreach ($query->getWheres() as $where) {
			$method = '_process' . $where->getProcessMethod();
			$wheres[] = $this->$method($where);
		}
		if (count($wheres) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $wheres);
		}
		
		return $sql;
		
	}
	
	protected function _processWhereJoinDescendant($where) {
		return $where->getLeftValue()->getTable() . '.' . $where->getLeftValue()->getColumn() . '=' . $where->getRightValue()->getTable() . '.' . $where->getRightValue()->getColumn();
	}
	
	protected function _processWhereId(SimDAL_Query_Where_Id $where) {
		$primary_key_column = $where->getLeftValue();
		$output = $primary_key_column->getTable() . '.' . $primary_key_column->getColumn();
		$output .= ' = ' . $where->getRightValue();
		return $output;
	}
	
}