<?php

abstract class SimDAL_Persitence_AdapterAbstract implements SimDAL_Persistence_AdapterInterface {
	
	static protected $_defaultMapper = null;
	
	/**
	 * Unit of work
	 *
	 * @var SimDAL_UnitOfWork
	 */
	protected $_unitOfWork = null;
	
	/**
	 * Mapper
	 *
	 * @var SimDAL_Mapper
	 */
	protected $_mapper = null;
	
	protected $_inserts = array();
	protected $_updates = array();
	protected $_deletes = array();
	
	public function _construct($mapper=null) {
		if ($mapper instanceof SimDAL_Mapper) {
			$this->_mapper = $mapper;
		} else if (self::$_defaultMapper instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultMapper;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
	}
	
	/**
	 * Returns Unit of Work
	 *
	 * @return SimDAL_UnitOfWork
	 */
	public function getUnitOfWork() {
		if (is_null($this->_unitOfWork)) {
			$this->_unitOfWork = new SimDAL_UnitOfWork($this->_mapper);
		}
	}
	
	public function insert($entity) {
		$this->getUnitOfWork()->add($entity);
	}
	
	public function delete($entity) {
		$this->getUnitOfWork()->delete($entity);
	}
	
	public function deleteById($id) {
		$this->getUnitOfWork()->delete(
			$id,
			$this->_getMapper()->getTable(
				$this->_getClass($entity)
			)
		);
	}
	
	public function commit() {
		$this->_processEntities();
		$queries = $this->_processQueries();
	}
	
	protected function _processQueries() {
		$queries = $this->_processDeleteQueries();
		$queries = array_merge($queries, $this->_processUpdateQueries());
		$queries = array_merge($queries, $this->_processInsertQueries());
		
		$classPriority = $this->_getMapper()->getClassPriority();
		
		foreach($classPriority as $class) {
			$this->deleteMultiple($class, $this->_deletes[$class]);
			$this->updateMultiple($class, $this->_updates[$class]);
			$this->insertMultiple($class, $this->_inserts[$class]);
		}
	}
	
	protected function _processEntities() {
		$this->_processInserts();
		$this->_processUpdates();
		$this->_processDeletes();
	}
	
	protected function _processInserts() {
		$data = $this->getUnitOfWork()->getNew();
		foreach ($data as $class=>$entities) {
			foreach ($rows as $id=>$entity) {
				$this->resolveDependencies();
				
				$this->_inserts[$class][$id] = $entity;
			}
		}
	}
	
	protected function _processUpdates() {
		$data = $this->getUnitOfWork()->getChanges();
		foreach ($data as $class=>$rows) {
			foreach ($rows as $id=>$row) {
				$entity = $this->getUnitOfWork()->getLoaded($id);
				$this->resolveDependencies();
				
				$this->_updates[$class][$id] = $row;
			}
		}
	}
	
	protected function _processDeletes() {
		$data = $this->getUnitOfWork()->getDeleted();
		foreach ($data as $class=>$rows) {
			foreach ($rows as $id=>$row) {
				// @todo resolve dependencies
				$this->_deletes[$class][] = $id;
			}
		}
	}
	
	protected function _transformData($key, $value, $class) {
		if (is_null($value)) {
			return "NULL";
		}
		
		$column = $this->_getMapper($class)->getColumn($key);
		
		if ($column[1] == 'varchar') {
			return "'$value'";
		}
		
		return $value;
	}
	
	public function _transformRow($row, $class) {
		$data = array();
		
		foreach ($row as $key=>$value) {
			$data[] = $this->_transformData($key, $value, $class);
		}
		
		return $data;
	}
	
	/**
	 * return Mapper
	 *
	 * @return SimDAL_Mapper
	 */
	protected function _getMapper() {
		return $this->_mapper;
	}
	
	protected function _getClass($entity) {
		if (!is_object($entity)) {
			throw new Exception("Invalid argument passed. Object is required");
		}
		
		$class = get_parent_class($entity);
		if (is_null($class)) {
			$class = get_class($entity);
		}
		
		return $class;
	}

	protected function _arrayForStorageFromEntity($entity, $includeNull = false, $transformData=false) {
		$array = array();
		
		$class = get_parent_class($entity);
		if (!$class) {
			$class = get_class($entity);
		}
		
		foreach($this->_mapper->getColumnData($class) as $key=>$value) {
			if (!$includeNull && is_null($entity->$key)) {
				continue;
			}
			if ($transformData) {
				if (is_null($entity->$key)) {
					$array[$value[0]] = 'NULL';
				} else if ($value[1] == 'int') {
					$array[$value[0]] = $entity->$key;
				} else if ($value[1] == 'varchar' || $value[1] == 'date') {
					$array[$value[0]] = "'".$entity->$key."'";
				}
			} else {
				$array[$value[0]] = $entity->$key;
			}
		}
		
		return $array;
	}
	
	abstract public function execute();
	
}