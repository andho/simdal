<?php

class SimDAL_Persistence_Query_Join {

	protected $_table;
	protected $_conditions;

	function __construct($table, $column) {
		$this->_table = $table;

		if (!$this->_conditions instanceof SimDAL_Persistence_Query_ConditionSet) {
			$this->_conditions = new SimDAL_Persistence_Query_ConditionSet();
		}

		if (is_array($column) && count($column) > 0) {
			foreach ($column as $key=>$value) {
				if (is_int($key)) {
					if (!is_array($value)) {
						$this->_conditions->addKeyValue($value);
					}
					else if (count($value) > 1) {
						$expr = new Db_Expr($value[0], $this->_table);
						$colval = $expr->__toString();

						$expr = new Db_Expr($value[1], $this->_table);
						$valval = $expr->__toString();
						if (isset($value[2])) $opval = $value[2];
						else $opval = '=';
						$this->_conditions->addKeyValue($colval, $valval, $opval);
					}
				}
				else {
					$expr = new Db_Expr($value, $this->_table);
					$this->_conditions->addKeyValue($key, $expr->__toString());
				}
			}
		} else {
			$this->_conditions->addKeyValue($column);
		}
	}

	public function __toString() {
		if ($this->_table instanceof Db_Table_Abstract ) $table = $this->_table->getTableName();
		else $table = $this->_table;
		$sql = "INNER JOIN `" . $table . "` ON ";
		$sql .= $this->_conditions->__toString();

		return $sql;
	}

}