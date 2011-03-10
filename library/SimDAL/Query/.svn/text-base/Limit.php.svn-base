<?php

class SimDAL_Query_Limit {
	
	protected $_query;
	protected $_limit;
	protected $_offset;
	
	public function __construct($limit, $offset=null, $query=null) {
		$this->_query = $query;
		$this->_limit = $limit;
		$this->_offset = $offset;
	}
	
	public function __call($method, $args) {
		if (method_exists($this->_query, $method)) {
			return call_user_func_array(array($this->_query, $method), $args);
		}
		
		return false;
	}
	
	public function setLimit($limit) {
		if (is_null($limit)) {
			$this->_limit = null;
			$this->_offset = null;
		}
		if (is_numeric($limit) && $limit > 0) {
			$this->_limit = $limit;
		}
	}
	
	public function setOffset($offset) {
		if (is_numeric($offset) && $offset >= 0) {
			$this->_offset = $offset;
		}
	}
	
	public function getLimit() {
		return $this->_limit;
	}
	
	public function getOffset() {
		return $this->_offset;
	}
	
}