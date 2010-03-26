<?php

class SimDAL_Mapper_Entity implements Countable, ArrayAccess, Iterator {

	protected $_table;
	protected $_columns = array();
	protected $_columnsRawData = array();
	protected $_associations = array();
	protected $_typeBinding = array();
	protected $_descendants = array();
	protected $_descendantTypeField;
	protected $_descendantClassNamePrefix;
	
	protected $_pointer = 0;
	protected $_keymap = array();
	
	public function current() {
		return $this->_columns[$this->_keymap[$this->key()]];
	}
	
	public function key() {
		return $this->_pointer;
	}
	
	public function next() {
		return $this->_pointer++;
	}
	
	public function previous() {
		return $this->_pointer--;
	}
	
	public function valid() {
		if (!array_key_exists($this->_pointer, $this->_keymap)) {
			return false;
		}
		
		if (!array_key_exists($this->_keymap[$this->_pointer], $this->_columns)) {
			return false;
		}
		
		return true;
	}
	
	public function rewind() {
		$this->_pointer = 0;
	}
	
	public function offsetExists($column) {
		return $this->hasColumn($column);
	}
	
	public function offsetGet($column) {
		return $this->getColumn($column);
	}
	
	public function offsetSet($column, $value) {
		return false;
	}
	
	public function offsetUnset($column) {
		return false;
	}
	
	public function count() {
		return count($this->_columns);
	}
	
	public function getTable() {
		return $this->_table;
	}
	
	public function getColumns() {
		return $this->_columns;
	}
	
	public function getColumn($column) {
		if (!$this->hasColumn($column)) {
			return false;
		}
		
		return $this->_columns[$column];
		return $this[$column];
	}
	
	public function hasColumn($column) {
		return isset($this->_columns[$column]);
		return isset($this[$column]);
	}
	
	public function getAssociations() {
		return $this->_associations;
	}

	public function getDescendants() {
		return $this->_descendants;
	}
	
}