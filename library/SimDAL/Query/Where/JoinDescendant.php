<?php

class SimDAL_Query_Where_JoinDescendant {
	
	/**
	 * 
	 * @var SimDAL_Mapper_Entity
	 */
	protected $_entity;
	/**
	 * @var SimDAL_Mapper_Descendent
	 */
	protected $_descendent;
	
	public function __construct(SimDAL_Mapper_Entity $entity, SimDAL_Mapper_Descendent $descendant) {
		$this->_entity = $entity;
		$this->_descendent = $descendant;
	}
	
	public function getLeftValue() {
	    return $this->_descendent->getColumn($this->_descendent->getForeignKey());
		//return new SimDAL_Query_Where_Column($this->_descendant->getTable(), $this->_descendant->getColumn($this->_descendant->getForeignKey()));
	}
	
	public function getRightValue() {
	    return $this->_entity->getColumn($this->_descendent->getParentKey());
		//return new SimDAL_Query_Where_Column($this->_entity->getTable(), $this->_entity->getColumn($this->_descendant->getParentKey()));
	}
	
	public function getProcessMethod() {
		return 'WhereJoinDescendent';
	}
	
	public function getOperator() {
		return '=';
	}
	
}