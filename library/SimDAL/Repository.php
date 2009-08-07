<?php

class SimDAL_Repository {

	protected $_dao = null;
	
	public function __construct($dao = null) {
		if ($dao !== null && !$dao instanceof Zend_Db_Table) {
			throw new Exception("Argument 'dao' should be of type Zend_Db_Table");
		} else if ($dao instanceof Zend_Db_Table) {
			$this->_dao = $dao;
			return;
		}
		
		if (!is_string($this->_dao) || !class_exists($this->_dao)) {
			throw new Exception("property '_dao' should be a name of or an object of Zend_Db_Table");
		}
		
		if (is_string($this->_dao)) {
			$class = $this->_dao;
			$dao = new $class();
			if (!$dao instanceof Zend_Db_Table_Abstract) {
				throw new Exception("class name in property '_dao' is not of type Zend_Db_Table_Abstract");
			}
			$this->_dao = $dao;
		}
	}
	
	public function save($entity) {
		if ($entity->id === null) {
			$row = $this->_dao->fetchNew();
		} else {
			$row = $this->_dao->find($entity->id)->current();
		}
		
		$row->setFromArray($entity->toArray());
		$row->save();
		
		$entity->setFromArray($row->toArray());
		
		return $entity;
	}
	
}