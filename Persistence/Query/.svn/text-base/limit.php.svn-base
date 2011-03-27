<?php

class SimDAL_Persistence_Query_Limit {
	
	protected $_limit;
	protected $_offset = null;
	
	public function __construct($limit, $offset = null) {
		if (!is_int($limit) || ($offset && !is_int($offset) ) )
			throw new Exception('Limit or Offset cannot be a non integer');
		
		$this->_limit = $limit;
		
		if ($offset) {
			$this->_offset = $offset;
		}
	}
	
	public function __toString() {
		if ($this->_offset == null) {
			return "LIMIT " . $this->_limit;
		} else {
			return "LIMIT " . $this->_offset . ", " . $this->_limit;
		}
	}
	
}