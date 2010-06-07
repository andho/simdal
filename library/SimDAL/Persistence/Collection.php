<?php

class SimDAL_Persistence_Collection extends SimDAL_Collection implements SimDAL_Query_ParentInterface {
	
	protected $_session = null;
	protected $_association = null;
	protected $_populated = false;
	
	public function rewind() {
		$this->_loadAll();
		parent::rewind();
	}
	
	/**
	 * 
	 * @var SimDAL_Query
	 */
	protected $_query = null;
	
	public function __construct(SimDAL_Session $session, SimDAL_Mapper_Association $association) {
		$this->_session = $session;
		$this->_association = $association;
	}
	
	public function add($entity) {
		$class = $this->_getAssociation()->getClass();
		if (!$entity instanceof $class) {
			throw new Exception('Object of invalid class has been passed');
		}
		$this->_getSession()->addEntity($entity);
		$this[] = $entity;
	}
	
	public function delete($entity) {
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
	}
	
	protected function _loadAll() {
		if (!$this->_isPopulated()) {
			$query = $this->_getQuery();
			$collection = $this->_getSession()->fetch($query);
			parent::__construct($collection);
			$this->_populated = true;
			$this->_query = null;
		}
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
	
	/**
	 * @return SimDAL_Query
	 */
	protected function _getQuery() {
		if (is_null($this->_query)) {
			$this->_query = new SimDAL_Query($this);
			$this->_query->from($this->_getSession()->getMapper()->getMappingForEntityClass($this->_getAssociation()->getClass()));
		}
		
		return $this->_query;
	}
	
	protected function _isPopulated() {
		return $this->_populated;
	}
	
	public function fetch(SimDAL_Query $query, $limit, $offset) {
		$this->_query = null;
		return $this->_getSession()->fetch($query, $limit, $offset);
	}
	
	public function execute(SimDAL_Query $query) {
		throw new Exception("Invalid usage of execute");
	}
	
}