<?php

class SimDAL_Mapper_Association {
	
	protected $_entity;
	protected $_type;
	protected $_class;
	protected $_foreignKey;
	protected $_parentKey;
	protected $_method;
	protected $_parentMethod;
	
	public function __construct(SimDAL_Mapper_Entity $entity, $data) {
		$this->_entity = $entity;
		$this->_type = $data[0];
		$this->_class = $data[1];
		$this->_foreignKey = $data[2]['fk'];
		$this->_parentKey = isset($data[2]['key']) ? $data[2]['key'] : $this->_entity->getPrimaryKey();
		$this->_method = isset($data[2]['method']) ? $data[2]['method'] : $this->_getDefaultMethod();
		$this->_parentMethod = isset($data[2]['parentMethod']) ? $data[2]['parentMethod'] : null;
	}
	
	public function getIdentifier() {
		return $this->getMethod();
	}
	
	public function getMethod() {
		return $this->_method;
	}
	
	public function getProperty() {
		$property = $this->getMethod;
		$property = strtolower(substr($property, 0, 1)) . substr($property, 1);
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
	
	protected function _getDefaultMethod() {
		$method = $this->_class;
		
		if ($this->getType() == 'one-to-many') {
			$method = $method . 's';
		}
		
		return $method;
	}
	
}