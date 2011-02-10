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

class SimDAL_Query_Limit {
	
	protected $_query;
	protected $_limit;
	protected $_offset;
	
	public function __construct($limit, $offset=null, $query=null) {
		$this->_query = $query;
		$this->_limit = $limit;
		$this->_offset = $offset;
	}
	
	public function __call($method, $args) {
		if (method_exists($this->_query, $method)) {
			return call_user_func_array(array($this->_query, $method), $args);
		}
		
		return false;
	}
	
	public function setLimit($limit) {
		if (is_null($limit)) {
			$this->_limit = null;
			$this->_offset = null;
		}
		if (is_numeric($limit) && $limit > 0) {
			$this->_limit = $limit;
		}
	}
	
	public function setOffset($offset) {
		if (is_numeric($offset) && $offset >= 0) {
			$this->_offset = $offset;
		}
	}
	
	public function getLimit() {
		return $this->_limit;
	}
	
	public function getOffset() {
		return $this->_offset;
	}
	
}