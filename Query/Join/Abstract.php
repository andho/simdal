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

abstract class SimDAL_Query_Join_Abstract extends SimDAL_Query {
	
	protected $_parent;
	protected $_type;
	protected $_wheres;
	protected $_columns;
	
	public function __construct(SimDAL_Query $query, $type=null, $columns = null) {
		$this->_parent = $query;
		$this->_setupType($type);
		$this->_setupColumns($columns);
		$this->_setupWheres();
	}
	
	public function getType() {
		return $this->_type;
	}
	
	public function getColumns() {
	    return $this->_columns;
	}
	
	public function getJoinType() {
		switch ($this->_type) {
			case 'inner':
			default:
				return 'INNER';
		}
	}
	
	protected function _setupType($type) {
		if (is_null($type)) {
			$type = 'inner';
		}
		
		$this->_type = $type;
	}
	
	protected function _setupColumns($columns) {
		$this->_columns = $columns;
	}
	
	abstract public function getTable();
	
	abstract protected function _setupWheres();
	
}