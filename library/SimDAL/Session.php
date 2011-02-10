<?php
/**
 * SimDAL - Simple Domain Abstraction Library.
 * This library will help you to separate your domain logic from
 * your persistence logic and makes the persistence of your domain
 * objects transparent.
 * 
 * Copyright (C) 2011  Andho
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class SimDAL_Session implements SimDAL_Query_ParentInterface {
	
	static protected $_factory;
	
	protected $_adapter;
	protected $_mapper;
	
	protected $_new = array();
	protected $_modified = array();
	protected $_actual = array();
	protected $_deleted = array();
	protected $_newKey = 1;
	protected $_lockRows = false;
	protected $_hooks = array();
	protected $_hookSession;
	
	/**
	 * @return SimDAL_Session_Factory
	 */
	static public function factory($conf=null) {
		if (is_null(self::$_factory)) {
			if (is_null($conf)) {
				throw new Exception("Need configuration when initializing Session Factory");
			}
			self::$_factory = new SimDAL_Session_Factory($conf);
		}
		
		return self::$_factory;
	}
	
	public function __construct($mapper, $adapter_class, $db_conf) {
		if ($mapper instanceof SimDAL_Mapper) {
			$this->_mapper = $mapper;
		} else if (self::$_defaultMapper instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultMapper;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
		
		if (!is_null($adapter_class)) {
			$this->_adapter = $adapter_class;
		}
		if ($adapter_class instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = $adapter_class;
			return;
		}
		if (!is_string($this->_adapter) || !class_exists($this->_adapter)) {
			throw new Exception("Supplied Adapter Class is not a valid class name");
		}
		
		$class_parents = class_parents($this->_adapter);
		if (!in_array('SimDAL_Persistence_AdapterAbstract', $class_parents)) {
			throw new Exception("Supplied Adapter is not a valid Adapter Class");
		}
		
		$adapter_class = $this->_adapter;
		
		$this->_adapter = new $adapter_class($this->_mapper, $this, $db_conf);
	}
	
	public function startTransaction() {
		if ($this->_lockRows !== true) {
			$this->getAdapter()->startTransaction();
			$this->_lockRows = true;
		}
	}
	
	public function rollback() {
		$this->_lockRows = false;
	}
	
	public function addEntity(&$entity) {
		if ($this->isLoaded($entity)) {
			return false;
		}
		
		$domain_entity_name = $this->getMapper()->getClassFromEntity($entity);
		$class = $this->getMapper()->getDescendentEntityClass($entity, $domain_entity_name);
		$class = preg_replace('/SimDALProxy$/', '', $class);
		/* @var $entityMapper SimDAL_Mapper_Entity */
		$entityMapper = $this->getMapper()->getMappingForEntityClass($domain_entity_name);
		
		if (!array_key_exists($domain_entity_name, $this->_new) || !is_array($this->_new[$domain_entity_name])) {
			$this->_new[$domain_entity_name] = array();
		}
		
		if (in_array($entity, $this->_new[$domain_entity_name])) {
			return false;
		}
		
		$pkColumn = $entityMapper->getPrimaryKeyColumn();
		$id = null;
		if ($pkColumn->isAutoIncrement()) {
			$pk = $pkColumn->getProperty();
			$id = 'autoincrement' . $this->_newKey;
		}
		
		$proxyClass = $class . 'SimDALProxy';
		$proxy = new $proxyClass($entity, $this, $id);
		$entity = $proxy;
		
		$this->_new[$domain_entity_name][] = $entity;
		
		$this->_newKey++;
		
		return true;
	}
	
	public function updateEntity($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		$entityMapping = $this->getMapper()->getMappingForEntityClass($class);
		$primaryKey = $entityMapping->getPrimaryKey();
		$pk_getter = 'get' . $primaryKey;
		
		if (!array_key_exists($class, $this->_modified)) {
			$this->_modified[$class] = array();
			$this->_actual[$class] = array();
		}
		
		$this->_modified[$class][$entity->$pk_getter()] = $entity;
		
		$actual = clone($entity);
		$this->_actual[$class][$entity->$pk_getter()] = $actual;
	}
	
	public function deleteEntity($entity) {
		$class = $this->_getClass($entity);
		$entityMapping = $this->getMapper()->getMappingForEntityClass($class);
		$primaryKey = $entityMapping->getPrimaryKey();
		$pk_getter = 'get' . $primaryKey;
		
		$this->_delete[$class][$entity->$pk_getter()] = $entity;
	}
	
	public function commit($soft=false) {
		$this->_resolveDependencies();
		
		$classes = $this->_getUsedClasses();
		$priority = $this->_getCommitPriority($classes);
		
		if ($this->_hasHooks()) {
			$this->_startHookSession();
		}
		
		$this->startTransaction();
		
		$error = false;
		try {
			foreach ($priority as $class) {
				if ($this->_hasDeletesFor($class) && !$this->_commitDeletesFor($class)) {
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
				
				if (!$this->_commitHookSession()) {
					throw new Exception("Unable to commit hook session");
				}
			}
		} catch (Exception $e) {
			$this->getAdapter()->rollbackTransaction();
			throw $e;
		}
		
		if (!$soft) {
			$this->getAdapter()->commitTransaction();
			$this->_lockRows = false;
		}
		
		return true;
	}
	
	public function softCommit() {
		return $this->commit(true);
	}

	/**
	 * 
	 * @return SimDAL_Query
	 */
	public function load($class) {
		$query = new SimDAL_Query($this, SimDAL_Query::TYPE_SELECT, $this->getMapper());
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$query->from($mapping);
		return $query;
	}
	
	public function count(SimDAL_Query $query) {
		$query->from($query->getMapping(), array('count'=>'COUNT(*)'));
		return $this->getAdapter()->returnQueryResultAsArray($query);
	}
	
	public function fetch(SimDAL_Query $query, $limit=null, $offset=null) {
		if (is_null($offset)) {
			$offset = 0;
		}
		if (is_null($limit)) {
			$query->limit(1);
		} else {
			$query->limit($limit, $offset);
		}
		
		return $this->getAdapter()->returnQueryResult($query, $this->_lockRows);
	}
	
	public function isAdded($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		if (!array_key_exists($class, $this->_new)) {
			return false;
		}
		return in_array($entity, $this->_new[$class]);
	}
	
	public function isLoaded($class, $id=null) {
		if (is_object($class)) {
			$entity = $class;
			$class = $this->getMapper()->getClassFromEntity($class);
			
			$pk = $this->getMapper()->getMappingForEntityClass($class)->getPrimaryKey();
			$getter = 'get' . ucfirst($pk);
			$id = $entity->$getter();
		}
		
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
	
	public function getActualFromEntity($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$associations = $mapping->getAssociations();
		$primaryKey = $mapping->getPrimaryKey();
		$pk_getter = 'get' . ucfirst($primaryKey);
		if (is_array($this->_actual[$class]) && array_key_exists($entity->$pk_getter(), $this->_actual[$class])) {
			return $this->_actual[$class][$entity->$pk_getter()];
		}
		
		return null;
	}
	
	/**
	 * @return SimDAL_Query
	 */
	public function update($class) {
		$query = new SimDAL_Query($this, $this->getMapper(), SimDAL_Query::TYPE_UPDATE);
		$query->limit(0);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$query->from($mapping);
		return $query;
	}
	
	/**
	 * 
	 * @param string $class
	 * @return SimDAL_Query
	 */
	public function delete($class) {
		$query = new SimDAL_Query($this, $this->getMapper(), SimDAL_Query::TYPE_DELETE);
		$query->limit(0);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$query->from($mapping);
		return $query;
	}
	
	public function execute(SimDAL_Query $query) {
		return $this->getAdapter()->executeQueryObject($query);
	}
	
	protected function _resolveDependencies() {
		foreach ($this->_new as $class=>$entities) {
			foreach ($entities as $entity) {
				//$this->_resolveEntityDependencies($entity);
			}
		}
		foreach ($this->_modified as $class=>$entities) {
			foreach ($entities as $entity) {
				//$this->_resolveEntityDependencies($entity);
			}
		}
	}
	
	protected function _resolveEntityDependencies($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$associations = $mapping->getAssociations();
		$primaryKey = $mapping->getPrimaryKeyColumn();
		
		$actual = $this->getActualFromEntity($entity);
		
		if (!is_array($associations) || count($associations) <= 0) {
			return;
		}
		
		/* @var $association SimDAL_Mapper_Association */
		foreach ($associations as $association) {
			$method = $association->getMethod();
			
			$parentKey = $association->getParentKey();
			$parentKey_getter = 'get' . $parentKey;
			$foreignKey = $association->getForeignKey();
			$foreignKey_setter = 'set' . $foreignKey;
			
			$matching_assoc = $association->getMatchingAssociationFromAssociationClass();
			if (is_null($matching_assoc)) {
				throw new Exception("Unable to find matching association in '{$association->getClass()}' Entity for '{$method}' association in '{$class}' Entity");
			}
			
			$otherside_method = $matching_assoc->getMethod();
			$otherside_setter = 'set' . $otherside_method;
			$otherside_getter = 'get' . $otherside_method;
			
			switch ($association->getType()) {
				case 'one-to-one':
				case 'many-to-one':
					if ($association->isDependent()) {
						$getter = 'get' . $method;
						$dependent = $entity->$getter(false);
						if (!is_null($dependent)) {
							$entity->$foreignKey_setter($dependent->$parentKey_getter());
							$dependent->$otherside_getter()->add($entity, false);
						}
					}
					break;
				case 'one-to-many':
					if ($entity->$parentKey_getter() !== $actual->$parentKey_getter()) {
						continue;
					}
					
					$getter = 'get' . $method;
					$dependents = $entity->$getter();
					foreach ($dependents->toArray() as $dependent) {
						$dependent->$otherside_setter($entity);
						$dependent->$foreignKey_setter($entity->$parentKey_getter());
					}
					
					$this->update($association->getClass())->set($foreignKey, $entity->$parentKey_getter())->whereColumn($foreignKey)->equals($actual->$parentKey_getter())->execute();
					
					break;
			}
		}
	}
	
	protected function _commitDeletesFor($class) {
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$primaryKey = $mapping->getPrimaryKey();
		$pk_getter = 'get' . ucfirst($primaryKey);
		foreach ($this->_deleted[$class] as $key=>$entity) {
			if (!$this->getAdapter()->deleteEntity($entity)) {
				return false;
			}
			$this->_processDeleteHooks($entity);
			
			unset($this->_deleted[$class][$entity->$pk_getter()]);
		}
		
		return true;
	}
	
	protected function _commitInsertsFor($class) {
		foreach ($this->_new[$class] as $key=>$entity) {
			$id = $this->getAdapter()->insertEntity($entity);
			if ($id === false) {
				return false;
			}
			$class = $this->getMapper()->getClassFromEntity($entity);
			$mapping = $this->getMapper()->getMappingForEntityClass($class);
			$pk = $mapping->getPrimaryKey();
			
			$entity->_SimDAL_setPrimaryKey($id);
			$this->_distributeParentKeysOfNewEntityToForeignKeysOfDependents($entity);
			$this->_processInsertHooks($entity);
			$this->updateEntity($entity);
		}
		
		return true;
	}
	
	protected function _commitUpdatesFor($class) {
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$primaryKey = $mapping->getPrimaryKey();
		$pk_getter = 'get' . ucfirst($primaryKey);
		foreach ($this->_modified[$class] as $key=>$entity) {
			$this->_processUpdateHooks($entity, $this->getActualFromEntity($entity));
			if (!$this->getAdapter()->updateEntity($entity)) {
				return false;
			}
			$actual = clone($entity);
			$this->_actual[$class][$entity->$pk_getter()] = $actual;
		}
		
		return true;
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
					$dependent = $entity->$getter(false);
					if (!is_null($dependent)) {
						$dependent->$foreignKey = $entity->$parentKey;
					}
					break;
				case 'one-to-many':
					$method = $association->getMethod();
					$getter = 'get' . $method;
					$dependents = $entity->$getter();
					foreach ($dependents->toArray(false) as $dependent) {
						$dependent->$foreignKey = $entity->$parentKey;
					}
					break;
			}
		}
	}
	
	protected function _hasDeletesFor($class) {
		if (!array_key_exists($class, $this->_deleted) || !is_array($this->_deleted[$class]) || count($this->_deleted[$class]) <= 0) {
			return false;
		}
		
		return true;
	}
	
	protected function _hasInsertsFor($class) {
		return array_key_exists($class, $this->_new);
	}
	
	protected function _hasUpdatesFor($class) {
		return array_key_exists($class, $this->_modified);
	}
	
	protected function _getUsedClasses() {
		$classes = array_keys($this->_new);
		$classes = array_merge($classes, array_keys($this->_modified));
		if (isset($this->_deleted['entities']) && is_array($this->_deleted['entities'])) {
			$classes = array_merge($classes, array_keys($this->_deleted['entities']));
		}
		return array_unique($classes);
	}
	
	protected function _getCommitPriority($classes) {
		return $this->getMapper()->getClassPriority($classes);
	}
	
	protected function _hasHooks() {
		if (count($this->_hooks['insert'])) {
			return true;
		}
		if (count($this->_hooks['update'])) {
			return true;
		}
		if (count($this->_hooks['delete'])) {
			return true;
		}
		
		return false;
	}
	
	protected function _startHookSession() {
		$this->_hookSession = SimDAL_Session::factory()->getNewSession();
	}
	
	/**
	 * @return SimDAL_Session
	 */
	protected function _getHookSession() {
		return $this->_hookSession;
	}
	
	protected function _commitHookSession() {
		if ($this->_hookSession) {
			return $this->_hookSession->commit();
		}
		
		return true;
	}
	
	protected function _processInsertHooks($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		if (!is_array($this->_hooks) || !array_key_exists('insert', $this->_hooks) || !array_key_exists($class, $this->_hooks['insert'])) {
			return;
		}
		
		foreach ($this->_hooks['insert'][$class] as $hook) {
			$method = $hook['method'];
			$hook['object']->$method($entity, $this->_getHookSession(), $hook['data']);
		}
	}
	
	protected function _processUpdateHooks($entity, $actual) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		if (!is_array($this->_hooks) || !array_key_exists('update', $this->_hooks) || !array_key_exists($class, $this->_hooks['update'])) {
			return;
		}
		
		$row = $this->getChanges($entity);
		foreach ($this->_hooks['update'][$class] as $hook) {
			$method = $hook['method'];
			$hook['object']->$method($entity, $actual, $row, $this->_getHookSession(), $hook['data']);
		}
	}
	
	protected function _processDeleteHooks($entity) {
		$class = $this->getMapper()->getClassFromEntity($entity);
		if (!array_key_exists($class, $this->_hooks['delete'])) {
			return;
		}
		
		foreach ($this->_hooks['delete'][$class] as $hook) {
			$method = $hook['method'];
			$hook['object']->$method($entity, $this->_getHookSession(), $hook['data']);
		}
	}
	
	protected function _registerHook($class, $object, $method, $type, $data) {
		$this->_hooks[$type][$class][] = array('object'=>$object, 'method'=>$method, 'data'=>$data);
	}
	
	public function registerInsertHook($class, $object, $method, $data=array()) {
		$this->_registerHook($class, $object, $method, 'insert', $data);
	}
	
	public function registerUpdateHook($class, $object, $method, $data=array()) {
		$this->_registerHook($class, $object, $method, 'update', $data);
	}
	
	public function registerDeleteHook($class, $object, $method, $data=array()) {
		$this->_registerHook($class, $object, $method, 'delete', $data);
	}
	
	public function getChanges($entity) {
		$data = array();
		
		$class = $this->getMapper()->getClassFromEntity($entity);
		$mapping = $this->getMapper()->getMappingForEntityClass($class);
		$actual = $this->getActualFromEntity($entity);
		
		$data = array();
		/* @var $column SimDAL_Mapper_Column */
		foreach ($mapping->getColumns() as $column) {
			$property = $column->getProperty();
			$method = ucfirst($property);
			$getter = 'get' . $method;
			$setter = 'set' . $method;
			if ($entity->$getter() != $actual->$getter()) {
				$data[$property] = $entity->$getter();
			}
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