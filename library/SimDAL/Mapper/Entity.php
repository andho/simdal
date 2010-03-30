<?php

class SimDAL_Mapper_Entity implements Countable, ArrayAccess, Iterator {

	protected $_table;
	protected $_class;
	protected $_columns = array();
	protected $_columnsRawData = array();
	protected $_primaryKey;
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
	
	public function __construct($class, $map) {
		$this->_class = $class;
		$this->_table = $map['table'];
		$this->_columnsRawData = $map['columns'];
		$this->_associations = $map['associations'];
		$this->_typeBinding = $map['type_binding'];
		$this->_descendants = $map['descendants'];
		$this->_descendantTypeField = $map['descendantTypeField'];
		$this->_descendantClassNamePrefix = $map['descendantClassNamePrefix'];
		
		$this->_setupColumns();
		$this->_setupAssociations();
	}
	
	public function getTable() {
		return $this->_table;
	}
	
	public function getClass() {
		return $this->_class;
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
	
	/**
	 * @return SimDAL_Mapper_Column
	 */
	public function getPrimaryKeyColumn() {
		return $this->_columns[$this->getPrimaryKey()];
	}
	
	public function getPrimaryKey() {
		return $this->_primaryKey;
	}
	
	public function getAssociations() {
		return $this->_associations;
	}

	public function getDescendants() {
		return $this->_descendants;
	}
	
	protected function _setupColumns() {
		foreach ($this->_columnsRawData as $property=>$column_data) {
			$this->_columns[$property] = new SimDAL_Mapper_Column($this->getClass(), $this->getTable(), $property, $column_data[0], $column_data[1], $column_data[2]['pk'], $column_data[2]['autoIncrement']);
			if ($column_data[2]['pk'] === true) {
				$this->_primaryKey = $property;
			}
		}
	}
	
	protected function _setupAssociations() {
		$associations = $this->_associations;
		foreach ($associations as $association_data) {
			$association = new SimDAL_Mapper_Association($this, $association_data);
			$this->_associations[$association->getIdentifier()] = $association;
		}
	}
	
}