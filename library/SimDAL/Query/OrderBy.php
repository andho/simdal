<?php

class SimDAL_Query_OrderBy {
	
	protected $column;
	
	public function __construct($column) {
		$this->column = $column;
	}
	
	public function getValue() {
		return $this->column;
	}
	
}