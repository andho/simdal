<?php

class SimDAL_Session {
	
	static protected $_defaultAdapter;
	static protected $_defaultMapper;
	
	protected $_adapter;
	protected $_mapper;
	
	protected $_new = array();
	protected $_modified = array();
	protected $_actual = array();
	protected $_deleted = array();
	protected $_newKey = 1;
	
	static public function setDefaultAdapter(SimDAL_Persistence_AdapterAbstract $adapter) {
		self::$_defaultAdapter = $adapter;
	}
	
	static public function setDefaultMapper(SimDAL_Mapper $mapper) {
		self::$_defaultMapper = $mapper;
	}
	
	public function __construct($adapter=null, $mapper=null) {
		if ($adapter instanceof SimDAL_Mapper) {
			$this->_mapper = $adapter;
		} else if (self::$_defaultAdapter instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultAdapter;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
		
		if ($mapper instanceof SimDAL_Mapper) {
			$this->_mapper = $mapper;
		} else if (self::$_defaultMapper instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultMapper;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
	}
	
	public function add($entity) {
		if ($this->isLoaded($entity)) {
			return false;
		}
		
		$class = $this->_getClass($entity);
		/* @var $entityMapper SimDAL_Mapper_Entity */
		$entityMapper = $this->getMapper()->getMappingForEntityClass($class);
		
		$table = $this->_mapper->getTable($class);
		
		if (!array_key_exists($class, $this->_new) || !is_array($this->_new[$class])) {
			$this->_new[$class] = array();
		}
		
		$pk = $entityMapper->getPrimaryKey();
		$column = $entityMapper->getPrimaryKeyColumn();
		if ($column->isAutoIncrement()) {
			$entity->$pk = 'autoincrement'.$this->_newKey++;
		}
		
		$this->_new[$class][$entity->$pk] = $entity;
		
		return true;
	}
	
	public function update($entity) {
		$class = $this->_getClass($entity);
		$entityMapping = $this->getMapper()->getMappingForEntityClass($class);
		
		if (!array_key_exists($class, $this->_modified)) {
			$this->_modified[$class] = array();
			$this->_actual[$class] = array();
		}
		
		$this->_modified[$class][$entity->id] = $entity;
		
		$actual = clone($entity);
		$this->_actual[$class][$entity->id] = $actual;
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
	
	public function commit() {
		$classes = $this->_getUsedClasses();
		$priority = $this->_getCommitPriority($classes);
		
		$this->getAdapter()->startTransaction();
		
		$error = false;
		
		foreach ($priority as $class) {
			if ($this->_hasDeletesForClass($class)) {
				$error = true;
			}
			
			if ($this->_hasInsertsFor($class) && !$this->_commitInsertsFor($class)) {
				$error = true;
				break;
			}
			
			if ($this->_hasUpdatesFor($class) && !$this->_commitUpdatesFor($class)) {
				$error = true;
				break;
			}
		}
		
		if (!$error) {
			$this->getAdapter()->commit();
		} else {
			$this->getAdapter()->rollbackTransaction();
		}
	}
	
	protected function _commitInsertsFor($class) {
		foreach ($this->_new[$class] as $key=>$entity) {
			$id = $this->getAdapter()->insertEntity($entity);
			$class = $this->getMapper()->getClassFromEntity($entity);
			$mapping = $this->getMapper()->getMappingForEntityClass($class);
			$pk = $mapping->getPrimaryKey();
			
			$entity->$pk = $id;
			$this->_distributeParentKeysToForeignKeys($entity);
			$this->getUnitOfWork()->update($entity);
		}
	}
	
	protected function _commitUpdatesFor($class) {
		
	}
	
	protected function _distributeParentKeysToForeignKeysOfNewEntity($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$associations = $mapping->getAssociations();
		
		if (!is_array($associations) || count($associations) <= 0) {
			return;
		}
		
		/* @var $association SimDAL_Mapper_Association */
		foreach ($associations as $association) {
			$parentKey = $association->getParentKey();
			$primaryKey = $mapping->getPrimaryKey();
			if ($primaryKey != $parentKey) {
				continue;
			}
			if (!$mapping->getPrimaryKeyColumn()->isAutoIncrement()) {
				continue;
			}
			$foreignKey = $association->getForeignKey();
			switch ($association->getType()) {
				case 'one-to-one':
					$method = $association->getParentM();
					$getter = 'set' . $method;
					$setter = 'get' . $method;
					$dependent = $entity->$getter();
					if (!is_null($dependent)) {
						$dependent->$foreignKey = $entity->$parentKey;
					}
					break;
				case 'one-to-many':
					$method = $association->getMethod();
					$getter = 'get' . $method;
					$dependents = $entity->$getter();
					foreach ($dependents as $dependent) {
						$dependent->$foreignKey = $entity->$parentKey;
					}
					break;
			}
		}
	}
	
	protected function _hasDeletesForClass($class) {
		if (!array_key_exists($class, $this->_deleted) || !is_array($this->_deleted[$class]) || count($this->_deleted[$class]) <= 0) {
			return false;
		}
		
		return true;
	}
	
	protected function getUsedClasses() {
		$classes = array_keys($this->_new);
		$classes = array_merge($classes, array_keys($this->_modified));
		$classes = array_merge($classes, array_keys($this->_deleted['entities']));
		return array_unique($classes);
	}
	
	protected function getCommitPriority($classes) {
		return $this->getMapper()->getClassPriority($classes);
	}
	
	/**
	 * return the mapper
	 *
	 * @return SimDAL_Mapper
	 */
	public function getMapper() {
		return $this->_mapper;
	}
	
	/**
	 * @return SimDAL_Persistence_AdapterAbstract
	 */
	public function getAdapter() {
		return $this->_adapter;
	}
	
}