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
	
	public function hasRelation($class, $relation) {
		if (!$this->classExists($class)) {
			return false;
		}
		
		foreach ($this->getRelations($class) as $relation) {
			if ($relation[1] == $class) {
				return true;
			}
		}
		
		return false;
	}
	
	public function getRelations($class) {
		if (!$this->classExists($class)) {
			return false;
		}
		
		return $this->map[$class]['associations'];
	}
	
	public function getRelation($class, $relation) {
		if (!$this->classExists($class)) {
			return false;
		}
		
		foreach ($this->map[$class]['associations'] as $relation) {
			if ($relation[1] == $class) {
				return $relation;
			}
		}
		
		return false;
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
	
	public function classExists(&$class) {
		if (is_object($class)) {
			$class = $this->getClassFromEntity($class);
		}
		
		if (!array_key_exists($class, $this->map)) {
			return false;
		}
		
		return true;
	}
	
	public function getClassFromEntity($entity) {
		if (!is_object($entity)) {
			throw new Exception("Invalid argument passed. Object is required");
		}
		
		$class = get_parent_class($entity);
		if (!$class) {
			$class = get_class($entity);
		}
		
		return $class;
	}
	
}