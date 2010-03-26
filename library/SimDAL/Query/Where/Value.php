<?php

class SimDAL_Query_Where_Column {
	
	protected $_table;
	protected $_column;
	
	public function __construct($table, $column) {
		$this->_table = $table;
		$this->_column = $column;
	}
	
	public function getTable() {
		return $this->_table;
	}
	
	public function getColumn() {
		return $this->_column;
	}
	
}