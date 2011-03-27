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

class SimDAL_Persistence_Query_ConditionSet {

	protected $_conditions = array();
	protected $_logical = 'AND';

	public function addKeyValue($column, $value = null, $operator = '=', $logical = 'AND') {
		if (!($column instanceof SimDAL_Persistence_Query_Condition OR $column instanceof SimDAL_Persistence_Query_ConditionSet )) {
			$condition = new SimDAL_Persistence_Query_Condition($column, $value, $operator, $logical);
		} else {
			$condition = $column;
		}

		if ($condition instanceof SimDAL_Persistence_Query_Condition ) {
			$this->_logical = $condition->getLogicalOperator();
		}

		$this->_conditions[] =& $condition;

		return $condition;
	}

	public function isAny() {
		if ( is_array($this->_conditions) && count($this->_conditions) > 0) {
			return true;
		}

		return false;
	}

	public function where ($column, $value = null, $valuealt = '=', $logical = 'AND') {

		$this->addKeyValue($column, $value, $valuealt, $logical);

		return $this;
	}

	public function getLogicalOperator() {
		return $this->_logical;
	}

	public function __toString() {
		$sql = '';
		if (is_array($this->_conditions) && count($this->_conditions) > 0) {
			foreach ($this->_conditions as $condition) {
				if ($condition instanceof SimDAL_Persistence_Query_Condition ) {
					$sql .= $condition->__toString();
					$trim = $condition->getTrimValue();
				}
				else if ($condition instanceof SimDAL_Persistence_Query_ConditionSet ) {
					$sql .= '(' . $condition->__toString() . ') ' . $condition->getLogicalOperator() . ' ';
					$trim = strlen($this->_logical) + 2;
				}
			}
			$sql = substr($sql, 0, -($trim));
		}

		return $sql;
	}

}