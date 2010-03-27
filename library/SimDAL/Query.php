<?php

class SimDAL_Query {
	/**
	 * 
	 * @var SimDAL_Mapper_Entity
	 */
	protected $_from;
	protected $_where = array();
	protected $_join = array();
	
	public function from($entity) {
		$this->_from = $entity;
	}
	
	public function whereIdIs($id) {
		$this->_where[] = new SimDAL_Query_Where_Id($this->_from, $id);
	}
	
	public function join($join) {
		if ($join instanceof SimDAL_Mapper_Descendant) {
			$this->_join[] = new SimDAL_Query_Join_Descendant($descendant);
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