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
	protected $_session = null;
	
	protected $_inserts = array();
	protected $_updates = array();
	protected $_deletes = array();
	
	static public function setDefaultMapper($mapper) {
		if (!$mapper instanceof SimDAL_Mapper) {
			return false;
		}
		
		self::$_defaultMapper = $mapper;
	}
	
	public function __construct($mapper=null, SimDAL_Session $session=null) {
		if ($mapper instanceof SimDAL_Mapper) {
			$this->_mapper = $mapper;
		} else if (self::$_defaultMapper instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultMapper;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
		
		if ($session instanceof SimDAL_Session) {
			$this->_session = $session;
		}
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
			throw new Exception($this->getAdapterError());
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
		
		$row = $this->_arrayForStorageFromEntity($mapping, $entity, false, true);
		$sql = $this->_processInsertQuery($mapping, $row);
		if (($result = $this->execute($sql)) === false) {
			throw new Exception($this->getAdapterError());
		}
		$id = $this->lastInsertId();
		
		$mapping = $this->_getMapper()->getMappingForEntityClass($class);
		if ($mapping->hasDescendents()) {
			$descendent = $mapping->getDescendentMappingFromEntity($entity);
			if (!is_null($descendent)) {
				$row = $this->_arrayForStorageFromEntity($descendent, $entity, false, true);
				$sql = $this->_processInsertQuery($descendent, $row);
				if (($result = $this->execute($sql)) === false) {
					throw new Exception($this->getAdapterError());
				}
			}
		}
		
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
			throw new Exception($this->getAdapterError());
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
	
	public function _update($entity) {
		$row = $this->getUnitOfWork()->getChanges($entity);
		if (count($row) <= 0) {
			return true;
		}
		
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$pk = $this->_getMapper()->getPrimaryKey($class);
			
		$sql = $this->_processUpdateQuery($class, $row, $entity->$pk);
		$result = $this->execute($sql);
		if ($result === false) {
			throw new Exception($this->getAdapterError());
		}
				
		return true;
	}
	
	protected function _processUpdateQuery(SimDAL_Mapper_Entity $mapping, $data, $id) {
		$pk = $mapping->getPrimaryKey();
		
		$sql = "UPDATE ".$this->_quoteIdentifier($mapping->getTable())." SET ";
		
		$columns = $mapping->getColumns();
		
		foreach ($data as $key=>$value) {
			$sql .= $this->_quoteIdentifier($columns[$key]->getColumn())." = ".$this->_transformData($columns[$key], $value, $mapping) . ",";
		}
		$sql = substr($sql,0,-1) . " WHERE `$pk`=$id";
		
		return $sql;
	}
	
	protected function _processInsertQuery(SimDAL_Mapper_Entity $mapping, $data) {
		$sql = "INSERT INTO ".$this->_quoteIdentifier($mapping->getTable())." (`".implode('`,`',array_keys($data))."`) VALUES (".implode(',',$data).")";
		
		return $sql;
	}
	
	protected function _processDeleteQuery(SimDAL_Mapper_Entity $mapping, $id) {
		$pk = $mapping->getPrimaryKeyColumn();
		$sql = "DELETE FROM ".$this->_quoteIdentifier($mapping->getTable())." WHERE `{$pk->getColumn()}`=".$id;
		
		return $sql;
	}
	
	public function insert($entity) {
		$this->getUnitOfWork()->add($entity);
	}
	
	public function delete($entity) {
		$this->getUnitOfWork()->delete($entity);
	}
	
	public function deleteById($id, $entity) {
		$this->getUnitOfWork()->delete(
			$id,
			$this->_getMapper()->getTable(
				$this->_getClass($entity)
			)
		);
	}
	
	public function deleteByColumn($class, $value, $column) {
		return $this->getUnitOfWork()->delete($value, $class, $column);
	}

	public function updateMultiple($class, $data) {
		if (is_array($data) && count($data) > 0) {
			foreach ($data as $id=>$entity) {
				$row = $this->getUnitOfWork()->getChanges($entity);
				if (count($row) <= 0) {
					continue;
				}
				
				$sql = $this->_processUpdateQuery($class, $row, $id);
				$result = $this->execute($sql);
				if ($result === false) {
					throw new Exception($this->getAdapterError());
				}
				$this->_resolveEntityDependencies($entity);
			}
		}
		
		return true;
	}
	
	public function deleteMultiple($class, $keys) {
		if (empty($keys)) {
			return true;
		}
		
		$sql = $this->_processMultipleDeleteQueries($class, $keys);
		
		$result = $this->execute($sql);
		
		if ($result === false) {
			throw new Exception($this->getAdapterError());
		}
		
		return true;
	}
	

	protected function _returnEntities($rows, $class) {
		$entities = array();
		
		foreach ($rows as $row) {
			$entity = $this->_returnEntity($row, $class);
			if (!$entityClass) {
				$entityClass = $this->_getMapper()->getClassFromEntity($entity);
				$pk = $this->_getMapper()->getPrimaryKey($entityClass);
				$pk_getter = 'get' . $pk;
			}
			$entities[$entity->$pk_getter()] = $entity;
		}
		
		$collection = new SimDAL_Collection($entities);
		
		return $collection;
	}
	
	protected function _returnEntity($row, $class) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$entity = $this->_entityFromArray($row, $class);
		$pk_getter = 'get' . ucfirst($pk);
		if ($this->_getSession()->isLoaded($class, $entity->$pk_getter())) {
			return $this->_getSession()->getLoaded($class, $entity->$pk_getter());
		}
		
		$this->_getSession()->updateEntity($entity);
		return $entity;
	}
	
	protected function _entityFromArray($row, &$class) {
		$entityClass = $this->_getMapper()->getTypeMorphClass($class, $row);
		if ($entityClass == $class) {
		    $mapping = $this->_getMapper()->getMappingForEntityClass($class);
		    $entityClass = $mapping->getDescendentClass($row);
		}
		
		$entityProxyClass = $entityClass . 'SimDALProxy';
		$entity = new $entityProxyClass(array(), $this->_getSession());
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$curedDate = array();
		foreach ($this->_getMapper()->getColumnData($class) as $property=>$column) {
			if (!array_key_exists($column[0], $row)) {
				continue;
			}
			$value = $row[$column[0]];
			$value = !is_null($value) && get_magic_quotes_runtime() ? stripslashes($value) : $value;
			switch($column[1]) {
				case 'binary':
				case 'blob': $value = base64_decode($value); break;
				case 'varchar':
				case 'text':
				case 'date':
				case 'datetime': break;
				case 'float': $value = (float)$value; break;
				case 'int': $value = (int)$value; break;
			}
			if ($column[1] == 'binary' || $column[1] == 'binary') {
				$value = base64_decode($value);
			}
			$curedData[$property] = $value;
		}
		
		if ($this->_getMapper()->hasDescendants($class)) {
			$typeField = $this->_getMapper()->getDescendantTypeField($class);
			$prefix = $this->_getMapper()->getDescendantClassPrefix($class);
			foreach ($this->_getMapper()->getDescendantColumnData($class, $prefix.ucfirst($entity->$typeField)) as $property=>$column) {
				if (!array_key_exists($column[0], $row)) {
					continue;
				}
				$curedDate[$property] = $row[$column[0]];
			}
		}
		
		$entity = new $entityProxyClass($curedData, $this->_getSession());
		
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
	
	public function returnQueryAsRows($sql, $class) {
		return $this->_returnResultRowsAsArray($sql);
	}
	
	public function returnQueryAsObjects($sql, $class) {
		return $this->_returnResultRows($sql, $class);
	}
	
	public function returnQueryResult(SimDAL_Query $query, $lockRows=false) {
		$sql = $this->_queryToString($query);
		
		if ($query->limit() == 1) {
			return $this->_returnResultRow($sql, $query->getClass(), $lockRows);
		} else {
			return $this->_returnResultRows($sql, $query->getClass(), $lockRows);
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
	
	protected function _transformData(SimDAL_Mapper_Column $column, $value, SimDAL_Mapper_Entity $mapping) {
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
				$array[$column->getColumn()] = $this->_transformData($key, $entity->$method(), $class);
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
				$array[$column->getColumn()] = $this->_transformData($column, $entity->$method(), $mapping);
			} else {
				$array[$column->getColumn()] = $entity->$method();
			}
		}
		
		return $array;
	}
	
	abstract public function execute($sql);
	
	abstract public function startTransaction();
	
	abstract public function commitTransaction();
	
	abstract public function rollbackTransaction();
	
	abstract public function getAdapterError();
	
	abstract public function escape($value);
	
	abstract protected function _returnResultRow($sql, $class=null);
	
	abstract protected function _returnResultRows($sql, $class);
	
	abstract protected function _quoteIdentifier($column);
	
	abstract protected function _queryToString(SimDAL_Query $query);
	
}