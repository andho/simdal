<?php

class SimDAL_Mapper {
	
	protected $map = array();
	
	public function getTable($class) {
		if (!array_key_exists($class, $this->map)) {
			return false;
		}
		
		return $this->map[$class]['table'];
	}
	
	public function getColumnData($class) {
		return $this->map[$class]['columns'];
	}
}