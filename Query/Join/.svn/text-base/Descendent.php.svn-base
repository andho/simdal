<?php

class SimDAL_Query_Join_Descendent {
	
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
		$this->_columns = $columns;
		if (!is_null($type)) {
		$this->_type = $type;
		} else {
			$this->_type = 'inner';
		}
        
		$this->_setupWheres();
	}
	
	public function getJoinType() {
		switch ($this->_type) {
			case 'inner':
				return 'INNER JOIN';
			default:
				return 'INNER';
		}
	}
	
	public function hasAliases() {
	    return $this->_descendant->hasAliases();
	}
	
	public function getColumns() {
	    return $this->_columns;
	}
	
	public function getTableColumns() {
	    return $this->_descendant->getColumns();
	}
	
	public function getWheres() {
		return $this->_wheres;
	}
	
	public function getTable() {
	  return $this->_descendant->getTable();
	}
	
	protected function _setupWheres() {
	  $this->_wheres[] = new SimDAL_Query_Where_JoinDescendant($this->_descendant->getEntity(), $this->_descendant);
	}
	
}