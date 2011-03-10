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

class SimDAL_Query_Where_Column implements SimDAL_Query_Where_Interface {
	
	protected $_query;
	protected $_entity;
	protected $_column;
	protected $_value;
	protected $_comparison = '=';
	
	public function __construct(SimDAL_Mapper_Entity $entity, SimDAL_Mapper_Column $column, SimDAL_Query $query) {
		$this->_query = $query;
		$this->_entity = $entity;
		$this->_column = $column;
	}
	
	public function __call($method, $args) {
		if (method_exists($this->_query, $method)) {
			return call_user_func_array(array($this->_query, $method), $args);
		}
		
		return false;
	}
	
	public function getLeftValue() {
		return $this->_column;
	}
	
	public function getRightValue() {
		return $this->_value;
	}
	
	public function getOperator() {
		return $this->_comparison;
	}
	
	/**
	 * 
	 * @return SimDAL_Query
	 */
	public function equals($value) {
		$this->_comparison = '=';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value
	 * @return SimDAL_Query
	 */
	public function isEqualTo($value) {
		return $this->equals($value);
	}
	
	/**
	 * 
	 * @param string $value
	 * @return SimDAL_Query
	 */
	public function isNotEqualTo($value) {
		$this->_comparison = '!=';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value
	 * @return SimDAL_Query
	 */
	public function isLike($value) {
		$this->_comparison = 'LIKE';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value
	 * @return SimDAL_Query
	 */
	public function isGreaterThan($value) {
		$this->_comparison = '>';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value
	 * @return SimDAL_Query
	 */
	public function isGreaterThanOrEqualTo($value) {
		$this->_comparison = '>=';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value
	 * @return SimDAL_Query
	 */
	public function isLessThan($value) {
		$this->_comparison = '<';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value
	 * @return SimDAL_Query
	 */
	public function isLessThanOrEqualTo($value) {
		$this->_comparison = '<=';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value1
	 * @param string $value2
	 * @return SimDAL_Query
	 */
	public function isBetween($value1, $value2) {
		$this->_comparison = 'BETWEEN';
		$this->_value = new SimDAL_Query_Where_Value_Range($value1, $value2);
		
		return $this->_query;
	}
	
	/**
	 * 
	 * @param string $value1
	 * @param string $value2
	 * @return SimDAL_Query
	 */
	public function isInRange($value1, $value2) {
		return $this->isBetween($value1, $value2);
	}
	
	/**
	 * 
	 * @param array $value
	 * @return SimDAL_Query
	 */
	public function isIn(array $value) {
		$this->_comparison = 'IN';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	public function getProcessMethod() {
		return 'WhereColumn';
	}
	
	public function __toString() {
		$string = $this->_column->getProperty();
		$string .= $this->_comparison;
		$string .= $this->_value;
		
		return $string;
	}
	
}