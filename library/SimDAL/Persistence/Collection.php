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

class SimDAL_Persistence_Collection extends SimDAL_Collection implements SimDAL_Query_ParentInterface {
	
	protected $_session = null;
	protected $_association = null;
	protected $_populated = false;
	protected $_parent = null;
	
	public function rewind() {
		$this->_loadAll();
		parent::rewind();
	}
	
	public function count(SimDAL_Query $count = null) {
		if (is_null($count)) {
			$count = $this->_getQuery();
		}
		$this->_query = null;
		$count = $this->_getSession()->count($count);
		return $count['count'];
	}
	
	/**
	 * 
	 * @var SimDAL_Query
	 */
	protected $_query = null;
	
	public function __construct(SimDAL_ProxyInterface $parent, SimDAL_Session $session, SimDAL_Mapper_Association $association) {
		$this->_parent = $parent;
		$this->_session = $session;
		$this->_association = $association;
	}
	
	public function add(&$entity, $load=true) {
		/**
		 * load current values, otherwise if load is called after
		 * commiting this entity will be in the collection twice.
		 * @todo find a way to load data when needed and not when adding an Entity
		 */
		if ($load) {
			$this->_loadAll();
		} 
		
		$class = $this->_getAssociation()->getClass();
		if (!$entity instanceof $class) {
			throw new Exception('Object of invalid class has been passed');
		}
		
		$primaryKey = $this->_getSession()->getMapper()->getMappingForEntityClass($class)->getPrimaryKey();
		$primaryKey_getter = 'get' . $primaryKey;
		if (!$this->_getSession()->isLoaded($class, $entity->$primaryKey_getter()) && !$this->_getSession()->isAdded($entity)) {
			$this->_getSession()->addEntity($entity);
		}
		$this[$entity->$primaryKey_getter()] = $entity;
	}
	
	public function delete(&$entity) {
		$class = $this->_getAssociation()->getClass();
		if (!$entity instanceof $class) {
			throw new Exception('Object of invalid class has been passed');
		}
		for ($i=0; $i<count($this->_keymap); $i++) {
			if ($this->_data[$this->_keymap[$i]]->id == $entity->id) {
				unset($this->_data[$this->_keymap[$i]]);
				unset($this->_keymap[$i]);
				$foreignKey = $this->_getAssociation()->getForeignKey();
				$entity->$foreignKey = null;
			}
		}
		
		return null;
	}
	
	protected function _loadAll() {
		if (!$this->_isPopulated()) {
			$query = $this->_getQuery();
			$collection = $this->_getSession()->fetch($query, 0);
			parent::__construct($collection);
			$this->_populated = true;
			$this->_query = null;
		}
	}
	
	public function get($position) {
		$this->_loadAll();
		
		return parent::get($position);
	}
	
	public function search($property, $value) {
		$this->_loadAll();
		
		return parent::search($property, $value);
	}
	
	public function toArray($load=true) {
		if ($load) {
			$this->_loadAll();
		}
		
		return parent::toArray();
	}
	
	/**
	 * 
	 * @param string $column
	 */
	public function whereColumn($column) {
		$query = $this->_getQuery();
		
		return $query->whereColumn($column);
	}
	
	public function orderBy($column) {
		$query = $this->_getQuery();
		
		return $query->orderBy($column);
	}
	
	/**
	 *
	 * @return SimDAL_Session
	 */
	protected function _getSession() {
		return $this->_session;
	}
	
	/**
	 * @return SimDAL_Mapper_Association
	 */
	protected function _getAssociation() {
		return $this->_association;
	}
	
	protected function _getParent() {
		return $this->_parent;
	}
	
	/**
	 * @return SimDAL_Query
	 */
	protected function _getQuery() {
		if (is_null($this->_query)) {
			$this->_query = new SimDAL_Query($this, $this->_getSession()->getMapper());
			$this->_query->from($this->_getSession()->getMapper()->getMappingForEntityClass($this->_getAssociation()->getClass()));
			$parentKey = $this->_getAssociation()->getParentKey();
			$parentKey_getter = 'get' . $parentKey;
			$this->_query->whereColumn($this->_getAssociation()->getForeignKey())->equals($this->_getParent()->$parentKey_getter());
		}
		
		return $this->_query;
	}
	
	protected function _isPopulated() {
		return $this->_populated;
	}
	
	public function getFirst() {
		$query = $this->_getQuery();
		return $this->fetch($query);
	}
	
	public function fetch(SimDAL_Query $query, $limit=null, $offset=null) {
		if (is_null($query)) {
			$query = $this->_getQuery();
		}
		if (is_null($limit)) {
			$limit = 0;
		}
		$this->_query = null;
		return $this->_getSession()->fetch($query, $limit, $offset);
	}
	
	public function execute(SimDAL_Query $query) {
		throw new Exception("Invalid usage of execute");
	}
	
}