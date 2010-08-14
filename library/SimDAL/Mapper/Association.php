<?php

class SimDAL_Mapper_Association {
	
	protected $_entity;
	protected $_type;
	protected $_class;
	protected $_foreignKey;
	protected $_parentKey;
	protected $_method;
	protected $_parentMethod;
	protected $_isParentAssociation;
	
	public function __construct(SimDAL_Mapper_Entity $entity, $data) {
		$this->_entity = $entity;
		$this->_type = $data[0];
		$this->_class = $data[1];
		$this->_foreignKey = $data[2]['fk'];
		$this->_parentKey = isset($data[2]['key']) ? $data[2]['key'] : $this->_entity->getPrimaryKey();
		$this->_method = isset($data[2]['method']) ? $data[2]['method'] : $this->_getDefaultMethod();
		$this->_parentMethod = isset($data[2]['parentMethod']) ? $data[2]['parentMethod'] : null;
		$this->_isParentAssociation = $this->_determineIfParentAssociation($data);
	}
	
	public function getIdentifier() {
		return $this->getMethod();
	}
	
	public function getMethod() {
		return ucfirst($this->_method);
	}
	
	public function getProperty() {
		$property = $this->getMethod();
		$property = strtolower(substr($property, 0, 1)) . substr($property, 1);
		
		return $property;
	}
	
	public function getParentMethod() {
		return $this->_parentMethod;
	}
	
	public function getType() {
		return $this->_type;
	}
	
	public function getForeignKey() {
		return $this->_foreignKey;
	}
	
	public function getParentKey() {
		return $this->_parentKey;
	}
	
	public function getClass() {
		return $this->_class;
	}
	
	public function isParent() {
		return $this->_isParentAssociation;
	}
	
	public function isDependent() {
		return !$this->_isParentAssociation;
	}
	
	/**
	 * @return SimDAL_Mapper_Association
	 */
	public function getMatchingAssociationFromAssociationClass() {
		$foreignKey = $this->getForeignKey();
		$parentKey = $this->getParentKey();
		
		$othersidemapping = $this->getMapping()->getMapper()->getMappingForEntityClass($this->getClass());
		/* @var $otherside_association SimDAL_Mapper_Association */
		foreach ($othersidemapping->getAssociations() as $otherside_association) {
			if ($otherside_association->getClass() == $this->getMapping()->getClass()) {
				if ( ($this->getType() == 'one-to-many' && $otherside_association->getType() == 'many-to-one') ||
					($this->getType() == 'many-to-one' && $otherside_association->getType() == 'one-to-many') ) {
					if ($foreignKey == $otherside_association->getForeignKey() && $parentKey == $otherside_association->getParentKey()) {
						return $otherside_association;
					}
				}
			}
		}
	}
	
	/**
	 * @return SimDAL_Mapper_Entity
	 */
	public function getMapping() {
		return $this->_entity;
	}
	
	protected function _getDefaultMethod() {
		$method = $this->_class;
		
		if ($this->getType() == 'one-to-many') {
			$method = $method . 's';
		}
		
		return $method;
	}
	
	protected function _determineIfParentAssociation($data) {
		if ($data[0] === 'one-to-one') {
			if (isset($data[2]) && isset($data[2]['parent']) && $data[2]['parent'] === true) {
				return true;
			}
		}
		
		if ($data[0] === 'one-to-many') {
			return true;
		}
		
		return false;
	}
	
}