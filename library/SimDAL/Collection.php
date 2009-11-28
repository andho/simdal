<?php

class SimDAL_Collection {
	
	static protected $_defaultAdapter = null;
	
	protected $_adapter = null;
	
	protected $items = array();
	
	static public function setDefaultAdapter($adapter) {
		if (!is_null($adapter) && !$adapter instanceof SimDAL_Persistence_AdapterAbstract ) {
			return false;
		}
		self::$_defaultAdapter = $adapter;
		
		return true;
	}
	
	public function __construct($adapter = null) {
		if ($adapter instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = $adapter;
		} else if (self::$_defaultAdapter instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = self::$_defaultAdapter;
		} else {
			throw new Simdal_PersistenceAdapterIsNotSetException();
		}
	}
	
	public function add($entity) {
		$this->getAdapter()->insert($entity);
		$this->items[] = $entity;
	}
	
	/**
	 * returns Persistence adapter
	 *
	 * @return SimDAL_Persistence_AdapterAbstract
	 */
	public function getAdapter() {
		return $this->_adapter;
	}
	
}