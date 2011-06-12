<?php

class SimDAL_Query_TransformAdapter_MySqlStatement extends SimDAL_Query_TransformAdapter_MySql {
	
	private $_bind_params;
	
	public function queryToString(SimDAL_Query $query) {
		$sql = parent::queryToString($query);
		
		$stmnt = $this->_getAdapter()->execute($sql, $this->_bind_params);
		
		return $stmnt;
	}
	
	public function processWhereRawValue($value) {
		$this->_bind_params[] = $value;
		
		return "?";
	}
	
	public function processWhereArray(array $values) {
		$string = '(';
		
		foreach ($values as $value) {
			$string .= '?,';
			$this->_bind_params[] = $value;
		}
		
		$string = substr($string, 0, -1) . ')';
		
		return $string;
	}
	
}