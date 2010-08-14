<?php

class SimDAL_Persistence_SqLiteAdapter extends SimDAL_Persistence_AdapterAbstract {
	
	private $_filename;
	private $_conn;
	
	public function __construct($mapper, $session, $conf) {
		parent::__construct($mapper, $session);
		$this->_filename = $conf['filename'];
	}
	
	public function __destruct() {
		if (is_resource($this->_conn)) {
			mysqli_close($this->_conn);
			$this->_conn = null;
		}
	}
	
	protected function _connect() {
		if (!is_null($this->_conn)) {
			return;
		}
		
		$error = '';
		$this->_conn = sqlite_open($this->_filename, 0666, $error);
		if (!$this->_conn) {
			throw new Exception('Could not connect to SQLite database \'' . $this->_filename . '\': ' . $error);
		}
	}
	
	protected function _processGetAllQuery($class) {
		$table = $this->_getMapper()->getTable($class);
		
		return "SELECT * FROM $table";
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
		
		return "SELECT * FROM ".$this->_quoteIdentifier($table)." WHERE $column = '$id'";
	}
	
	protected function _processFindByColumnQuery($table, $key, $value, $limit) {
		$sql = "SELECT * FROM $table WHERE {$key} = $value";
		if (is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT $limit";
		}
		
		return $sql;
	}
	
	protected function _processFindByQuery($table, $where, $limit) {
		$sql = "SELECT * FROM $table WHERE ".implode(" AND ", $where);
		if (is_numeric($limit) && $limit > 0) {
			$sql .= " LIMIT $limit";
		}
		
		return $sql;
	}
	
	protected function _processFindByEither($table, $where, $limit) {
		if (!is_null($limit)) {
			$limit = " LIMIT $limit";
		}
		
		$sql = "SELECT * FROM $table WHERE ".implode(" OR ", $where)."$limit";
		
		return $sql;
	}
	
	protected function _returnResultRowsAsArray($sql) {
		$this->_connect();
		
		$query = sqlite_query($this->_conn, $sql) or error_log(sqlite_error_string($this->_conn), 0);
		
		if ($query === false) {
			return false;
		}
		
		$rows = sqlite_fetch_all($query);
		
		return $rows;
	}
	
	protected function _processInsertQuery($class, $data) {
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO ".$this->_quoteIdentifier($table)." (".implode(',',array_keys($data)).") VALUES (".implode(',',$data).")";
		
		return $sql;
	}
	
	protected function _returnResultRows($sql, $class, $lockRows = false) {
		$this->_connect();
		
		$query = sqlite_query($this->_conn, $sql);
		
		if ($query === false) {
			return $this->_returnEntities(array(), $class);
		}
		
		$rows = sqlite_fetch_all($query);
		
		return $this->_returnEntities($rows, $class);
	}
	
	protected function _returnResultRow($sql, $class=null, $lockRows = false) {
		$this->_connect();
		
		if (!($query = sqlite_query($this->_conn, $sql))) {
			return null;
		}
		if (sqlite_num_rows($query) <= 0) {
			return null;
		}
		$row = sqlite_single_query($query, true);
		
		if (is_null($class)) {
			return $row;
		}
		
		return $this->_returnEntity($row, $class);
	}
	
	public function query($sql) {
		
	}
	
	public function lastInsertId() {
		return sqlite_last_insert_rowid($this->_conn);
	}
	
	public function getAdapterError() {
		return sqlite_error_string($this->_conn);
	}
	
	public function escape($value, $type=null) {
		$this->_connect();
		
		return sqlite_escape_string($this->_conn, $value);
	}
	
	protected function _whereRange($key, $values) {
		$where = "{$key} IN (".implode(",", $values).")";
		
		return $where;
	}
	
	public function startTransaction() {
		$this->_connect();
		return false;
	}
	
	public function commitTransaction() {
		return false;
	}
	
	public function rollbackTransaction() {
		return false;
	}
	
	protected function _quoteIdentifier($column) {
		$parts = explode('.', $column);
		if (count($parts) > 0) {
			$column = implode('.', $parts);
		}
		
		return "$column";
	}
	
	public function executeQueryObject($query) {
		$sql = $this->_queryToString($query);
		
		return $this->execute($sql);
	}
	
	public function execute($sql) {
		$this->_connect();
		
		$result = sqlite_query($this->_conn, $sql);
		
		if ($result === false) {
			return false;
		}
		
		if ($result === true) {
			return sqlite_changes($this->_conn);
		}
		
		return $result;
	}
	
	protected function _queryToString(SimDAL_Query $query) {
		$wheres = array();
		foreach ($query->getWheres() as $where) {
			$wheres[] = $this->_processWhere($where);
		}
		
		$limit = $this->_processQueryLimit($query->getLimit()->getLimit(), $query->getLimit()->getOffset(), $query);
		
		$columns = null;
		if ($query->getType() == SimDAL_Query::TYPE_SELECT) {
			$columns = $this->_processQueryPrimaryTableColumns($query);
		}
		$joins = $this->_processQueryJoins($query, $columns);
		
		$from = $query->getFrom();
		$schema = $query->getSchema();
		if (!is_null($schema) && !empty($schema)) {
			$from = $query->getSchema() . '.' . $from;
		}
		
		switch ($query->getType()) {
			case SimDAL_Query::TYPE_SELECT:
				$sql = 'SELECT ' . implode(', ', $columns) . ' ';
				$sql .= 'FROM ' . $from;
				$sql .= $joins;
				break;
			case SimDAL_Query::TYPE_DELETE:
				$sql = 'DELETE ';
				$sql .= 'FROM ' . $from;
				$sql .= $joins;
				break;
			case SimDAL_Query::TYPE_UPDATE:
				$sql = 'UPDATE ' . $from;
				$sql .= $joins;
				$sets = $this->_processWhereSets($query);
				$sql .= ' SET ' . implode(', ', $sets);
		}
		
		if (count($wheres) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $wheres);
		}
		
		$sql .= ' ' . $limit;
		
		return $sql;
		
	}
	
	protected function _processQueryPrimaryTableColumns($query) {
	    $columns = array();
	    $columns_array = $query->getColumns();
	    if (!$query->hasAliases() && count($columns_array) <= 0) {
	        $columns_array = $query->getTableColumns();
	    }
	    if (count($columns_array) > 0) {
	        /* @var $column SimDAL_Mapper_Column */
	        foreach ($columns_array as $alias=>$column) {
	        	if ($column instanceof SimDAL_Mapper_Column) {
			        $column_string = $column->getTable() . '.' . $column->getColumn();
			        if ($column->hasAlias()) {
			            $column_string .= ' AS ' . $column->getAlias();
			        }
	        	} else {
	        		$column_string = $column;
	        		if (!is_numeric($alias) && $alias != '') {
	        			$column_string .= ' AS ' . $alias;
	        		}
	        	}
		        $columns[] = $column_string;
	        }
	    } else {
	        $columns[] = $query->getFrom() . '.*';
	    }
	    
	    return $columns;
	}
	
	protected function _processQueryJoins($query, &$columns=null) {
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
		
		return $joins;
	}
	
	protected function _processWhereSets(SimDAL_Query $query) {
		$sets = array();
		
		/* @var $set SimDAL_Query_Set */
		foreach ($query->getSets() as $set) {
			$column = $set->getColumn();
			$value = $set->getValue();
			$set = $this->_processWhereColumn($column->getTable(), $column->getColumn());
			$set .= ' = ';
			if ($value instanceof SimDAL_Mapper_Entity) {
				$set .= $this->_processWhereColumn($value->getTable(), $value->getColumn());
			} else {
				$set .= $this->_transformData($column->getColumn(), $value, $column->getClass());
			}
			
			$sets[] = $set;
		}
		
		return $sets;
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
	
	protected function _processWhereValue($value, SimDAL_Query_Where_Interface $where) {
		if (is_object($value)) {
			$class = get_class($value);
			switch ($class) {
				case 'SimDAL_Mapper_Column': return $this->_processWhereColumn($value->getTable(), $value->getColumn(), $where); break;
				case 'SimDAL_Query_Where_Value_Range': return $this->_processWhereRange($value); break;
			}
		} else if (is_array($value)) {
			return $this->_processWhereArray($value);
		} else {
			return "'" . $this->escape($value) . "'";
		}
	}
	
	// @todo is @where needed here
	protected function _processWhereColumn($table, $column, $where=null) {
		return $this->_quoteIdentifier($table) . '.' . $this->_quoteIdentifier($column);
	}
	
	protected function _processWhereRange(SimDAL_Query_Where_Value_Range $range) {
		$value1 = $this->_processWhereValue($range->getValue1());
		$value2 = $this->_processWhereValue($range->getValue2());
		
		return $value1 . ' AND ' . $value2;
	}
	
	protected function _processWhereArray(array $value) {
		
	}
	
	protected function _processWhereOperator($operator) {
		return $operator;
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