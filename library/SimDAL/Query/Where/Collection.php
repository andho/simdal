<?php

class SimDAL_Query_Where_Collection implements SimDAL_Query_Where_Interface {
	
	public function getLeftValue() {
		return null;
	}
	
	public function getRightValue() {
		return null;
	}
	
	public function getOperator() {
		return null;
	}
	
	public function __toString() {
		$string = '';
		foreach ($this->_wheres as $where) {
			$string .= $where->__toString();
		}
		
		return $string;
	}
	
}