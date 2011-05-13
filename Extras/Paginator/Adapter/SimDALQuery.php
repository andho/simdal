<?php

class SimDAL_Extras_Paginator_Adapter_SimDALQuery implements Zend_Paginator_Adapter_Interface {
	
	protected $_query;
	
	public function __construct(SimDAL_Query $query) {
		$this->_query = $query;
	}
	
	public function count() {
		$query = clone($this->_query);
		$row = $query->count();
		
		return $row['count'];
	}
	
	public function getItems($offset, $itemCountPerPage) {
		$query = clone($this->_query);
		return $query->fetch($itemCountPerPage, $offset);
	}
	
}
