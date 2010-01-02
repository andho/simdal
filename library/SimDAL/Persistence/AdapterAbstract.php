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
	
	protected $_errorMessages = array();
	
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
	
	public function deleteByColumn($class, $value, $column) {
		$this->getUnitOfWork()->delete($value, $class, $column);
	}
	
	public function commit() {
		$this->_processEntities();
		
		$priority = $this->_getMapper()->getClassPriority();
		
		foreach ($priority as $class) {
			if (!$this->deleteMultiple($class, $this->_deletes[$class])) {
				return false;
			}
			
			if (!$this->insertMultiple($class, $this->_inserts[$class])) {
				return false;
			}
			
			if (!$this->updateMultiple($class, $this->_updates[$class])) {
				return false;
			}
		}
		
		$this->getUnitOfWork()->clearAll();
		
		$this->_inserts = array();
		$this->_updates = array();
		$this->_deletes = array();
		
		return true;
	}

	protected function _returnEntities($rows, $class) {
		$collection = new SimDAL_Collection();
		
		foreach ($rows as $row) {
			$entity = $this->_returnEntity($row, $class);
			$entityClass = $this->_getMapper()->getClassFromEntity($entity);
			$pk = $this->_getMapper()->getPrimaryKey($entityClass);
			$collection[$entity->$pk] = $entity;
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
	
	protected function _entityFromArray($row, &$class) {
		$entityClass = $this->_getMapper()->getTypeMorphClass($class, $row);
		
		$entity = new $entityClass();
		$class = $this->_getMapper()->getClassFromEntity($entity);
		foreach ($this->_getMapper()->getColumnData($class) as $property=>$column) {
			if (!property_exists($entity, $property)) {
				continue;
			}
			if (!property_exists($column[0], $row)) {
				continue;
			}
			$entity->$property = $row[$column[0]];
		}
		
		return $entity;
	}
	
	protected function _processEntities() {
		//$this->_resolveDependencies();0.
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
		foreach ($data as $class=>$entities) {
			foreach ($entities as $id=>$entity) {
				$this->_resolveEntityDependencies($entity);
				
				$this->_updates[$class][$id] = $entity;
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
				case 'one-to-one':
					$getter1 = 'get'.$relation[2]['method'];
					$setter1 = 'set'.$relation[2]['method'];
					$dependent = $entity->$getter1();
					if (!is_null($dependent) && !empty($relation[2]['dependentMethod'])) {
						$setter3 = 'set'.$relation[2]['dependentMethod'];
						$dependent->$setter3($entity);
					}
					if (!is_null($dependent) && $this->_getMapper()->compare($class, $relation[1]) == SimDAL_Mapper::COMPARE_GREATER) {
						$this->_update($dependent);
					}
					break;
				case 'many-to-one':
					$getter = 'get'.$relation[1];
					$setter = 'set'.$relation[1];
					$relationEntity = $entity->$getter();
					if (!is_null($relationEntity) && $this->_isNew($relationEntity)) {
						$this->insert($relationEntity);
						$entity->$setter($relationEntity);
					}
					break;
				case 'one-to-many':
					$getter = 'get'.$relation[1].'s';
					$setter = 'get'.$relation[1].'s';
					$fk = $relation[2]['fk'];
					$key = isset($relation[2]['key']) ? $relation[2]['key'] : 'id';
					if (isset($relation[2]['key'])) {
						$key = $relation[2]['key'];
					}
					$relationEntities = $entity->$getter();
					if (count($relationEntities) > 0) {
						foreach ($entity->$getter() as $relationEntity) {
							$relationEntity->$fk = $entity->$key;
						}
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
				if (is_numeric($id)) {
					$this->_deletes[$class][] = $id;
				} else {
					$this->_deletes[$class][$id] = $row;
				}
			}
		}
	}
	
	public function returnQueryAsObject($class, $sql) {
		return $this->_returnResultRow($sql, $class);
	}
	
	public function returnQueryAsRow($sql) {
		return $this->_returnResultRow($sql);
	}
	
	public function returnQueryAsRows($sql, $class) {
		return $this->_returnResultRows($sql, $class);
	}
		
	protected function _transformData($key, $value, $class) {
		if (is_null($value)) {
			return "NULL";
		}
		
		$column = $this->_getMapper()->getColumn($class, $key);
		
		switch ($column[1]) {
			case 'varchar':
				return "'".$this->escape($value)."'";
				break;
			case 'date':
			case 'datetime':
				if (empty($value)) {
					return "NULL";
				} else {
					return "'".$this->escape($value)."'";
				}
			case 'float':
			case 'int':
				if ((int)$value !== 0 && (empty($value) || $value == '')) {
					return "NULL";
				} else {
					return $this->escape($value);
				}
			default: return $this->escape($value);
		}
	}
	
	public function _transformRow($row, $class, $key=null) {
		$data = array();
		
		foreach ($row as $column=>$value) {
			if (!is_null($key)) {
				$column = $key;
			}
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
		return $this->_getMapper()->getClassFromEntity($entity);
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
		
		$class = $this->_getMapper()->getClassFromEntity($entity);
		
		$pk = $this->_getMapper()->getPrimaryKey($class);
		
		foreach($this->_mapper->getColumnData($class) as $key=>$value) {
			if ($pk === $key) {
				continue;
			}
			if (!$includeNull && is_null($entity->$key)) {
				continue;
			}
			if ($transformData) {
				$array[$value[0]] = $this->_transformData($key, $entity->$key, $class);
			} else {
				$array[$value[0]] = $entity->$key;
			}
		}
		
		return $array;
	}
	
	protected function _setError($msg, $key=null) {
		if (is_null($key)) {
			$this->_errorMessages[] = $msg;
			return;
		}
		
		$this->_errorMessages[$key] = $msg;
	}
	
	public function getErrorMessages() {
		return $this->_errorMessages;
	}
	
	public function getErrorMessage($key) {
		if (!array_key_exists($key, $this->_errorMessages)) {
			return false;
		}
		
		return $this->_errorMessages[$key];
	}
	
	abstract public function findById($class, $id);
	
	abstract public function findByColumn($class, $value, $column, $limit=1);
	
	abstract public function findBy($class, array $keyValuePairs, $limit=1);
	
	abstract public function findByEither($class, array $keyValuePairs, $limit=1);
	
	abstract public function execute($sql);
	
	abstract public function getAdapterError();
	
	abstract public function escape($value);
	
	abstract protected function _returnResultRow($sql, $class=null);
	
	abstract protected function _returnResultRows($sql, $class);
	
}