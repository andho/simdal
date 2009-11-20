<?php

class SimDAL_Repository {
	
	static protected $_defaultAdapter = null;
	
	static protected $_defaultMapper = null;
	
	protected $_adapter = null;
	
	protected $_mapper = null;
	
	protected $_class = null;
	
	protected $_new = array();
	
	protected $_loaded = array();
	
	protected $_delete = array();
	
	protected $_cleanData = array();
	
	protected $_errorMessages = array();
	
	static public function setDefaultAdapter($adapter) {
		if (!$adapter instanceof SimDAL_Persistence_AdapterInterface) {
			return false;
		}
		
		self::$_defaultAdapter = $adapter;
	}
	
	static public function setDefaultMapper($mapper) {
		if (!$mapper instanceof SimDAL_Mapper) {
			return false;
		}
		
		self::$_defaultMapper = $mapper;
	}
	
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
		if (in_array($entity, $this->_new) ) {
			return false;
		}
		
		$this->_new[] = $entity;
		
		return true;
	}
	
	public function delete($entity) {
		if (is_object($entity)) {
			$this->_delete[$entity->id] = $entity->id;
		} else {
			$this->_delete[$entity] = $entity;
		}
	}
	
	public function getNew() {
		return $this->_new;
	}
	
	public function findById($id) {
		$entity = $this->_getFromLoaded($id);
		if (!is_null($entity)) {
			return $entity;
		}
		
		$pk = $this->_mapper->getPrimaryKey($this->_class);
		
		$array = $this->_adapter->findById($this->_getTable(), $id, $pk);
		if (is_null($array)) {
			return null;
		}
		
		$class = $this->_class;
		
		$entity = $this->_entityFromArray($array);
		
		$this->_loaded[$entity->id] = $entity;
		$this->_insertIntoCleanData($entity);
		
		return $entity;
	}
	
	public function query($sql) {
		$rows = $this->_adapter->query($sql);
		$class = $this->_class;
		
		$collection = array();
		
		foreach ($rows as $row) {
			$entity = $this->_entityFromArray($row);
			
			$this->_loaded[$entity->id] = $entity;
			$this->_insertIntoCleanData($entity);
			
			$collection[$entity->id] = $entity;
		}
		
		return $collection;
	}
	
	protected function _getFromLoaded($id) {
		if ($this->_isLoaded($id)) {
			return $this->_loaded[$id];
		}
		
		return null;
	}
	
	protected function _isLoaded($id) {
		if (!array_key_exists($id, $this->_loaded)) {
			return false;
		}
		
		return true;
	}
	
	public function getChanges() {
		$array = array();
		
		foreach ($this->_loaded as $id=>$obj) {
			if (!array_key_exists($id, $this->_cleanData)) {
				continue;
			}
			
			foreach ($this->_cleanData[$id] as $key=>$value) {
				if ($value !== $obj->$key) {
					$array[$id][$key] = $obj->$key;
				}
			}
		}
		
		return $array;
	}
	
	public function getDeleted() {
		return $this->_delete;
	}
	
	public function revert($entity) {
		if (array_key_exists($entity->id, $this->_delete)) {
			unset($this->_delete[$entity->id]);
		}
		
		if (!array_key_exists($entity->id, $this->_cleanData)) {
			return;
		}
		
		foreach ($this->_cleanData[$entity->id] as $key=>$value) {
			$entity->$key = $value;
		}
	}
	
	public function commit() {
		// @todo put everythin into a unitofwork
		foreach ($this->_delete as $id) {
			$this->_delete($id);
		}
		
		foreach ($this->_loaded as $entity) {
			$this->_update($entity);
		}
		
		foreach ($this->_new as $entity) {
			if ($this->_insert($entity) === null) {
				// @todo rollback transaction
			}
		}
		
		if (count($this->_errorMessages) > 0) {
			return false;
		}
		
		return true;
	}
	
	protected function _insert($entity) {
		$data = $this->_arrayForStorageFromEntity($entity);
		
		if (($id = $this->_adapter->insert($this->_getTable(), $data)) === false) {
			$this->_errorMessages['insert'] = $this->_adapter->getError();
			return null;
		}
		
		$entity->id = $id;
		$this->_loaded[$entity->id] = $entity;
		$this->_insertIntoCleanData($entity);
		
		if (method_exists($this, '_postInsertHook')) {
			$this->_postInsertHook($entity->cardNumber);
		}
		
		return $id;
	}
	
	protected function _postInsertHook($entity) {
		$data = array(
			'date'=>date("Y-m-d"),
			'time'=>date("H:i:s"),
			'ip'=>$_SERVER['REMOTE_ADDRESS'],
			'username_authentic'=>$this->_user,
			'class'=>null,
			'customer_id' => $entity->cardNumber,
			'amount' => $entity->billAmount,
			'refnumber' => $entity->memoNumber,
			'type' => $entity->serviceType,
			'centre' => $entity->serviceProvider
		);
		$this->_adapter->insert('tbl_audit_transactions', $data);
	}
	
	protected function _update($entity) {
		$data = $this->_arrayForStorageFromEntityChangesOnly($entity);
		
		if (count($data) <= 0) {
			return;
		}
		
		$pk = $this->_mapper->getPrimaryKey($this->_class);
		
		$this->_adapter->update($this->_getTable(), $data, $entity->id, $pk);
		
		$this->_updateCleanData($entity);
	}
	
	protected function _delete($id) {
		$rows_affected = $this->_adapter->delete($this->_table, $id);
		
		unset($this->_delete[$id]);
		
		if (array_key_exists($id, $this->_loaded)) {
			unset($this->_loaded[$id]);
			unset($this->_cleanData[$id]);
		}
		
		return $rows_affected;
	}
	
	protected function _insertIntoCleanData($entity) {
		$data = array();
		
		foreach ($entity as $key=>$value) {
			$data[$key] = $value;
		}
		
		$this->_cleanData[$entity->id] = $data;
	}
	
	protected function _updateCleanData($entity) {
		$id = $entity->id;
		foreach ($entity as $key=>$value) {
			$this->_cleanData[$id][$key] = $value;
		}
	}
	
	/*protected function _arrayForStorageFromEntity($entity) {
		$array = array();
		
		foreach($this->_getColumnData() as $key=>$value) {
			$array[$value[0]] = $entity->$key;
		}
		
		return $array;
	}*/

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
	
	protected function _entityFromArray($array) {
		$class = $this->_class;
		
		$entity = new $class();
		foreach ($this->_getColumnData() as $key=>$value) {
			$entity->$key = $array[$value[0]];
		}
		
		return $entity;
	}
	
	protected function _getColumnData() {
		return $this->_mapper->getColumnData($this->_class);
	}
	
	protected function _getTable() {
		return $this->_mapper->getTable($this->_class);
	}
	
	public function getAdapter() {
		return $this->_adapter;
	}
	
	public function getErrorMessages() {
		return $this->_errorMessages;
	}
	
}