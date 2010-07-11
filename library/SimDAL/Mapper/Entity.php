<?php

class SimDAL_Mapper_Entity implements Countable, ArrayAccess, Iterator {

	protected $_schema;
	protected $_table;
	protected $_class;
	protected $_columns = array();
	protected $_columnsRawData = array();
	protected $_hasAliases = false;
	protected $_primaryKey;
	protected $_associations = array();
	protected $_typeBinding = array();
	protected $_descendents = array();
	protected $_descendentTypeField;
	protected $_descendentClassNamePrefix;
	protected $_mapper;
	
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
	
	public function __construct($class, $map, SimDAL_Mapper $mapper) {
		$this->_class = $class;
		$this->_schema = isset($map['schema']) ? $map['schema'] : '';
		$this->_table = $map['table'];
		$this->_columnsRawData = $map['columns'];
		if (isset($map['associations']) && is_array($map['associations'])) {
			$this->_associations = $map['associations'];
		}
		$this->_typeBinding = isset($map['type_binding']) ? $map['type_binding'] : '';
		if (isset($map['descendents']) && is_array($map['descendents'])) {
			$this->_descendents = $map['descendents'];
		}
		$this->_descendentTypeField = isset($map['descendentTypeField']) ? $map['descendentTypeField'] : '';
		$this->_descendentClassNamePrefix = isset($map['descendentClassNamePrefix']) ? $map['descendentClassNamePrefix'] : $this->getClass() . '_';
		$this->_mapper = $mapper;
		
		$this->_setupColumns();
		$this->_setupAssociations();
		$this->_setupDescendents();
	}
	
	public function getSchema() {
		return $this->_schema;
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
	
	public function hasAliases() {
	    return $this->_hasAliases;
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

	public function getDescendents() {
		return $this->_descendents;
	}
	
	public function getDescendentClass($row) {
	  $type_field = $this->_descendentTypeField;
	  $prefix = $this->getDescendentPrefix();
	  $class = $prefix . ucfirst($row[$type_field]);
	  
	  if (isset($this->_descendents[$class])) {
	    return $class;
	  }
	  
	  return $this->getClass();
	}
	
	public function getDescendentPrefix() {
	    if (!isset($this->_descendentClassNamePrefix) && $this->_descendentClassNamePrefix === false) {
	      return false;
	    }
	    
	    if ($this->_descendentClassNamePrefix === true) {
	      return $this->getClass() . '_';
	    }
	    
	    return $this->_descendentClassNamePrefix;
	}
	
	protected function _setupColumns($descendent=true) {
		if (!is_array($this->_columnsRawData)) {
			if (!$descendent) {
				throw new Exception("No column data given for Entity Mapping");
			} else {
				return;
			}
		}
		foreach ($this->_columnsRawData as $property=>$column_data) {
			$this->_columns[$property] = new SimDAL_Mapper_Column($this->getClass(), $this->getTable(), $property, $column_data[0], $column_data[1], $column_data[2]['pk'], $column_data[2]['autoIncrement'], $column_data[2]['alias']);
			if (array_key_exists(2, $column_data)) {
				if (array_key_exists('pk', $column_data[2]) && $column_data[2]['pk'] === true) {
					$this->_primaryKey = $property;
				}
				if (array_key_exists('alias', $column_data[2]) && !$this->hasAliases()) {
				    $this->_hasAliases = true;
				}
			}
		}
	}
	
	/**
	 * @return SimDAL_Mapper
	 */
	public function getMapper() {
		return $this->_mapper;
	}
	
	protected function _setupAssociations() {
		$associations = $this->_associations;
		$this->_associations = array();
		foreach ($associations as $association_data) {
			$association = new SimDAL_Mapper_Association($this, $association_data);
			$this->_associations[$association->getIdentifier()] = $association;
		}
	}
	
	protected function _setupDescendents() {
	  $descendents = $this->_descendents;
	  $this->_descendents = array();
	  foreach ($descendents as $class=>$descendent_data) {
	    $descendent = new SimDAL_Mapper_Descendent($this, $class, $descendent_data);
	    $this->_descendents[$descendent->getIdentifier()] = $descendent;
	  }
	}
	
}