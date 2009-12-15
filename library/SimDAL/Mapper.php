<?php

class SimDAL_Mapper {
	
	protected $map = array();
	
	public function getTable($class) {
		if (!array_key_exists($class, $this->map)) {
			return false;
		}
		
		return $this->map[$class]['table'];
	}
	
	public function getColumn($class, $key) {
		return $this->map[$class]['columns'][$key];
	}
	
	public function getColumnData($class) {
		return $this->map[$class]['columns'];
	}
	
	public function getPrimaryKey($class) {
		foreach ($this->map[$class]['columns'] as $property=>$column) {
			if ($column[2]['pk'] === true) {
				return $property;
			}
		}
	}
	
	public function getRelations($class) {
		return $this->map[$class]['associations'];
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

	public function getAll() {
		return $this->map;
	}
	
	public function getClassPriority() {
		$priority = array();
		
		foreach (array_keys($this->map) as $class) {
			$priority[0][$class] = $class;
			$priority2[$class] = 0;
		}
		
		$highest = 0;
		
		foreach ($this->map as $class=>$metadata) {
			foreach ($metadata['associations'] as $relations) { 
				switch ($relations[0]) {
					case 'many-to-one':
						$i = $priority2[$relations[1]];
						unset($priority[$priority2[$class]][$class]);
						$priority[++$i][$class] = $class;
						$priority2[$class] = $i;
						break;
				}
			}
		}
		
		$ordered = array();
		
		foreach ($priority as $level=>$classes) {
			foreach ($classes as $class) {
				$ordered[] = $class;
			}
		}
		
		return $ordered;
	}
	
	public function classExists($class) {
		if (is_object($class)) {
			$class = get_class($class);
		}
		
		if (!array_key_exists($class, $this->map)) {
			return false;
		}
		
		return true;
	}
	
}