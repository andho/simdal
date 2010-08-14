<?php

class SimDAL_Mapper_Descendent extends SimDAL_Mapper_Entity {
	
	const TYPE_NORMAL = 'normal';
	
    protected $_entity;
	protected $_parentKey;
	protected $_foreignKey;
	protected $_type;
	
	public function __construct(SimDAL_Mapper_Entity $entity, $class, $data) {
	  $this->_entity = $entity;
	  $this->_parentKey = isset($data['parentKey'])?$data['parentKey']:'';
	  $this->_foreignKey = isset($data['foreignKey'])?$data['foreignKey']:'';
	  if (!isset($data['type'])) {
	  	throw new Exception("Descendent type not given");
	  }
	  $this->_type = $data['type'];
	  parent::__construct($class, $data, $entity->getMapper());
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
	
	public function getType() {
		return $this->_type;
	}
	
	public function getFullClassName() {
		return $this->getEntity()->getDescendentPrefix() . $this->getClass();
	}
	
}