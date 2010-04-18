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
	
	public function like($value) {
		$this->_comparison = 'LIKE';
		$this->_value = $value;
		
		return $this->_query;
	}
	
	public function getProcessMethod() {
		return 'WhereColumn';
	}
	
}