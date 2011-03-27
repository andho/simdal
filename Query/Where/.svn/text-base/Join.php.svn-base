<?php

class SimDAL_Query_Where_JoinDescendant {
	
	/**
	 * 
	 * @var SimDAL_Mapper_Entity
	 */
	protected $_entity;
	/**
	 * 
	 * @var SimDAL_Mapper_Descendant
	 */
	protected $_descendant;
	
	public function __construct($entity, $descendant) {
		$this->_entity = $entity;
		$this->_descendant = $descendant;
	}
	
	public function getLeftValue() {
		return new SimDAL_Query_Where_Column($this->_descendant->getTable(), $this->_descendant->getColumn($this->_descendant->getForeignKey()));
	}
	
	public function getRightValue() {
		return new SimDAL_Query_Where_Column($this->_entity->getTable(), $this->_entity->getColumn($this->_descendant->getParentKey()));
	}
	
}