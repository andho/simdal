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

class SimDAL_Persistence_Query_Condition {
	
	protected $_column = null;
	protected $_value = null;
	protected $_operator = '=';
	protected $_logical = 'AND';
	protected $_trim;
	
	public function __construct($column, $value = null, $operator = '=', $logical = 'AND') {
		if (preg_match('/(.+?)(IN|BETWEEN|>|<|<=|>=|=)(.+)/', $column, $matches)) {
			$this->_column = "`".$matches[1]."`";
			$this->_operator = $matches[2];
			$this->_value = $matches[3];
			$this->_logical = $logical;

			$this->_trim = strlen($logical) + 2;

			return true;
		}

		$this->_column = $column;
		$this->_value = $value;
		
		if ($value === 'NULL') {
			if ($operator == '=')
				$this->_operator = 'IS';
			if ($operator == '!=')
				$this->_operator = 'IS NOT';
		}
		else {
			$this->_operator = $operator;
		}
		
		$this->_logical = $logical;
		
		$this->_trim = strlen($logical) + 2;
	}
	
	public function getTrimValue() {
		return $this->_trim;
	}
	
	public function getLogicalOperator() {
		return $this->_logical;
	}
	
	public function __toString() {
		return $this->_column . ' ' . $this->_operator . ' ' . $this->_value . ' ' . $this->_logical . ' ';
	}
	
}