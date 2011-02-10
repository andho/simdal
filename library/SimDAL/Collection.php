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