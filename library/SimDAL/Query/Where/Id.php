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

class SimDAL_Query_Where_Id implements SimDAL_Query_Where_Interface {
	
	/**
	 * @var SimDAL_Mapper_Entity
	 */
	protected $_entity;
	protected $_id;
	
	public function __construct($entity, $id) {
		$this->_entity = $entity;
		$this->_id = $id;
	}
	
	public function getLeftValue() {
		return $this->_entity->getPrimaryKeyColumn();
	}
	
	public function getRightValue() {
		return $this->_id;
	}
	
	public function getOperator() {
		return '=';
	}
	
	public function getProcessMethod() {
		return 'WhereId';
	}
	
	public function getOperator() {
		return '=';
	}
	
}