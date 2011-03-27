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

class SimDAL_Query {
	
	const TYPE_SELECT = 'select';
	const TYPE_UPDATE = 'update';
	const TYPE_DELETE = 'delete';
	
	/**
	 * 
	 * @var SimDAL_Mapper_Entity
	 */
	protected $_from;
	protected $_where = array();
	protected $_join = array();
	protected $_columns = array();
	protected $_limit;
	protected $_parent;
	protected $_type;
	protected $_sets = array();
	protected $_orderBy = null;
	protected $_mapper;
	
	/**
	 * 
	 * @param SimDAL_Query_ParentInterface $parent A parent object of which the execute function will be called. Object must implement SimDAL_Query_ParentInterface
	 * @param String $type Type of query to create. Options are 'select', 'update' and 'delete'
	 */
	public function __construct(SimDAL_Query_ParentInterface $parent=null, $type=SimDAL_Query::TYPE_SELECT, SimDAL_Mapper $mapper) {
		$this->_parent = $parent;
		$this->_type = $type;
		$this->_limit = new SimDAL_Query_Limit(1, 0, $this);
		$this->_mapper = $mapper;
	}
	
	/**
	 * @return SimDAL_Mapper
	 */
	protected function _getMapper() {
		return $this->_mapper;
	}
	
	/**
	 * 
	 * @param SimDAL_Mapper_Entity $entity
	 * @param array $columns
	 * @return SimDAL_Query
	 */
	public function from(SimDAL_Mapper_Entity $entity, array $columns = array()) {
		if (is_string($entity)) {
			$entity = $this->_getMapper()->getMappingForEntityClass($entity);
		} else if (!$entity instanceof SimDAL_Mapper_Entity) {
			if (is_object($entity)) {
				$type = get_class($entity);
			}
			throw new Exception("Argument 1 passed to SimDAL_Query::from should be an instance of SimDAL_Mapper_Entity or string, '" . $type . "' given");
		}
	    $this->_columns = $columns;
		$this->_from = $entity;
		
		return $this;
	}

	/**
	 * 
	 * @param $column
	 * @param $value
	 * @return SimDAL_Query
	 */
	public function set($column, $value) {
		$column = $this->_from->getColumn($column);
		
		if (!($column)) {
			throw new Exception("Wrong column name specified for update");
		}
		
		$this->_sets[] = new SimDAL_Query_Set($this, $column, $value);
		
		return $this;
	}
	
	public function hasAliases() {
	    return $this->_from->hasAliases();
	}
	
	public function getColumns() {
	  return $this->_columns;
	}
	
	public function getTableColumns() {
	    $this->_from->getColumns();
	}
	
	/**
	 * @param string $id Primary Key of the entity
	 * @return SimDAL_Query
	 */
	public function whereIdIs($id) {
		$this->_where[] = new SimDAL_Query_Where_Id($this->_from, $id);
		
		return $this;
	}
	
	/**
	 * 
	 * @return SimDAL_Query_Where_Column
	 */
	public function whereColumn($column, $entity=null) {
		return $this->whereProperty($column, $entity);
	}
	
	public function whereProperty($property, $entity=null) {
		if (is_null($entity)) {
			$entity = $this->_from;
		} else if ($this->_from->getClass() == $entity) {
			$entity = $this->_from;
		}
		/* @var $entity SimDAL_Mapper_Entity */
		$column = $entity->getColumn($property);
		
		if (!$column) {
			throw new Exception("Property '$property' does not exist in Entity '" . $entity->getClass() . "'");
		}
		
		$where = new SimDAL_Query_Where_Column($entity, $column, $this);
		$this->_where[] = $where;
		
		return $where;
	}
	
	/**
	 * 
	 * @param unknown_type $join
	 * @return SimDAL_Query
	 */
	public function join($join) {
		if ($join instanceof SimDAL_Mapper_Descendent) {
			$this->_join[] = new SimDAL_Query_Join_Descendent($join);
		}
		
		return $this;
	}
	
	/**
	 * 
	 * @param integer $limit
	 * @param integer $offset
	 * @return SimDAL_Query
	 */
	public function limit( $limit=null,  $offset=null) {
		 if (is_null($limit) && $offset == null) {
		 	return $this->_limit->getLimit();
		 }
		 
		 if ($limit == 0) {
		 	$limit = null;
		 }
		 
		 $this->_limit->setLimit($limit);
		 
		 if (!is_null($limit)) {
		 	$this->_limit->setOffset($offset);
		 }
		 
		 return $this;
	}
	
	/**
	 * 
	 * @param string $column
	 * @return SimDAL_Query_OrderBy
	 */
	public function orderBy($column) {
		$column = $this->_from->getColumn($column);
		$this->_orderBy = new SimDAL_Query_OrderBy($column, $this);
		
		return $this->_orderBy;
	}
	
	public function getOrderBy() {
		return $this->_orderBy;
	}
	
	/**
	 * @return SimDAL_Mapper_Entity
	 */
	public function getFrom() {
		return $this->_from->getTable();
	}
	
	public function getSchema() {
		return $this->_from->getSchema();
	}
	
	public function getMapping() {
		return $this->_from;
	}
	
	public function getJoins() {
		return $this->_join;
	}
	
	public function hasJoin($class) {
		
	}
	
	public function getWheres() {
		return $this->_where;
	}
	
	public function getClass() {
		return $this->_from->getClass();
	}
	
	/**
	 * @return SimDAL_Query_Limit
	 */
	public function getLimit() {
		return $this->_limit;
	}
	
	public function getSets() {
		return $this->_sets;
	}
	
	public function getType() {
		return $this->_type;
	}
	
	public function count() {
		if (method_exists($this->_parent, 'count')) {
			return $this->_parent->count($this);
		}
		
		return false;
	}
	
	public function fetch($limit=null, $offset=null) {
		if (method_exists($this->_parent, 'fetch')) {
			return $this->_parent->fetch($this, $limit, $offset);
		}
		
		return false;
	}
	
	public function execute() {
		if (method_exists($this->_parent, 'execute')) {
			return $this->_parent->execute($this);
		}
		
		return false;
	}
	
	public function __toString() {
		$string = implode(',', $this->getColumns());
		$string .= $this->getFrom();
		//$string .= $this->_where->__toString();
		foreach ($this->_where as $where) {
			$string .= $where->__toString();
		}
		
		return $string;
	}
	
	public function getHash() {
		return md5($this->__toString());
	}
	
}