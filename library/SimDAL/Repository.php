<?php

class SimDAL_Repository {
	
	static protected $_defaultAdapter = null;
	
	static protected $_defaultMapper = null;
	
	protected $_adapter = null;
	
	protected $_mapper = null;
	
	protected $_class = null;
	
	protected $_errorMessages = array();
	
	static public function setDefaultAdapter($adapter) {
		if (!$adapter instanceof SimDAL_Persistence_AdapterAbstract) {
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
		if ($adapter instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = $adapter;
		} else if (self::$_defaultAdapter instanceof SimDAL_Persistence_AdapterAbstract) {
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
	
	public function getClass() {
		return $this->_class;
	}
	
	public function add($entity) {
		if (!$this->getAdapter()->insert($entity)){
			return false;
		}
		
		return true;
	}
	
	public function delete($entity) {
		return $this->getAdapter()->delete($entity);
	}
	
	/*public function getNew() {
		$uow = $this->_getUnitOfWork();
		$new = $uow->getNew();
		
		return $new[$this->_getTable()];
	}*/
	
	public function findById($id) {
		return $this->_adapter->findById($this->getClass(), $id);
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
	
	/**
	 * Return a unit of work object
	 *
	 * @return SimDAL_UnitOfWork
	 */
	protected function _getUnitOfWork() {
		return $this->getAdapter()->getUnitOfWork();
	}
	
	protected function _entitiesFromArray($rows) {
		if (is_null($rows)) {
			return null;
		}
		
		$class = $this->_class;
		
		$output = array();
		
		if (!is_array($rows[0])) {
			$rows = array($rows);
		}
		
		foreach ($rows as $row) {
			$entity = $this->_entityFromArray($row);
			
			$this->_loaded[$entity->id] = $entity;
			$this->_loadIntoUnitOfWork($entity);
			
			$output[] = $entity;
		}
		
		return $output;
	}
	
	protected function _loadIntoUnitOfWork($entity) {
		$uow = $this->_getUnitOfWork();
		
		if (!$uow->updateCleanEntity($entity)) {
			return false;
		}
		
		return $entity;
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
		$changes = $this->_getUnitOfWork()->getChanges();
		$table = $this->_getTable();
		
		return isset($changes[$table]) ? $changes[$table] : array();
	}
	
	public function getDeleted() {
		$deleted = $this->_getUnitOfWork()->getDeleted();
		
		return $deleted[$this->_getTable()];
	}
	
	public function revert($entity) {
		$this->_getUnitOfWork()->revert($entity);
	}
	
	public function commit() {
		// @todo put everythin into a unitofwork
		$return = $this->getAdapter()->commit();
		
		if (!$return) {
			$this->_errorMessages = $this->getAdapter()->getErrorMessages();
		}
		
		return $return;
	}
	
	/**
	 * return Adapter
	 *
	 * @return SimDAL_Persistence_AdapterAbstract
	 */
	public function getAdapter() {
		return $this->_adapter;
	}
	
	public function getErrorMessages() {
		return $this->_errorMessages;
	}
	
}