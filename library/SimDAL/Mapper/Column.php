<?php

class SimDAL_Mapper_Column {
	
	protected $_class;
	protected $_table;
	protected $_property;
	protected $_fieldName;
	protected $_dataType;
	protected $_primaryKey;
	protected $_autoIncrement;
	
	public function __construct($class, $table, $property, $fieldname, $datatype, $primarykey=false, $autoincrement=false) {
		$this->_class = $class;
		$this->_table = $table;
		$this->_property = $property;
		$this->_fieldName = $fieldname;
		$this->_dataType = $datatype;
		$this->_primaryKey = $primarykey;
		$this->_autoIncrement = $autoincrement;
	}
	
	public function getTable() {
		return $this->_table;
	}
	
	public function getClass() {
		return $this->_class;
	}
	
	public function getProperty() {
		return $this->_property;
	}
	
	public function getColumn() {
		return $this->_fieldName;
	}
	
}