<?php

class SimDAL_Persistence_MySqliAdapter extends SimDAL_Persistence_AdapterAbstract {
	
	private $_host;
	private $_username;
	private $_password;
	private $_database;
	private $_conn;
	private $_transaction = true;
	
	public function __construct($mapper, $session, $conf) {
		parent::__construct($mapper, $session);
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
	
	protected function _queryToString(SimDAL_Query $query) {
	    $columns = array();
	    $columns_array = $query->getColumns();
	    if ($query->hasAliases() || count($columns_array) > 0) {
	        $columns_array = $query->getTableColumns();
	    }
	    if (count($columns_array) > 0) {
	        /* @var $column SimDAL_Mapper_Column */
	        foreach ($columns_array as $column) {
		        $column_string = $column->getTable() . '.' . $column->getColumn();
		        if ($column->hasAlias()) {
		            $column_string .= ' AS ' . $column->getAlias();
		        }
		        $columns[] = $column_string;
	        }
	    } else {
	        $columns[] = $query->getFrom() . '.*';
	    }
		
		/* @var $join SimDAL_Query_Join_Descendent */
	    $joins = '';
		foreach ($query->getJoins() as $join) {
			$joins .= ' ' . $join->getJoinType() . ' ' . $join->getTable() . ' ON ';
			foreach ($join->getWheres() as $where) {
				$method = '_process' . $where->getProcessMethod();
				$joins .= $this->$method($where);
			}
			$columns_array = $join->getColumns();
			if ($join->hasAliases() || count($columns_array) > 0) {
			    $columns_array = $join->getTableColumns();
			}
			if (count($columns_array) > 0) {
			    /* @var $column SimDAL_Mapper_Column */
			    foreach ($columns_array as $column) {
			        $column_string = $column->getTable() . '.' . $column->getColumn();
			        if ($column->hasAlias()) {
			            $column_string .= ' AS ' . $this->_quoteIdentifier($column->getAlias());
			        }
			        $columns[] = $column_string;
			    }
			} else {
			    $columns[] = $join->getTable() . '.*';
			}
		}
		
		$wheres = array();
		foreach ($query->getWheres() as $where) {
			$wheres[] = $this->_processWhere($where);
		}
		
		$limit = $this->_processQueryLimit($query->getLimit()->getLimit(), $query->getLimit()->getOffset(), $query);
		
		$sql = 'SELECT ' . implode(', ', $columns) . ' ';
		$sql .= 'FROM ' . $query->getFrom();
		$sql .= $joins;
		
		if (count($wheres) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $wheres);
		}
		
		$sql .= ' ' . $limit;
		
		return $sql;
		
	}
	
	protected function _processWhereJoinDescendent($where) {
		return $where->getLeftValue()->getTable() . '.' . $where->getLeftValue()->getColumn() . '=' . $where->getRightValue()->getTable() . '.' . $where->getRightValue()->getColumn();
	}
	
	protected function _processWhereId(SimDAL_Query_Where_Id $where) {
		$primary_key_column = $where->getLeftValue();
		$output = $primary_key_column->getTable() . '.' . $primary_key_column->getColumn();
		$output .= ' = ' . $this->_transformData($primary_key_column->getProperty(), $where->getRightValue(), $primary_key_column->getClass());
		return $output;
	}
	
	protected function _processWhere(SimDAL_Query_Where_Interface $where) {
		if ($where instanceof SimDAL_Query_Where_Collection) {
			//@todo do whatever is needed
		}
		$left = $this->_processWhereValue($where->getLeftValue(), $where);
		$right = $this->_processWhereValue($where->getRightValue(), $where);
		
		$operator = $this->_processWhereOperator($where->getOperator());
		
		return $left . $operator . $right;
	}
	
	protected function _processWhereValue($value, $where) {
		if (is_object($value)) {
			$class = get_class($value);
			switch ($class) {
				case 'SimDAL_Mapper_Column': return $this->_processWhereColumn($value->getTable(), $value->getColumn(), $where); break;
			}
		} else {
			return "'" . $value . "'";
		}
	}
	
	protected function _processWhereColumn($table, $column, $where) {
		return $this->_quoteIdentifier($table) . '.' . $this->_quoteIdentifier($column);
	}
	
	protected function _processWhereOperator($operator) {
		switch ($operator) {
			case 'LIKE': return ' LIKE '; break;
			case '=':
			default: return ' = '; break;
		}
	}
	
	protected function _processQueryLimit($limit, $offset, SimDAL_Query $query) {
		$output = '';
		if (is_numeric($limit)) {
			$output = $limit;
			if (is_numeric($offset)) {
				$output = $offset . ", " . $output;
			}
			
			$output = 'LIMIT ' . $output;
		}
		
		return $output;
	}
	
}