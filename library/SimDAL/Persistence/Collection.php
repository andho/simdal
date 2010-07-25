<?php

class SimDAL_Persistence_Collection extends SimDAL_Collection implements SimDAL_Query_ParentInterface {
	
	protected $_session = null;
	protected $_association = null;
	protected $_populated = false;
	protected $_parent = null;
	
	public function rewind() {
		$this->_loadAll();
		parent::rewind();
	}
	
	public function count() {
		$this->_loadAll();
		
		return parent::count();
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
		 * @todo find a way to load data when needed
		 */
		if ($load) {
			$this->_loadAll();
		} 
		
		$class = $this->_getAssociation()->getClass();
		if (!$entity instanceof $class) {
			throw new Exception('Object of invalid class has been passed');
		}
		$primaryKey = $this->_getSession()->getMapper()->getMappingForEntityClass($class)->getPrimaryKey();
		if (!$this->_getSession()->isLoaded($class, $entity->$primaryKey) && !$this->_getSession()->isAdded($entity)) {
			$this->_getSession()->addEntity($entity);
		}
		$this[$entity->$primaryKey] = $entity;
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
	public function whereColumn(string $column) {
		$query = $this->_getQuery();
		
		return $query->whereColumn($column);
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
			$this->_query = new SimDAL_Query($this);
			$this->_query->from($this->_getSession()->getMapper()->getMappingForEntityClass($this->_getAssociation()->getClass()));
			$parentKey = $this->_getAssociation()->getParentKey();
			$this->_query->whereColumn($this->_getAssociation()->getForeignKey())->equals($this->_getParent()->$parentKey);
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
		$this->_query = null;
		return $this->_getSession()->fetch($query, $limit, $offset);
	}
	
	public function execute(SimDAL_Query $query) {
		throw new Exception("Invalid usage of execute");
	}
	
}