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
	
	public function getPrimaryKey($class) {
		foreach ($this->map[$class]['columns'] as $property=>$column) {
			if ($column[2]['pk'] === true) {
				return $column[0];
			}
		}
	}
	
	public function getManyToOneRelations($class) {
		$associations = array();
		foreach ($this->map[$class]['associations'] as $association) {
			if ($association[0] == 'many-to-one') {
				$associations[] = $association;
			}
		}
		
		return $associations;
	}

}