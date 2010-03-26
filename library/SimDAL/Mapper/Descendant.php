<?php

class SimDAL_Mapper_Descendant extends SimDAL_Mapper_Entity {
	
	protected $_parentKey;
	protected $_foreignKey;
	
	public function getParentKey() {
		return $this->_parentKey;
	}
	
	public function getForeignKey() {
		return $this->_foreignKey;
	}
	
}