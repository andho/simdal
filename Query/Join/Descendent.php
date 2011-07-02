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

class SimDAL_Query_Join_Descendent extends SimDAL_Query_Join_Abstract {
	
    /**
     * 
     * @var SimDAL_Mapper_Descendent
     */
	protected $_descendant;
	protected $_type;
	protected $_wheres;
	protected $_columns = array();
	
	public function __construct($descendant, $type=null, array $columns = array()) {
		$this->_descendant = $descendant;
        
		parent::__construct($type, $columns);
	}
	
	public function hasAliases() {
	    return $this->_descendant->hasAliases();
	}
	
	public function getTableColumns() {
	    return $this->_descendant->getColumns();
	}
	
	public function getTable() {
	  return $this->_descendant->getTable();
	}
	
	protected function _setupWheres() {
	  $this->_wheres[] = new SimDAL_Query_Where_JoinDescendant($this->_descendant->getEntity(), $this->_descendant);
	}
	
}