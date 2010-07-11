<?php

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
	
}