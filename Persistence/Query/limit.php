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

class SimDAL_Persistence_Query_Limit {
	
	protected $_limit;
	protected $_offset = null;
	
	public function __construct($limit, $offset = null) {
		if (!is_int($limit) || ($offset && !is_int($offset) ) )
			throw new Exception('Limit or Offset cannot be a non integer');
		
		$this->_limit = $limit;
		
		if ($offset) {
			$this->_offset = $offset;
		}
	}
	
	public function __toString() {
		if ($this->_offset == null) {
			return "LIMIT " . $this->_limit;
		} else {
			return "LIMIT " . $this->_offset . ", " . $this->_limit;
		}
	}
	
}