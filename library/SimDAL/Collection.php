<?php

class SimDAL_Collection implements Iterator, Countable, ArrayAccess {
	
	// iterator implementation
	private $_position = 0;
	private $_data = array();
	private $_keymap = array();
	public function rewind() {
		$this->_position = 0;
	}
	public function current() {
		return $this->_data[$this->_keymap[$this->key()]];
	}
	public function key() {
		return $this->_position;
	}
	public function next() {
		return ++$this->_position;
	}
	public function valid() {
		return isset($this->_data[$this->_keymap[$this->key()]]);
	}
	// end of iterator implementation
	// countable implementation
	public function count() {
		return count($this->_data);
	}
	// end of countable implementation
	// ArrayAccess implementation
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->_data);
	}
	public function offsetGet($offset) {
		if (!$this->offsetExists($offset)) {
			return null;
		}
		
		return $this->_data[$offset];
	}
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$offset = rand();
			$key = count($this->_data);
		} else if ($this->offsetExists($offset)) {
			$key = array_search($offset, $this->_keymap);
		} else {
			$key = count($this->_data);
		}
		$this->_data[$offset] = $value;
		$this->_keymap[$key] = $offset;
	}
	public function offsetUnset($offset) {
		if (!$this->offsetExists($offset)) {
			return false;
		}
		
		$key = array_search($offset, $this->_keymap);
		unset($this->_data[$offset]);
		array_slice($this->_keymap, $key);
	}
	
	static protected $_defaultAdapter = null;
	
	protected $_adapter = null;
	
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
		$this[] = $entity;
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