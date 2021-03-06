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

abstract class SimDAL_Persistence_AdapterAbstract {
	
	static protected $_defaultMapper = null;
	
	/**
	 * Mapper
	 *
	 * @var SimDAL_Mapper
	 */
	protected $_mapper = null;
	
	protected $_inserts = array();
	protected $_updates = array();
	protected $_deletes = array();
	
	protected $_transaction = false;
	protected $_auto_commit = false;
	
	protected $_mockQueries = array();
	
	static public function setDefaultMapper($mapper) {
		if (!$mapper instanceof SimDAL_Mapper) {
			return false;
		}
		
		self::$_defaultMapper = $mapper;
	}
	
	public function __construct($mapper=null, $conf=array()) {
		if ($mapper instanceof SimDAL_Mapper) {
			$this->_mapper = $mapper;
		} else if (self::$_defaultMapper instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultMapper;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
		
		$this->_auto_commit = isset($conf['autocommit']) ? $conf['autocommit'] : false;
	}
	
	public function __destruct() {
		
	}
	
	public function autoCommit($set=false) {
		$this->_auto_commit = $set;
	}
	
	/**
	 * @return SimDAL_Session
	 */
	protected function _getSession() {
		return $this->_session;
	}
	/*
	public function _insert($entity) {
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$row = $this->_arrayForStorageFromEntity($entity, false, true);
		$sql = $this->_processInsertQuery($class, $row);
		if (($result = $this->execute($sql)) === false) {
			throw new SimDAL_Persistence_AdapterException($this, $this->getAdapterError() . ' for entity ' . get_class($entity));
		}
		
		$id = $this->lastInsertId();
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$entity->$pk = $id;
		
		return true;
	}
	*/
	public function insertEntity($entity) {
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$mapping = $this->_getMapper()->getMappingForEntityClass($class);
		
		$row = $this->_arrayForStorageFromEntity($mapping, $entity, true, true);
		$sql = $this->_processInsertQuery($mapping, $row);
		if (($result = $this->execute($sql)) === false) {
			throw new SimDAL_Persistence_AdapterException($this, $this->getAdapterError() . ' for entity ' . get_class($entity));
		}
		$id = $this->lastInsertId();
		
		/*$mapping = $this->_getMapper()->getMappingForEntityClass($class);
		if ($mapping->hasDescendents()) {
			$descendent = $mapping->getDescendentMappingFromEntity($entity);
			if (!is_null($descendent)) {
				$row = $this->_arrayForStorageFromEntity($descendent, $entity, false, true);
				$sql = $this->_processInsertQuery($descendent, $row);
				if (($result = $this->execute($sql)) === false) {
					throw new Exception($this->getAdapterError());
				}
			}
		}*/
		
		return $id;
	}
	
	public function updateEntity($entity) {
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$mapping = $this->_getMapper()->getMappingForEntityClass($class);
		$pk = $mapping->getPrimaryKey();
		$getter = 'get' . ucfirst($pk);
		
		$row = $this->_getSession()->getChanges($entity);
		if (count($row) <= 0) {
			return true;
		}
		
		$sql = $this->_processUpdateQuery($mapping, $row, $entity->$getter());
		$result = $this->execute($sql);
		if ($result === false) {
			throw new SimDAL_Persistence_AdapterException($this, $this->getAdapterError() . ' for entity ' . get_class($entity));
		}
		
		return true;
	}
	
	public function deleteEntity($entity) {
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$mapping = $this->_getMapper()->getMappingForEntityClass($class);
		$pk = $mapping->getPrimaryKey();
		$getter = 'get' . ucfirst($pk);
		
		$sql = $this->_processDeleteQuery($mapping, $entity->$getter());
		$result = $this->execute($sql);
		if ($result === false) {
			throw new Exception($this->getAdapterError());
		}
		
		return true;
	}
	
	protected function _processInsertQuery(SimDAL_Mapper_Entity $mapping, $data) {
		$sql = "INSERT INTO ".$this->quoteIdentifier($mapping->getTable())." (`".implode('`,`',array_keys($data))."`) VALUES (".implode(',',$data).")";
		
		return $sql;
	}
	
	protected function _processDeleteQuery(SimDAL_Mapper_Entity $mapping, $id) {
		$pk = $mapping->getPrimaryKeyColumn();
		$sql = "DELETE FROM ".$this->_quoteIdentifier($mapping->getTable())." WHERE `{$pk->getColumn()}`=".$id;
		
		return $sql;
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
		
		if (!is_array($relations) || count($relations) <= 0) {
			return;
		}
		
		foreach ($relations as $relation) {
			switch ($relation[0]) {
				case 'one-to-one':
					$method = $this->_getMapper()->getRelationMethod($relation);
					$getter1 = 'get'.$method;
					$setter1 = 'set'.$method;
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
					$method = $this->_getMapper()->getRelationMethod($relation);
					$getter = 'get'.$method;
					$setter = 'set'.$method;
					$relationEntity = $entity->$getter();
					if (!is_null($relationEntity) && $this->_isNew($relationEntity)) {
						$this->insert($relationEntity);
						$entity->$setter($relationEntity);
					}
					break;
				case 'one-to-many':
					$method = $this->_getMapper()->getRelationMethod($relation);
					$getter = 'get'.$method;
					$setter = 'get'.$method;
					if (!isset($relation[2]['fk'])) {
						throw new Exception("Foriegn Key not set for {$relation[0]} relation '{$relation[1]}' in '$class'");
					}
					$fk = $relation[2]['fk'];
					$pk = $this->_getMapper()->getPrimaryKey($class);
					$key = isset($relation[2]['key']) ? $relation[2]['key'] : 'id';
					if (isset($relation[2]['key'])) {
						$key = $relation[2]['key'];
					}
					$actual = $this->getUnitOfWork()->getActual($class, $entity->$pk);
					if (is_null($actual) || $entity->$key != $actual->$key) {
						$relationEntities = $entity->$getter();
						if (count($relationEntities) > 0) {
							foreach ($entity->$getter() as $relationEntity) {
								if (is_null($actual) && $relationEntity->$fk != -1 && $relationEntity->$fk != null && strpos($relationEntity->$fk, 'autoincrement') === false) {
									continue;
								}
								$relationEntity->$fk = $entity->$key;
							}
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
	
	public function returnQueryAsRows($sql) {
		if ($this->_isMockQueriesSet()) {
			$result = $this->_fetchMockResult(md5($sql));
			if ($result !== false) {
				return $result;
			}
		}
		
		return $this->_returnResultRows($sql);
	}
	
	public function returnQueryAsObjects($sql, $class) {
		return $this->_returnResultRows($sql, $class);
	}
	
	public function returnQueryResult(SimDAL_Query $query, $lockRows=false) {
		$sql = $this->_queryToString($query);
		
		if ($query->limit() == 1) {
			return $this->_returnResultRow($sql, $lockRows);
		} else {
			return $this->_returnResultRows($sql, $lockRows);
		}
	}
	
	public function returnQueryResultAsArray(SimDAL_Query $query) {
		$sql = $this->_queryToString($query);
		
		if ($query->limit() == 1) {
			return $this->returnQueryAsRow($sql);
		} else {
			return $this->returnQueryAsRows($sql, $query->getClass());
		}
	}
	
	public function transformData(SimDAL_Mapper_Column $column, $value, SimDAL_Mapper_Entity $mapping) {
		if (is_null($value)) {
			return "NULL";
		}
		
		switch ($column->getDataType()) {
			case 'text':
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
				break;
			case 'float':
			case 'int':
				if ($value === '' || ($value != 0 && (empty($value) && $value == ''))) {
					return "NULL";
				} else {
					return $this->escape($value);
				}
				break;
			case 'binary':
			case 'blob':
				$output = "'".$this->escape($value, 'binary')."'";
				return $output;
				break;
			default: return $this->escape($value);
		}
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

	protected function _arrayForStorageFromEntityDescendent($entity, $includeNull = false, $transformData=false) {
		$array = array();
		
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$mapping = $this->_getMapper()->getMappingForEntityClass($class);
		
		/* @var $descendent_mapping SimDAL_Mapper_Descendent */
		$descendent_mapping = $mapping->getDescendentMappingByEntity($entity);
		
		$pk = $descendent_mapping->getPrimaryKey();
		
		/* @var $column SimDAL_Mapper_Column */
		foreach ($descendent_mapping->getColumns() as $column) {
			if ($column->isPrimaryKey()) {
				continue;
			}
			$method = 'get' . ucfirst($column->getColumn());
			if (!method_exists($entity, $method)) {
				continue;
			}
			if (!$includeNull && is_null($entity->$method())) {
				continue;
			}
			
			if ($transformData) {
				$array[$column->getColumn()] = $this->transformData($key, $entity->$method(), $class);
			} else {
				$array[$column->getColumn()] = $entity->$method();
			}
		}
		
		return $array();
	}
	
	protected function _arrayForStorageFromEntity(SimDAL_Mapper_Entity $mapping, $entity, $includeNull = false, $transformData=false) {
		$array = array();
		
		/* @var SimDAL_Mapper_Column */
		foreach($mapping->getColumns() as $key=>$column) {
			if ($column->isPrimaryKey() && $column->isAutoIncrement()) {
				continue;
			}
			
			$method = 'get' . ucfirst($column->getProperty());
			if (!method_exists($entity, $method)) {
				continue;
			}
			if (!$includeNull && is_null($entity->$method())) {
				continue;
			}
			
			if ($transformData) {
				$array[$column->getColumn()] = $this->transformData($column, $entity->$method(), $mapping);
			} else {
				$array[$column->getColumn()] = $entity->$method();
			}
		}
		
		return $array;
	}
	
	public function setMockQuery($sql, $result) {
		$hash = md5($sql);
		$this->_mockQueries[$hash]['result'] = $result;
	}
	
	protected function _isMockQueriesSet() {
		return count($this->_mockQueries) > 0;
	}
	
	protected function _fetchMockResult($hash) {
		if (!isset($this->_mockQueries[$hash])) {
			return false;
		}
		return $this->_mockQueries[$hash]['result'];
	}
	
	public function executeQueryObject($query) {
		$sql = $this->_queryToString($query);
		
		return $this->execute($sql);
	}
	
	abstract public function execute($sql);
	
	abstract public function startTransaction();
	
	abstract public function commitTransaction();
	
	abstract public function rollbackTransaction();
	
	abstract public function getAdapterError();
	
	abstract public function escape($value);
	
	abstract public function quoteIdentifier($column);
	
	abstract public function lastInsertId();
	
	abstract protected function _returnResultRow($sql, $class=null);
	
	abstract protected function _returnResultRows($sql, $class=null);
	
	abstract protected function _queryToString(SimDAL_Query $query);
	
}