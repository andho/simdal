<?php

abstract class SimDAL_Persistence_AdapterAbstract {
	
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
	
	static public function setDefaultMapper($mapper) {
		if (!$mapper instanceof SimDAL_Mapper) {
			return false;
		}
		
		self::$_defaultMapper = $mapper;
	}
	
	public function __construct($mapper=null) {
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
		
		return $this->_unitOfWork;
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
		
		$priority = $this->_getMapper()->getClassPriority();
		
		foreach ($priority as $class) {
			foreach ($this->_deletes[$class] as $data) {
				$this->deleteMultiple($class, $data);
			}
			//foreach ($this->_updates[$class] as $id=>$row) {
				$this->updateMultiple($class, $this->_updates[$class]);
			//}
			
			$this->insertMultiple($class, $this->_inserts[$class]);
		}
		
		$this->getUnitOfWork()->clearAll();
		
		return true;
	}

	protected function _returnEntities($rows, $class) {
		$collection = array();
		
		foreach ($rows as $row) {
			$collection[] = $this->_returnEntity($row, $class);
		}
		
		return $collection;
	}
	
	protected function _returnEntity($row, $class) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$entity = $this->_entityFromArray($row, $class);
		if ($this->getUnitOfWork()->isLoaded($class, $entity->id)) {
			return $this->getUnitOfWork()->getLoaded($class, $entity->id);
		}
		
		$this->getUnitOfWork()->updateCleanEntity($entity);
		
		return $entity;
	}
	
	protected function _entityFromArray($row, $class) {
		$entity = new $class();
		foreach ($this->_getMapper()->getColumnData($class) as $property=>$column) {
			$entity->$property = $row[$column[0]];
		}
		
		return $entity;
	}
	
	protected function _processEntities() {
		$this->_resolveDependencies();
		$this->_processInserts();
		$this->_processUpdates();
		$this->_processDeletes();
	}
	
	protected function _processInserts() {
		$data = $this->getUnitOfWork()->getNew();
		foreach ($data as $class=>$entities) {
			foreach ($entities as $entity) {
				$this->_inserts[$class][] = $entity;
			}
		}
	}
	
	protected function _processUpdates() {
		$data = $this->getUnitOfWork()->getChanges();
		foreach ($data as $class=>$rows) {
			foreach ($rows as $id=>$row) {
				$entity = $this->getUnitOfWork()->getLoaded($class, $id);
				$this->_resolveEntityDependencies($entity);
				
				$this->_updates[$class][$id] = $row;
			}
		}
	}
	
	protected function _resolveDependencies() {
		foreach ($this->getUnitOfWork()->getNew() as $entities) {
			foreach ($entities as $entity) {
				$this->_resolveEntityDependencies($entity);
			}
		}
		foreach ($this->getUnitOfWork()->getLoaded() as $entities) {
			foreach ($entities as $entity) {
				$this->_resolveEntityDependencies($entity);
			}
		}
	}
	
	protected function _resolveEntityDependencies($entity) {
		$class = $this->_getClass($entity);
		$relations = $this->_getMapper()->getRelations($class);
		
		foreach ($relations as $relation) {
			switch ($relation[0]) {
				case 'many-to-one':
					$getter = 'get'.$relation[1];
					$ch1 = $this->getUnitOfWork()->getChanges();
					$relationEntity = $entity->$getter();
					$ch2 = $this->getUnitOfWork()->getChanges();
					if (!is_null($relationEntity) && $this->_isNew($relationEntity)) {
						$this->insert($relationEntity);
					}
					break;
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
		
		$column = $this->_getMapper()->getColumn($class, $key);
		
		//if ($column[1] == 'varchar') {
			return "'$value'";
		//}
		
		//return $value;
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
		if (!$class) {
			$class = get_class($entity);
		}
		
		return $class;
	}
	
	protected function _isNew($entity, $query=false) {
		if (!is_object($entity)) {
			return false;
		}
		$pk = $this->_getMapper()->getPrimaryKey($this->_getClass($entity));
		if (!is_null($entity->$pk)) {
			return false;
		}
		
		return true;
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
				} else if ($value[1] == 'int' || $value[1] == 'float') {
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
	
	abstract public function execute($sql);
	
	
}