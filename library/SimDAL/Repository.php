<?php

class SimDAL_Repository {
	
	static protected $_defaultAdapter = null;
	
	protected $_adapter = null;
	
	protected $_table = null;
	
	protected $_class = 'TestDomain_Project';
	
	protected $_new = array();
	
	protected $_loaded = array();
	
	protected $_delete = array();
	
	protected $_cleanData = array();
	
	public function __construct($adapter=null) {
		if ($adapter instanceof SimDAL_Persistence_AdapterInterface) {
			$this->_adapter = $adapter;
		} else if (self::$_defaultAdapter instanceof SimDAL_Persistence_AdapterInterface) {
			$this->_adapter = self::$_defaultAdapter;
		} else {
			throw new Simdal_PersistenceAdapterIsNotSetException();
		}
	}
	
	public function add($entity) {
		if (in_array($entity, $this->_new) ) {
			return false;
		}
		
		$this->_new[] = $entity;
		
		return true;
	}
	
	public function delete($entity) {
		if (is_object($entity)) {
			$this->_delete[] = $entity->id;
		} else {
			$this->_delete[] = $entity;
		}
	}
	
	public function getNew() {
		return $this->_new;
	}
	
	public function findById($id) {
		$entity = $this->_getFromLoaded($id);
		if (!is_null($entity)) {
			$this->_loaded[$entity->id] = $entity;
			return $entity;
		}
		
		$array = $this->_adapter->findById($this->_table, $id);
		if (is_null($array)) {
			return null;
		}
		
		$class = $this->_class;
		
		$entity = new $class();
		foreach ($array as $key=>$value) {
			$entity->$key = $value;
		}
		
		$this->_loaded[$entity->id] = $entity;
		$this->_cleanData[$array['id']] = $array;
		
		return $entity;
	}
	
	protected function _getFromLoaded($id) {
		if ($this->_isLoaded($id)) {
			return $this->_loaded[$id];
		}
		
		return null;
	}
	
	protected function _isLoaded($id) {
		if (!array_key_exists($id, $this->_loaded)) {
			return false;
		}
		
		return true;
	}
	
	public function getChanges() {
		$array = array();
		
		foreach ($this->_loaded as $id=>$obj) {
			if (!array_key_exists($id, $this->_cleanData)) {
				continue;
			}
			
			foreach ($this->_cleanData[$id] as $key=>$value) {
				if ($value !== $obj->$key) {
					$array[$id][$key] = $obj->$key;
				}
			}
		}
		
		return $array;
	}
	
	public function getDeleted() {
		return $this->_delete;
	}
	
	public function revert($entity) {
		if (array_key_exists($entity->id, $this->_delete)) {
			unset($this->_delete[$entity->id]);
		}
		
		if (!array_key_exists($entity->id, $this->_cleanData)) {
			return;
		}
		
		foreach ($this->_cleanData[$entity->id] as $key=>$value) {
			$entity->$key = $value;
		}
	}
	
}