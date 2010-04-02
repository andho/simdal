<?php

class SimDAL_Mapper_Descendent extends SimDAL_Mapper_Entity {
	
    protected $_entity;
	protected $_parentKey;
	protected $_foreignKey;
	
	public function __construct(SimDAL_Mapper_Entity $entity, $class, $data) {
	  $this->_entity = $entity;
	  $this->_parentKey = $data['parentKey'];
	  $this->_foreignKey = $data['foreignKey'];
	  parent::__construct($class, $data);
	}
	
	public function getParentKey() {
		return $this->_parentKey;
	}
	
	public function getForeignKey() {
		return $this->_foreignKey;
	}
	
	public function getIdentifier() {
	  return $this->_class;
	}
	
	/**
	 * @var SimDAL_Mapper_Entity
	 */
	public function getEntity() {
	  return $this->_entity;
	}
	
}