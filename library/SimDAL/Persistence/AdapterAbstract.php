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
	
	public function _insert($entity) {
		$class = $this->_getMapper()->getClassFromEntity($entity);
		$row = $this->_arrayForStorageFromEntity($entity, false, true);
		$sql = $this->_processInsertQuery($class, $row);
		if (($result = $this->execute($sql)) === false) {
			$this->_setError($this->getAdapterError());
			return false;
		}
		
		$id = $this->lastInsertId();
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$entity->$pk = $id;
		
		return true;
	}
	
	public function getAll($class) {
		$this->_connect();
		
		$sql = $this->_processGetAllQuery($class);
		
		return $this->_returnResultRows($sql, $class);
	}
	
	public function findById($class, $id) {
		$entity = $this->getUnitOfWork()->getLoaded($class, $id);
		if (!is_null($entity)) {
			return $entity;
		}
		$table = $this->_getMapper()->getTable($class);
		$property = $this->_getMapper()->getPrimaryKey($class);
		$column = $this->_getMapper()->getColumn($class, $property);
		$column = $column[0];
		
		$sql = $this->_processFindByIdQuery($table, $column, $id);
		
		return $this->_returnResultRow($sql, $class);
	}

	public function findByColumn($class, $value, $column, $limit=1) {
		$table = $this->_getMapper()->getTable($class);
		$property = $this->_getMapper()->getColumn($class, $column);
		$this->_connect();
		
		if (is_string($value)) {
			$value = "'$value'";
		}
		
		$sql = $this->_processFindByColumnQuery($table, $property[0], $value, $limit);
		
		if ($limit == 1) {
			return $this->_returnResultRow($sql, $class);
		}
		
		return $this->_returnResultRows($sql, $class);
	}
	
	public function findBy($class, array $keyValuePairs, $limit=1) {
		$table = $this->_getMapper()->getTable($class);
		$this->_connect();
		
		if (count($keyValuePairs) == 0) {
			return false;
		}
		
		$where = array();
		foreach ($keyValuePairs as $key=>$value) {
			$column = $this->_getMapper()->getColumn($class, $key);
			//$where[] = "`{$column[0]}` = '$value'";
			if (is_null($value)) {
				$where[] = $this->_quoteIdentifier($column[0]) . " IS NULL";
			} else {
				$where[] = $this->_quoteIdentifier($column[0]) . " = " . $this->_transformData($key, $value, $class);
			}
		}
		
		$sql = $this->_processFindByQuery($table, $where, $limit);
		
		if ($limit == 1) {
			return $this->_returnResultRow($sql, $class);
		}
		
		return $this->_returnResultRows($sql, $class);
	}
	
	public function findByEither($class, array $keyValuePairs, $limit=1) {
		$table = $this->_getMapper()->getTable($class);
		$this->_connect();
		
		if (count($keyValuePairs) == 0) {
			return false;
		}
		
		$where = array();
		foreach ($keyValuePairs as $key=>$value) {
			$column = $this->_getMapper()->getColumn($class, $key);
			$where[] = "`{$column[0]}` = '$value'";
		}
		
		if (!is_null($limit)) {
			$limit = " LIMIT $limit";
		}
		
		$sql = $this->_processFindByEither($table, $where, $limit);
		
		return $this->_returnResultRows($sql, $class);
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
			$this->_errorMessages['dberror'] = $this->getError();
			return false;
		}
				
		return true;
	}
	
	protected function _processMultipleDeleteQueries($class, $keys) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		foreach ($keys as $key=>$value) {
			if (is_numeric($key)) {
				if (!isset($where['byid'])) $where['byid'] = array();
				$where['byid'][] = $value;
			} else {
				$where[$key] = $value;
			}
		}
		
		$wherecolumns = array();
		if (isset($where['byid'])) {
			$column = $this->_getMapper()->getColumn($class, $pk);
			$whereid = $this->_whereRange($column[0], $where['byid']);
			$wherecolumns['byid'] = $whereid;
		}
		
		foreach ($where as $key=>$value) {
			if ($key == 'byid') {
				continue;
			}
			$column = $this->_getMapper()->getColumn($class, $key);
			$wherecolumns[$key] = $this->_whereRange($column[0], $this->_transformRow($value, $class, $key));
		}
		
		$where = implode(" OR ", $wherecolumns); 
		
		$sql = "DELETE FROM `$table` WHERE $where";
		
		return $sql;
	}
	
	protected function _processUpdateQuery($class, $data, $id) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$pk = $this->_getMapper()->getColumn($class, $pk);
		$pk = $pk[0];
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "UPDATE ".$this->_quoteIdentifier($table)." SET ";
		
		$columns = $this->_getMapper()->getColumnData($class);
		
		foreach ($data as $key=>$value) {
			$sql .= $this->_quoteIdentifier($columns[$key][0])." = ".$this->_transformData($key, $value, $class) . ",";
		}
		$sql = substr($sql,0,-1) . " WHERE `$pk`=$id";
		
		return $sql;
	}
	
	protected function _processInsertQuery($class, $data) {
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO ".$this->_quoteIdentifier($table)." (`".implode('`,`',array_keys($data))."`) VALUES (".implode(',',$data).")";
		
		return $sql;
	}
	
	protected function _processMultipleInsertQueries($class, $data) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$table = $this->_getMapper()->getTable($class);
		
		$sql = "INSERT INTO `$table` (`".implode('`,`',$this->_getMapper()->getColumnData($class))."`) VALUES ";
		
		foreach ($data as $row) {
			$sql .= "(" . implode(',', $this->_transaformRow($row, $class)) . "),";
		}
		$sql = substr($sql,0,-1);
		
		return $sql;
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

	public function insertMultiple($class, $data) {
		if (!is_array($data) || count($data) <= 0) {
			return true;
		}
 		foreach ($data as $entity) {
			$row = $this->_arrayForStorageFromEntity($entity, false, true);
			$sql = $this->_processInsertQuery($class, $row);
			if (($result = $this->execute($sql)) === false) {
				$this->_setError($this->getAdapterError());
				return false;
			}
			$id = $this->lastInsertId();
			$entity->id = $id;
			$this->_resolveEntityDependencies($entity);
			$this->getUnitOfWork()->updateCleanEntity($entity);
		}
		
		return true;
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
					$this->_errorMessages['dberror'] = $this->getAdapterError();
					return false;
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
			$this->_errorMessages['dberror'] = $this->getAdapterError();
			return false;
		}
		
		return true;
	}
	
	public function commit() {
		$this->_processEntities();
		
		$priority = $this->_getMapper()->getClassPriority();
		
		$this->startTransaction();
		$commit = true;
		
		foreach ($priority as $class) {
			if (array_key_exists($class, $this->_deletes) && !$this->deleteMultiple($class, $this->_deletes[$class])) {
				$commit = false;
				break;
			}
			
			if (array_key_exists($class, $this->_inserts) && !$this->insertMultiple($class, $this->_inserts[$class])) {
				$commit = false;
				break;
			}
			
			if (array_key_exists($class, $this->_updates) && !$this->updateMultiple($class, $this->_updates[$class])) {
				$commit = false;
				break;
			}
		}
		
		if ($commit) {
			$this->commitTransaction();
		} else {
			$this->rollbackTransaction();
		}
		
		$this->getUnitOfWork()->clearAll();
		
		$this->_inserts = array();
		$this->_updates = array();
		$this->_deletes = array();
		
		return $commit;
	}

	protected function _returnEntities($rows, $class) {
		$collection = new SimDAL_Collection();
		
		foreach ($rows as $row) {
			$entity = $this->_returnEntity($row, $class);
			$entityClass = $this->_getMapper()->getClassFromEntity($entity);
			$pk = $this->_getMapper()->getPrimaryKey($entityClass);
			$collection[$entity->$pk] = $entity;
		}
		
		$collection->setPopulated(true);
		
		return $collection;
	}
	
	protected function _returnEntity($row, $class) {
		$pk = $this->_getMapper()->getPrimaryKey($class);
		$entity = $this->_entityFromArray($row, $class);
		if ($this->getUnitOfWork()->isLoaded($class, $entity->$pk)) {
			return $this->getUnitOfWork()->getLoaded($class, $entity->id);
		}
		
		$this->getUnitOfWork()->updateCleanEntity($entity);
		return $entity;
	}
	
	protected function _entityFromArray($row, &$class) {
		$entityClass = $this->_getMapper()->getTypeMorphClass($class, $row);
		if ($entityClass == $class) {
			$entityClass = $this->_getMapper()->getDescendantClass($class, $row);
		}
		
		$entity = new $entityClass();
		$class = $this->_getMapper()->getClassFromEntity($entity);
		foreach ($this->_getMapper()->getColumnData($class) as $property=>$column) {
			if (!property_exists($entity, $property)) {
				continue;
			}
			if (!array_key_exists($column[0], $row)) {
				continue;
			}
			if ($column[1] == 'binary' || $column[1] == 'binary') {
				$row[$column[0]] = base64_decode($row[$column[0]]);
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
					
					$key = isset($relation[2]['key']) ? $relation[2]['key'] : 'id';
					if (isset($relation[2]['key'])) {
						$key = $relation[2]['key'];
					}
					$relationEntities = $entity->$getter();
					if (count($relationEntities) > 0) {
						foreach ($entity->$getter() as $relationEntity) {
							$pk = $this->_getMapper()->getPrimaryKey($class);
							$actual = $this->getUnitOfWork()->getActual($class, $entity->$pk);
							if (!is_null($actual)) {
								if ($entity->$key == $actual->$key) {
									continue;
								}
								if ($relationEntity->$fk != $actual->key) {
									continue;
								}
							}
							if (is_null($actual) && $relationEntity->$fk != -1 && $relationEntity->$fk != null) {
								continue;
							}
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
		return $this->_returnResultRowsAsArray($sql);
	}
	
	public function returnQueryAsObjects($sql, $class) {
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
				error_log(base64_decode($output));
				return $output;
				break;
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
	
	abstract public function execute($sql);
	
	abstract public function startTransaction();
	
	abstract public function commitTransaction();
	
	abstract public function rollbackTransaction();
	
	abstract public function getAdapterError();
	
	abstract public function escape($value);
	
	abstract protected function _returnResultRow($sql, $class=null);
	
	abstract protected function _returnResultRows($sql, $class);
	
	abstract protected function _quoteIdentifier($column);
	
}