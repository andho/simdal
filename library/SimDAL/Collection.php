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
		if (!array_key_exists($this->_keymap[$this->key()])) {
			return false;
		}
		if (!array_key_exists($this->_data[$this->_keymap[$this->key()]])) {
			return false;
		}
		return true;
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
	
	protected $_populated = false;
	
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
	
	public function delete($entity) {
		for ($i=0; $i<count($this->_keymap); $i++) {
			if ($this->_data[$this->_keymap[$i]]->id == $entity->id) {
				unset($this->_data[$this->_keymap[$i]]);
				unset($this->_keymap[$i]);
			}
		}
	}
	
	/**
	 * returns Persistence adapter
	 *
	 * @return SimDAL_Persistence_AdapterAbstract
	 */
	public function getAdapter() {
		return $this->_adapter;
	}
	
	public function setPopulated($populated) {
		$this->_populated = (bool)$populated;
	}
	
	public function isPopulated() {
		return $this->_populated;
	}
	
}