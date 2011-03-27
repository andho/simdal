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

class SimDAL_Query_OrderBy {
	
	protected $column;
	protected $query;
	protected $type;
	
	public function __construct(SimDAL_Mapper_Column $column, SimDAL_Query $query) {
		$this->column = $column;
		$this->query = $query;
	}
	
	public function getValue() {
		return $this->column;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function descending() {
		$this->type = 'descending';
		
		return $this->query;
	}
	
	public function ascending() {
		$this->type = 'ascending';
		
		return $this->query;
	}
	
	public function fetch($limit=null, $offset=null) {
		return $this->query->fetch($limit, $offset);
	}
	
}