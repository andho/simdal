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
	
	public function __construct(SimDAL_Mapper_Entity $entity, $property, $fieldname, $datatype, $primarykey=false, $autoincrement=false, $alias=null) {
		$this->_entity = $entity;
		$this->_class = $entity->getClass();
		$this->_table = $entity->getTable();
		$this->_schema = $this->getSchema();
		$this->_property = $property;
		$this->_fieldName = $fieldname;
		$this->_dataType = $datatype;
		$this->_primaryKey = $primarykey;
		$this->_autoIncrement = $autoincrement;
		$this->_alias = $alias;
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