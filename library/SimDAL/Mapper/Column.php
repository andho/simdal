<?php

class SimDAL_Mapper_Column {
	
	protected $_class;
	protected $_table;
	protected $_schema;
	protected $_property;
	protected $_fieldName;
	protected $_alias;
	protected $_dataType;
	protected $_primaryKey;
	protected $_autoIncrement;
	protected $_entity;
	
	public function __construct(SimDAL_Mapper_Entity $entity, $property, $data, $class=null, $table=null, $fieldname=null, $datatype=null, $primarykey=false, $autoincrement=false, $alias=null) {
		$this->_entity = $entity;
		$this->_class = $entity->getClass();
		$this->_table = $entity->getTable();
		$this->_schema = $this->getSchema();
		$this->_property = $property;
		$this->_fieldName = $data[0];
		$this->_dataType = $data[1];
		if (isset($data[2]) && is_array($data[2])) {
			$this->_primaryKey = isset($data[2]['pk']) ? $data[2]['pk'] : false;
			$this->_autoIncrement = isset($data[2]['autoIncrement']) ? $data[2]['autoIncrement'] : false;
			$this->_alias = isset($data[2]['alias']) ? $data[2]['alias'] : null;
		}
	}

	/**
	 * @return SimDAL_Mapper_Entity
	 */
	public function getEntity() {
		return $this->_entity;
	}
	
	public function getTable() {
		return $this->_table;
	}
	
	public function getClass() {
		return $this->_class;
	}
	
	public function getSchema() {
		return $this->_schema;
	}
	
	public function getProperty() {
		return $this->_property;
	}
	
	public function getColumn() {
		return $this->_fieldName;
	}
	
	public function hasAlias() {
	    return is_string($this->_alias) && $this->_alias != '';
	}
	
	public function getAlias() {
	    return $this->_alias;
	}
	
	public function getDataType() {
		return $this->_dataType;
	}
	
	public function isPrimaryKey() {
		return $this->_primaryKey;
	}
	
	public function isAutoIncrement() {
		return $this->_autoIncrement;
	}
	
}