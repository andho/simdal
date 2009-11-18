<?php

class SimDAL_UnitOfWork {
	
	private $_mapper = null;
	private $_adapter = null;
	
	private $_new = array();
	private $_modified = array();
	private $_actual = array();
	private $_delete = array();
	
	public function __construct($adapter=null, $mapper=null) {
		if ($adapter instanceof SimDAL_Persistence_AdapterInterface) {
			$this->_adapter = $adapter;
		} else if (self::$_defaultAdapter instanceof SimDAL_Persistence_AdapterInterface) {
			$this->_adapter = self::$_defaultAdapter;
		} else {
			throw new Simdal_PersistenceAdapterIsNotSetException();
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
		if (!$this->_entityIsNew($entity)) {
			return false;
		}
		
		$class = $this->_getClass($entity);
		$table = $this->_mapper->getTable($class);
		
		$this->_new[$table][] = $entity;
	}
	
	public function getNew() {
		return $this->_new;
	}
	
	protected function _entityIsNew($entity) {
		return true;
	}
	
	public function update($entity, $actual_data) {
		/*if ($this->_entityIsNew($entity)) {
			return false;
		}*/
		
		$class = $this->_getClass($entity);
		$table = $this->_mapper->getTable($class);
		
		$this->_modified[$table][$entity->id] = $entity;
		$this->_actual[$table][$entity->id] = $actual_data;
	}
	
	public function getChanges() {
		$data = array();
		
		foreach ($this->_modified as $table=>$entities) {
			foreach ($entities as $id=>$entity) {
				if (!array_key_exists($id, $this->_actual[$table])) {
					continue;
				}
				
				foreach ($entity as $key=>$value) {
					if ($entity->$key === $this->_actual[$table][$id][$key]) {
						continue;
					}
					
					$data[$table][$id][$key] = $entity->$key;
				}
			}
		}
		
		return $data;
		
	}
	
	public function delete($entity, $table=null) {
		if (is_object($entity)) {
			$class = $this->_getClass($entity);
			$table = $this->_mapper->getTable($class);
			
			$this->_delete[$table][$entity->id] = $entity;
		} else {
			if (is_null($table)) {
				return false;
			}
			$this->_delete[$table][$entity] = $entity;
		}
	}
	
	public function getDeleted() {
		return $this->_delete;
	}
	
	public function commit() {
		foreach ($this->_new as $table=>$entities) {
			$this->_insertEntities($entities, $table);
		}
	}
	
	protected function _insertEntities($entities, $table) {
		if (!is_array($entities) || count($entities) <= 0) {
			return false;
		}
		
		$columnData = $this->_mapper->getColumnData($this->_getClass($entities[0]));
		
		if (count($entities) == 1) {
			$this->_resolveManyToOneRelationsForInsert($entities[0]);
			$id = $this->_adapter->insert($table, $this->_arrayForStorageFromEntity($entities[0]));
			$entities[0]->id = $id;
		} else {
			$sql = "INSERT INTO `$table` VALUES ";
			foreach ($entities as $entity) {
				$sql .= "(";
				foreach ($this->_arrayForStorageFromEntity($entity, true, true) as $value) {
					$sql .= "$value,";
				}
				$sql = substr($sql,0,-1) . "),";
			}
			$sql = substr($sql, 0, -1);
			
			$count = $this->_adapter->query($sql);
		}
	}

	protected function _arrayForStorageFromEntity($entity, $includeNull = false, $transformData=false) {
		$array = array();
		
		foreach($this->_mapper->getColumnData($this->_getClass($entity)) as $key=>$value) {
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
	
	protected function _arrayForStorageFromEntityChangesOnly($entity) {
		$array = array();
		$id = $entity->id;
		
		foreach ($this->_getColumnData() as $key=>$value) {
			if ($entity->$key !== $this->_cleanData[$id][$key]) {
				$array[$value[0]] = $entity->$key;
			}
		}
		
		return $array;
	}
	
	protected function _resolveRelations($entity) {
		
	}
	
	protected function _resolveManyToOneRelationsForInsert($entity) {
		$class = $this->_getClass($entity);
		
		foreach ($this->_mapper->getManyToOneRelations($this->_getClass($entity)) as $relation) {
			$getMethod = 'get'.$relation[1];
			$setMethod = 'set'.$relation[1];
			if (!method_exists($entity, $getMethod)) {
				continue;
			}
			
			$relation_entity = $entity->$getMethod();
			
			if (is_null($relation_entity)) {
				continue;
			}
			
			$this->_resolveManyToOneRelationsForInsert($relation_entity);
			
			$table = $this->_mapper->getTable($relation[1]);
			$id = $this->_adapter->insert($table, $this->_arrayForStorageFromEntity($relation_entity));
			
			$relation_entity->id = $id;
			
			$entity->$setMethod($relation_entity);
		}
	}
	
	protected function _getClass($entity) {
		$class = get_parent_class($entity);
		if (!$class) {
			$class = get_class($entity);
		}
		
		return $class;
	}
	
}