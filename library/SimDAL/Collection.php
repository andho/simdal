<?php

class SimDAL_Collection implements Iterator, Countable, ArrayAccess {
	
	// iterator implementation
	private $_position = 0;
	private $_data = array();
	private $_keymap = array();
	private $_searchHash = array();
	private $_searchHashed = array();
	public function rewind() {
		$this->_position = 0;
	}
	public function current() {
		return $this->_data[$this->key()];
	}
	public function key() {
		if (!array_key_exists($this->_position, $this->_keymap)) {
			return null;
		}
		return $this->_keymap[$this->_position];
	}
	public function next() {
		return ++$this->_position;
	}
	public function valid() {
		$key = $this->key();
		
		if (is_null($key)) {
			return false;
		}
		
		if (!array_key_exists($key, $this->_data)) {
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
	// end of ArrayAccess implementation
	
	public function __construct($data) {
		foreach ($data as $key=>$value) {
			if (in_array($value, $this->_data)) {
				continue;
			}
			$this[$key] = $value;
		}
	}
	
	public function get($position) {
		if (!array_key_exists($position, $this->_keymap)) {
			return null;
		}
		
		return $this->_data[$this->_keymap[$position]];
	}
	
	public function search($property, $value) {
		if (!array_key_exists($property, $this->_searchHash)) {
			$this->_searchHash[$property] = array();
		}
		
		if ($this->_searchHashed[$property] !== true) {
			foreach ($this as $item) {
				if (!property_exists($item, $property)) {
					throw new Exception("Invalid property searched for in collection of type '".get_class($item)."'");
				}
				$this->_searchHash[$property][$item->$property] = $item;
			}
			$this->_searchHashed[$property] = true;
		}
		
		if (array_key_exists($value, $this->_searchHash[$property])) {
			return $this->_searchHash[$property][$value];
		}
		
		return null;
	}
	
	public function toArray() {
		return $this->_data;
	}
	
}