<?php

class SimDAL_Query_Join_Descendant {
	
	protected $_descendant;
	protected $_type;
	protected $_wheres;
	
	public function __construct($descendant, $type=null) {
		$this->_descendant = $descendant;
		if (!is_null($type)) {
		$this->_type = $type;
		} else {
			$this->_type = 'inner';
		}
	}
	
	public function getJoinType() {
		switch ($this->_type) {
			case 'inner':
				return 'INNER JOIN';
			default:
				return 'INNER';
		}
	}
	
	public function getWheres() {
		
	}
	
}