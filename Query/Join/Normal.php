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
 * 
 * @author Amjad Mohamed <andhos@gmail.com>
 */

class SimDAL_Query_Join_Normal extends SimDAL_Query_Join_Abstract {
	
	protected $_parent;
	protected $_entity;
	protected $_associated_entity;
	
	public function __construct(SimDAL_Query $query, SimDAL_Mapper_Entity $entity, SimDAL_Mapper_Entity $associated_entity, $type=null, array $columns = array()) {
		$this->_parent = $query;
		$this->_from = $entity;
		$this->_entity = $entity;
		$this->_associated_entity = $associated_entity;
		
		parent::__construct($type, $columns);
	}
	
	public function getMapping() {
		return $this->_entity;
	}
	
	public function getTableColumns() {
		return $this->_entity->getColumns();
	}
	
	public function getTable() {
		return $this->_entity->getTable();
	}
	
	public function hasAliases() {
		return $this->_entity->hasAliases();
	}
	
	protected function _setupWheres() {
		
	}
	
}