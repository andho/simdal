<?php

class SimDAL_UnitOfWork {
	
	static protected $_defaultMapper;
	
	/**
	 * Mapper
	 *
	 * @var SimDAL_Mapper
	 */
	private $_mapper = null;
	
	private $_new = array();
	private $_modified = array();
	private $_actual = array();
	private $_delete = array();
	private $_newkey = 1;
	
	public function __construct($mapper=null) {		
		if ($mapper instanceof SimDAL_Mapper) {
			$this->_mapper = $mapper;
		} else if (self::$_defaultMapper instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultMapper;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
	}
	
	public function add($entity) {
		if (!$this->_entityIsNew($entity)) {
			return false;
		}
		
		$class = $this->_getClass($entity);
		$table = $this->_mapper->getTable($class);
		
		if (!array_key_exists($class, $this->_new) || !is_array($this->_new[$class])) {
			$this->_new[$class] = array();
		}
		
		if (in_array($entity, $this->_new[$class])) {
			return false;
		}
		
		$pk = $this->_mapper->getPrimaryKey($class);
		$column = $this->_mapper->getColumn($class, $pk);
		if ($column[2]['autoIncrement'] === true) {
			$entity->$pk = 'autoincrement'.$this->_newkey++;
		}
		
		$this->_new[$class][$entity->$pk] = $entity;
	}
	
	public function getNew() {
		return $this->_new;
	}
	
	protected function _entityIsNew($entity) {
		return true;
	}
	
	public function updateCleanEntity($entity) {
		$copy = clone($entity);
		
		$this->update($entity, $copy);
	}
	
	public function update($entity, $actual_data) {
		/*if ($this->_entityIsNew($entity)) {
			return false;
		}*/
		
		$class = $this->_getClass($entity);
		$table = $this->_mapper->getTable($class);
		
		if (!array_key_exists($class, $this->_modified)) {
			$this->_modified[$class] = array();
			$this->_actual[$class] = array();
		}
		
		$this->_modified[$class][$entity->id] = $entity;
		$this->_actual[$class][$entity->id] = $actual_data;
	}
	
	public function getChanges($entity=null) {
		if (is_null($entity)) {
			return $this->_modified;
		}
		
		$data = array();
		
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$pk = $this->_getMapper()->getPrimaryKey($class);
		
		if (!is_object($this->_actual[$class][$entity->$pk])) {
			return $data;
		}
		
		foreach ($this->_actual[$class][$entity->$pk] as $key=>$value) {
			if ($entity->$key == $value) {
				continue;
			}
			
			$data[$key] = $entity->$key;
		}
		
		return $data;
	}
	
	public function delete($entity, $class=null, $column=null) {
		if (is_object($entity)) {
			$class = $this->_getClass($entity);
			$table = $this->_mapper->getTable($class);
			
			$this->_delete[$class][$entity->id] = $entity;
		} else if (!is_null($column)) {
			$this->_delete[$class][$column][] = $entity;
		} else {
			if (is_null($class)) {
				return false;
			}
			$this->_delete[$class][$entity] = $entity;
		}
	}
	
	public function getDeleted() {
		return $this->_delete;
	}
	
	public function isLoaded($class, $id) {
		if (!array_key_exists($class, $this->_modified)) {
			return false;
		}
		return array_key_exists($id, $this->_modified[$class]);
	}
	
	public function getLoaded($class=null, $id=null) {
		if (!is_null($class) && !array_key_exists($class, $this->_modified) && !array_key_exists($class, $this->_new)) {
			return null;
		}
		if (!is_null($id)) {
			if (is_array($this->_modified[$class]) && array_key_exists($id, $this->_modified[$class])) {
				return $this->_modified[$class][$id];
			} else if (is_array($this->_new[$class]) && array_key_exists($id, $this->_new[$class])) {
				return $this->_new[$class][$id];
			}
			return null;
		}
		if (is_null($id)) {
			return $this->_modified[$class];
		}
		return $this->_modified;
	}
	
	public function getActual($class, $id) {
		if (!array_key_exists($class, $this->_modified)) {
			return null;
		}
		if (!array_key_exists($id, $this->_modified[$class])) {
			return null;
		}
		
		return $this->_modified[$class][$id];
	}
	
	public function revert($entity) {
		$this->_revertDeleted($entity);
		
		$this->_revertModified($entity);
		
		$this->_revertNew($entity);
	}
	
	protected function _revertDeleted($entity) {
		$table = $this->_getTable($entity);
		if (array_key_exists($entity->id, $this->_delete[$table])) {
			unset($this->_delete[$table][$entity->id]);
		}
	}
	
	protected function _revertModified($entity) {
		$table = $this->_getTable($entity);
		if (array_key_exists($entity->id, $this->_modified[$table])) {
			foreach ($this->_actual[$table][$entity->id] as $key=>$value) {
				$entity->$key = $value;
			}
			unset($this->_modified[$table][$entity->id]);
			unset($this->_actual[$table][$entity->id]);
		}
	}
	
	protected function _revertNew($entity) {
		$table = $this->_getTable($entity);
		
		if (in_array($entity, $this->_new[$table])) {
			$key = array_search($entity, $this->_new[$table]);
			unset($this->_new[$table][$key]);
		}
	}
	
	protected function _getTable($entity) {
		$class = $this->_getClass($entity);
		$table = $this->_mapper->getTable($class);
		
		return $table;
	}
	
	public function commit() {
		foreach ($this->_new as $table=>$entities) {
			$this->_insertEntities($entities, $table);
		}
	}
	
	public function clearAll() {
		$this->_new = array();
		foreach ($this->_modified as $entities) {
			foreach ($entities as $entity) {
				$this->updateCleanEntity($entity);
			}
		}
		$this->_delete = array();
	}
	
	/**
	 * return the mapper
	 *
	 * @return SimDAL_Mapper
	 */
	protected function _getMapper() {
		return $this->_mapper;
	}
	
}