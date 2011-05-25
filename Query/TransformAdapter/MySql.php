<?php

class SimDAL_Query_TransformAdapter_Mysql implements SimDAL_Query_TransformAdapter_Abstract {
	
	public function queryToString(SimDAL_Query $query) {
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
		
		if ($query->getOrderBy() !== null) {
			$sql .= ' ' . $this->_processOrderBy($query->getOrderBy(), $query);
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
	
	protected function _processWhere(SimDAL_Query_Where_Interface $where) {
		if ($where instanceof SimDAL_Query_Where_Collection) {
			//@todo do whatever is needed
		}
		$left = $this->_processWhereValue($where->getLeftValue(), $where);
		$right = $this->_processWhereValue($where->getRightValue(), $where);
		
		$operator = $this->_processWhereOperator($where->getOperator());
		
		return $left . ' ' . $operator . ' ' . $right;
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
				$set .= $this->_transformData($column, $value, $column->getEntity());
			}
			
			$sets[] = $set;
		}
		
		return $sets;
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
		} else if (is_null($value)) {
			return 'NULL';
		} else {
			return "'" . $this->escape($value) . "'";
		}
	}
	
	protected function _processWhereColumn($table, $column, $where=null) {
		return $this->_quoteIdentifier($table) . '.' . $this->_quoteIdentifier($column);
	}
	
	protected function _processWhereRange(SimDAL_Query_Where_Value_Range $range) {
		$value1 = $this->_processWhereValue($range->getValue1());
		$value2 = $this->_processWhereValue($range->getValue2());
		
		return $value1 . ' AND ' . $value2;
	}
	
	protected function _processWhereArray(array $value) {
		$value = '(\'' . implode('\',\'', $value) . '\')';
		
		return $value;
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
	
	protected function _processOrderBy(SimDAL_Query_OrderBy $order_by, SimDAL_Query $query) {
		$output = ' ORDER BY ';
		$column = $order_by->getValue();
		$type = $order_by->getType();
		
		$output .= $this->_processWhereColumn($column->getTable(), $column->getColumn()) . ' ';
		switch ($type) {
			case 'descending': $output .= 'DESC'; break;
			default: $output .= 'ASC'; break;
		}
		
		return $output;
	}
	
}