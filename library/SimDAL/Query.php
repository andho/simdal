<?php

class SimDAL_Query {
	/**
	 * 
	 * @var SimDAL_Mapper_Entity
	 */
	protected $_from;
	protected $_where = array();
	protected $_join = array();
	protected $_columns = array();
	
	public function from($entity, array $columns = array()) {
	    $this->_columns = $columns;
		$this->_from = $entity;
	}
	
	public function hasAliases() {
	    return $this->_from->hasAliases();
	}
	
	public function getColumns() {
	  return $this->_columns;
	}
	
	public function getTableColumns() {
	    $this->_from->getColumns();
	}
	
	public function whereIdIs($id) {
		$this->_where[] = new SimDAL_Query_Where_Id($this->_from, $id);
	}
	
	public function join($join) {
		if ($join instanceof SimDAL_Mapper_Descendent) {
			$this->_join[] = new SimDAL_Query_Join_Descendent($join);
		}
	}
	
	public function getFrom() {
		return $this->_from->getTable();
	}
	
	public function getJoins() {
		return $this->_join;
	}
	
	public function getWheres() {
		return $this->_where;
	}
	
}