<?php

class SimDAL_Persistence_Query_Condition {
	
	protected $_column = null;
	protected $_value = null;
	protected $_operator = '=';
	protected $_logical = 'AND';
	protected $_trim;
	
	public function __construct($column, $value = null, $operator = '=', $logical = 'AND') {
		if (preg_match('/(.+?)(IN|BETWEEN|>|<|<=|>=|=)(.+)/', $column, $matches)) {
			$this->_column = "`".$matches[1]."`";
			$this->_operator = $matches[2];
			$this->_value = $matches[3];
			$this->_logical = $logical;

			$this->_trim = strlen($logical) + 2;

			return true;
		}

		$this->_column = $column;
		$this->_value = $value;
		
		if ($value === 'NULL') {
			if ($operator == '=')
				$this->_operator = 'IS';
			if ($operator == '!=')
				$this->_operator = 'IS NOT';
		}
		else {
			$this->_operator = $operator;
		}
		
		$this->_logical = $logical;
		
		$this->_trim = strlen($logical) + 2;
	}
	
	public function getTrimValue() {
		return $this->_trim;
	}
	
	public function getLogicalOperator() {
		return $this->_logical;
	}
	
	public function __toString() {
		return $this->_column . ' ' . $this->_operator . ' ' . $this->_value . ' ' . $this->_logical . ' ';
	}
	
}