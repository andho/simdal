<?php

class SimDAL_Mapper {
	
	const COMPARE_GREATER = 0;
	const COMPARE_LESS = 1;
	
	protected $map = array();
	
	protected $_priority = array();
	protected $_priority2 = array();
	
	public function getTable($class) {
		if (!array_key_exists($class, $this->map)) {
			return false;
		}
		
		$table = $this->map[$class]['table'];
		if (isset($this->map[$class]['schema'])) {
			$table = "{$this->map[$class]['schema']}.{$table}";
		}
		
		return $table;
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
		
		foreach ($this->getRelations($class) as $relation_) {
			if ($relation_[1] == $class) {
				return true;
			}
			$method = isset($relation_[2]['method']) ? $relation_[2]['method'] : null;
			if (!is_null($method) && $method == $relation) {
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
		
		foreach ($this->map[$class]['associations'] as $relation_) {
			if ($relation_[1] == $relation) {
				return $relation_;
			}
			$method = isset($relation_[2]['method']) ? $relation_[2]['method'] : null;
			if (!is_null($method) && $method == $relation) {
				return $relation_;
			}
			
			$method = isset($relation_[2]['parentMethod']) ? $relation_[2]['parentMethod'] : null;
			if (!is_null($method) && $method == $relation) {
				return $relation_;
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
		
		$this->_priority = $priority;
		$this->_priority2 = $priority2;
		
		return $ordered;
	}
	
	public function compare($class1, $class2) {
		if ($this->_priority2[$class1] > $this->_priority2[$class2]) {
			return SimDAL_Mapper::COMPARE_GREATER;
		} else {
			return SimDAL_Mapper::COMPARE_LESS;
		}
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
		
		$class = get_class($entity);
		
		while (!array_key_exists($class, $this->map) && $class !== false) {
			$class = get_parent_class($class);
		}
		
		return $class;
	}
	
}