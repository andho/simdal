<?php

class SimDAL_Query_Set {

	protected $_query;
	protected $_column;
	protected $_value;
	
	public function __construct(SimDAL_Query $query, SimDAL_Mapper_Column $column, $value) {
		$this->_query = $query;
		$this->_column = $column;
		$this->_value = $value;
	}
	
}