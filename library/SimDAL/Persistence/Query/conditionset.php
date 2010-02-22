<?php

class SimDAL_Persistence_Query_ConditionSet {

	protected $_conditions = array();
	protected $_logical = 'AND';

	public function addKeyValue($column, $value = null, $operator = '=', $logical = 'AND') {
		if (!($column instanceof SimDAL_Persistence_Query_Condition OR $column instanceof SimDAL_Persistence_Query_ConditionSet )) {
			$condition = new SimDAL_Persistence_Query_Condition($column, $value, $operator, $logical);
		} else {
			$condition = $column;
		}

		if ($condition instanceof SimDAL_Persistence_Query_Condition ) {
			$this->_logical = $condition->getLogicalOperator();
		}

		$this->_conditions[] =& $condition;

		return $condition;
	}

	public function isAny() {
		if ( is_array($this->_conditions) && count($this->_conditions) > 0) {
			return true;
		}

		return false;
	}

	public function where ($column, $value = null, $valuealt = '=', $logical = 'AND') {

		$this->addKeyValue($column, $value, $valuealt, $logical);

		return $this;
	}

	public function getLogicalOperator() {
		return $this->_logical;
	}

	public function __toString() {
		$sql = '';
		if (is_array($this->_conditions) && count($this->_conditions) > 0) {
			foreach ($this->_conditions as $condition) {
				if ($condition instanceof SimDAL_Persistence_Query_Condition ) {
					$sql .= $condition->__toString();
					$trim = $condition->getTrimValue();
				}
				else if ($condition instanceof SimDAL_Persistence_Query_ConditionSet ) {
					$sql .= '(' . $condition->__toString() . ') ' . $condition->getLogicalOperator() . ' ';
					$trim = strlen($this->_logical) + 2;
				}
			}
			$sql = substr($sql, 0, -($trim));
		}

		return $sql;
	}

}