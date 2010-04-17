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
	
	public function __construct($mapper=null, $adapter_class=null) {
		if (!is_null($adapter_class)) {
			$this->_adapter = $adapter_class;
		} else {
			$this->_adapter = self::$_defaultAdapter;
		}
		/*if (!is_string($this->_adapter) || !class_exists($this->_adapter)) {
			throw new Exception("Supplied Adapter Class is not a valid class name");
		}
		
		$class_parents = class_parents($this->_adapter);
		if (!in_array('SimDAL_Persistence_AdapterAbstract')) {
			throw new Exception("Supplied Adapter  is not a valid Adapter Class");
		}
		
		$adapter_class = $this->_adapter;
		$this->_adapter = new $adapter_class(null, $this);*/
		
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
		$this->_resolveDependencies();
		
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

	/**
	 * 
	 * @return SimDAL_Query
	 */
	public function load($class) {
		$query = new SimDAL_Query($this);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$query->from($mapping);
		return $query;
	}
	
	public function fetch($query) {
		return $this->getAdapter()->returnQueryResult($query);
	}
	
	protected function _resolveDependencies() {
		foreach ($this->_new as $class=>$entities) {
			foreach ($entities as $entity) {
				$this->_resolveEntityDependencies($entity);
			}
		}
	}
	
	protected function _resolveEntityDependencies($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$associations = $mapping->getAssociations();
		$primaryKey = $mapping->getPrimaryKeyColumn();
		
		if (!is_array($associations) || count($associations) <= 0) {
			return;
		}
		
		$processed = array();
		
		/* @var $association SimDAL_Mapper_Association */
		foreach ($associations as $association) {
			$parentKey = $association->getParentKey();
			$foreignKey = $association->getForeignKey();
			switch ($association->getType()) {
				case 'one-to-one':
					$method = $assocation->getMethod();
					$getter = 'get ' . $method;
					$dependent = $this->$getter();
				case 'one-to-many':
					$method = $association->getMethod();
					$getter = 'get' . $method;
					$dependents = $entity->$getter();
					$dependent_mapping = $this->getMapper()->getMappingForEntityClass($association->getClass());
					$dependent_associations_all = $dependent_mapping->getAssociations();
					foreach ($dependent_associations_all as $dependent_association) {
						if ($class == $dependent_association->getClass() && $foreignKey == $dependent_association->getForeignKey()) {
							break;
						}
					}
					foreach ($dependents as $dependent) {
						$method = $dependent_association->getMethod();
						$dependent->$method($entity);
					}
					break;
			}
		}
	}
	
	protected function _commitInsertsFor($class) {
		foreach ($this->_new[$class] as $key=>$entity) {
			$id = $this->getAdapter()->insertEntity($entity);
			$class = $this->getMapper()->getClassFromEntity($entity);
			$mapping = $this->getMapper()->getMappingForEntityClass($class);
			$pk = $mapping->getPrimaryKey();
			
			$entity->$pk = $id;
			$this->_distributeParentKeysOfNewEntityToForeignKeysOfDependents($entity);
			$this->getUnitOfWork()->update($entity);
		}
	}
	
	protected function _commitUpdatesFor($class) {
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$primaryKey = $mapping->getPrimaryKey();
		foreach ($this->_modified[$class] as $key=>$entity) {
			$this->getAdapter()->updateEntity($entity);
			$actual = clone($entity);
			$this->_actual[$class][$entity->$primaryKey] = $entity;
		}
	}
	
	protected function _distributeParentKeysOfNewEntityToForeignKeysOfDependents($entity) {
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
	
	public function getChanges($entity) {
		$data = array();
		
		$class = $this->getMapper()->getClassFromEntity($entity);
		$pk = $this->getMapper()->getPrimaryKey($class);
		
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