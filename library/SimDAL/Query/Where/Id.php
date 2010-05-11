<?php

class SimDAL_Query_Where_Id implements SimDAL_Query_Where_Interface {
	
	/**
	 * @var SimDAL_Mapper_Entity
	 */
	protected $_entity;
	protected $_id;
	
	public function __construct($entity, $id) {
		$this->_entity = $entity;
		$this->_id = $id;
	}
	
	public function getLeftValue() {
		return $this->_entity->getPrimaryKeyColumn();
	}
	
	public function getRightValue() {
		return $this->_id;
	}
	
	public function getProcessMethod() {
		return 'WhereId';
	}
	
	public function getOperator() {
		return '=';
	}
	
}