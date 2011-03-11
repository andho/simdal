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

class SimDAL_Persistence_Query {
	
	static protected $_defaultAdapter = null;

	protected $_from = null;
	protected $_columns = null;
	protected $_sets = null;
	protected $_joins = null;
	protected $_conditions = null;
	protected $_having = null;
	protected $_order_by = null;
	protected $_group_by = null;
	protected $_asc = true;
	protected $_limit = null;
	protected $_adapter = null;
	
	static public function setDefaultAdapter(SimDAL_Persistence_AdapterAbstract $adapter) {
		self::$_defaultAdapter = $adapter;
	}
	
	public function __construct(SimDAL_Persistence_AdapterAbstract $adapter=null) {
		if ($adapter instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = $adapter;
		} else if (self::$_defaultAdapter instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = self::$_defaultAdapter;
		} else {
			throw new Exception("No default persistence adapter was set for Query");
		}
	}
	
	public function getAdapter() {
		return $this->_adapter;
	}

	public function from ($table, $columns = '*') {
		if (!is_array($table)) {
			$table = array($table);
		}
		foreach ($table as $key=>$value) {
			if (is_int($key)) {
				$this->_from = array('tablename'=>$value, 'alias'=>null);
				$tablename = $value;
			}
			else {
				$this->_from = array('tablename'=>$key, 'alias'=>$value);
				$tablename = $key;
			}
		}

		if ($columns != '*') {
			if (!is_array($columns)) $columns = array($columns);
			foreach ($columns as $key=>$value) {
				if (is_numeric($key)) {
					if (is_array($value) && count($value) > 1) {
						$this->_columns[] = array('column'=>$value[0], 'alias'=>$value[1], 'tablename'=>$tablename);
					}
					else {
						$this->_columns[] = array('column'=>$value, 'alias'=>null, 'tablename'=>$tablename);
					}
				}
				else {
					$this->_columns[] = array('column'=>$key, 'alias'=>$value, 'tablename'=>$tablename);
				}
			}
		} else {
			$this->_columns[] = array('column'=>'*', 'alias'=>null, 'tablename'=>$tablename);
		}

		return $this;
	}

	public function columns($columns) {
		if (!is_array($columns)) $columns = array($columns);
		foreach ($columns as $key=>$value) {
			if (is_numeric($key)) {
				if (is_array($value) && count($value) > 1) {
					$tablename = isset($value[2]) ? $value[2] : $this->_table->getTableName();
					$this->_columns[] = array('column'=>$value[0], 'alias'=>$value[1], 'tablename'=>$tablename);
				}
				else {
					$this->_columns[] = array('column'=>$value, 'alias'=>null, 'tablename'=>$this->_table->getTableName());
				}
			}
			else {
				$this->_columns[] = array('column'=>$value, 'alias'=>$key, 'tablename'=>$this->_table->getTableName());
			}
		}

		return $this;
	}
	
	public function join ($table, $conditions, $columns='*') {
		$this->_joins[] = new SimDAL_Persistence_Query_Join($table, $conditions);
		if (is_array($columns)) {
			
		} else {
			$this->_columns[] = array('column'=>'*', 'alias'=>null, 'tablename'=>$table);
		}

		return $this;
	}

	public function where ($column, $value = null, $valuealt = '=', $logical = 'AND') {
		if ($this->_conditions === null) {
			$this->_conditions = new SimDAL_Persistence_Query_ConditionSet();
		}

		if ($column instanceof SimDAL_Persistence_Query_Condition Or $column instanceof SimDAL_Persistence_Query_ConditionSet ) {
			$this->_conditions->where($column);

			return $this;
		}

		$adapter = $this->getAdapter();
		$value = $adapter->escape($value);
		$this->_conditions->addKeyValue($column, $value, $valuealt, $logical);

		return $this;
	}

	/*public function orderBy($order_by, $asc = true) {
		$this->_order_by = new Db_Expr($order_by, $this->_table);
		$this->_asc = $asc;

		return $this;
	}*/

	public function limit($limit, $offset = null) {
		$this->_limit = new SimDAL_Persistence_Query_Limit($limit, $offset);

		return $this;
	}

	public function groupBy($columns) {
		if (!is_array($columns)) $columns = array($columns);
		foreach ($columns as $column) {
			$this->_group_by[] = $column;
		}

		return $this;
	}

	public function having ($column, $value = null, $valuealt = '=', $logical = 'AND') {
		if ($this->_having === null) {
			$this->_having = new SimDAL_Persistence_Query_ConditionSet();
		}

		$adapter = $this->_table->getAdapter();
		$value = $adapter->escape($value);
		$this->_having->addKeyValue($column, $value, $valuealt, $logical);

		return $this;
	}

	public function evaluateColumn($column, $tablename) {
		if (preg_match("/^\((.+)\)$/", $column, $matches)) {
			return "(" . $this->evaluateColumn($matches[1], $tablename) . ")";
		}
		else if (preg_match("/^(select)/i", $column, $matches)) {
			return $column;
		}
		else if (preg_match("/^(.*?)\(([^)]*?)\)$/", $column, $matches)) {
			return $matches[1] . "(" . $this->evaluateColumn($matches[2], $tablename) . ")";
		}
		else if ($column != '*' && preg_match("/^(.*?)([-+\/*])(.*?)$/", $column, $matches)) {
			return $this->evaluateColumn(trim($matches[1]), $tablename) . " " . $matches[2] . " " . $this->evaluateColumn(trim($matches[3]), $tablename);
		}
		else {
			return "`".$tablename."`" . '.' . ($column!="*" ? "`".$column."`" : $column);
		}
	}

	public function __toString() {
		$sql = "SELECT ";

		// COLUMNS
		if (is_array($this->_columns) && count($this->_columns) > 0) {
			foreach ($this->_columns as $column) {
				if (is_array($column) && count($column) ==3 && $column['alias'] != null) {
					$sql .= $this->evaluateColumn($column['column'], $column['tablename']) . " AS `" . $column['alias'] . "`, ";
				} else if (is_array($column) && count($column) == 3 && $column['alias'] == null) {
					$sql .= $this->evaluateColumn($column['column'], $column['tablename']) . ", ";
				}
			}
			$sql = substr($sql,0,-2);
		} else {
			$sql .= "*";
		}

		if (!$this->_from) {
			$this->_defaultFrom();
		}

		$sql .= " FROM `" . $this->_from['tablename'] . "`" . ($this->_from['alias']!=null ? " AS `" . $this->_from['alias'] . "`" : '' );

		// JOINS
		if (is_array($this->_joins) && count($this->_joins) > 0) {
			$sql .= " ";
			foreach ($this->_joins as $join) {
				$sql .= $join->__toString();
			}
		}

		// CONDITIONS
		if ($this->_conditions !== null && $this->_conditions->isAny() ) {
			$sql .= " WHERE ";
			$sql .= $this->_conditions->__toString();
		}

		// GROUP BY
		if ($this->_group_by !== null) {
			$sql .= " GROUP BY ";

			foreach ($this->_group_by as $column) {
				$sql .= "`" . $column . "`, ";
			}

			$sql = substr($sql,0,-2);
		}

		// HAVING
		if ($this->_having !== null && $this->_having->isAny() ) {
			$sql .= " HAVING ";
			$sql .= $this->_having->__toString();
		}

		// ORDER BY
		if ($this->_order_by !== null)
			$sql .= " ORDER BY " . $this->_order_by->__toString() . ($this->_asc === false ? ' DESC' : '');

		// LIMIT
		if ($this->_limit !== null) {
			$sql .= " " . $this->_limit->__toString();
		}

		return $sql;
	}

	public function conditionsToString() {
		return $this->_conditions->__toString();
	}

}