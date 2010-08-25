<?php

class SimDAL_Query_OrderBy {
	
	protected $column;
	protected $query;
	protected $type;
	
	public function __construct(SimDAL_Mapper_Column $column, SimDAL_Query $query) {
		$this->column = $column;
		$this->query = $query;
	}
	
	public function getValue() {
		return $this->column;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function descending() {
		$this->type = 'descending';
		
		return $this->query;
	}
	
	public function ascending() {
		$this->type = 'ascending';
		
		return $this->query;
	}
	
	public function fetch($limit=null, $offset=null) {
		return $this->query->fetch($limit, $offset);
	}
	
}