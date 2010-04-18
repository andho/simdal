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
	protected $_limit;
	protected $_parent;
	
	public function __construct($parent=null) {
		$this->_parent = $parent;
		$this->_limit = new SimDAL_Query_Limit(1, 0, $this);
	}
	
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
		
		return $this;
	}
	
	/**
	 * 
	 * @return SimDAL_Query_Where_Column
	 */
	public function whereColumn($column) {
		$column = $this->_from->getColumn($column);
		$where = new SimDAL_Query_Where_Column($this->_from, $column, $this);
		$this->_where[] = $where;
		
		return $where;
	}
	
	public function join($join) {
		if ($join instanceof SimDAL_Mapper_Descendent) {
			$this->_join[] = new SimDAL_Query_Join_Descendent($join);
		}
	}
	
	public function limit($limit=null, $offset=null) {
		 if (is_null($limit) && $offset == null) {
		 	return $this->_limit->getLimit();
		 }
		 
		 $this->_limit->setLimit($limit);
		 
		 if (!is_null($limit)) {
		 	$this->_limit->setOffset($offset);
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
	
	public function getClass() {
		return $this->_from->getClass();
	}
	
	/**
	 * @return SimDAL_Query_Limit
	 */
	public function getLimit() {
		return $this->_limit;
	}
	
	public function fetch($limit=null, $offset=null) {
		if (method_exists($this->_parent, 'fetch')) {
			return $this->_parent->fetch($this, $limit, $offset);
		}
		
		return false;
	}
	
}