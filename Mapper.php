<?php
/**
 * SimDAL - Simple Domain Abstraction Library.
 * This library will help you to separate your domain logic from
 * your persistence logic and makes the persistence of your domain
 * objects transparent.
 * 
 * Copyright (C) 2011  Andho
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class SimDAL_Mapper {
	
	const COMPARE_GREATER = 0;
	const COMPARE_LESS = 1;
	
	protected $map = array();
	protected $_mappings = array();
	
	protected $_priority = array();
	protected $_priority2 = array();
	
	public function __construct() {
		SimDAL_Autoload::registerMapper($this);
	}
	
	public function addMappingForEntityClass($class, $mapping) {
		$this->map[$class] = $mapping;
		
		return $this->getMappingForEntityClass($class);
	}
	
	public function getClasses() {
		return array_keys($this->map);
	}
	
	public function getTable($class) {
		if (!is_array($this->map) || (!is_numeric($class) && !is_string($class)) || !array_key_exists($class, $this->map)) {
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
		return $this->getMappingForEntityClass($class)->getPrimaryKey();
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
	
	public function getClassPriority($classes=null) {
		$priority = array();
		
		if (is_null($classes)) {
			$classes = array_keys($this->map);
		}
		
		foreach ($classes as $class) {
			$priority[0][$class] = $class;
			$priority2[$class] = 0;
		}
		
		$highest = 0;
		
		foreach ($this->map as $class=>$metadata) {
			if (!in_array($class, $classes)) {
				continue;
			}
			if (isset($metadata['associations'])) {
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
	
	public function hasDescendants($class) {
		if (!isset($this->_map[$class])) {
			return false;
		}
		if (!isset($this->map[$class]['descendants'])) {
			return false;
		}
		if (!is_array($this->map[$class]['descendants'])) {
			return false;
		}
		if (count($this->map[$class]['descendants']) == 0) {
			return false;
		}
		
		return true;
	}
	
	public function getDescendants($class) {
		if (!$this->hasDescendants($class)) {
			return false;
		}
		
		return $this->map[$class]['descendants'];
	}
	
	public function getDescendantTypeField($class) {
		if (!$this->hasDescendants($class)) {
			return false;
		}
		
		return $this->map[$class]['descendantTypeField'];
	}
	
	public function getDescendantClassPrefix($class) {
		if (!$this->hasDescendants($class)) {
			return false;
		}
		
		$prefix = $this->map[$class]['descendantClassNamePrefix'];
		
		if ($prefix === true) {
			$prefix = "{$class}_";
		} else if (!empty($prefix)) {
			$prefix = $this->map[$class]['descendantClassPrefix'];
		}
		
		return $prefix;
	}
	
	public function getDescendantColumn($class, $descendantClass, $key) {
		if (!$this->hasDescendants($class)) {
			return false;
		}
		
		return $this->map[$class]['descendants'][$descendantClass]['columns'][$key];
	}
	
	public function getDescendantColumnData($class, $descendantClass) {
		if (!$this->hasDescendants($class)) {
			return false;
		}
		
		return $this->map[$class]['descendants'][$descendantClass]['columns'];
	}
	
	/**
	 * 
	 * @return SimDAL_Mapper_Entity
	 */
	public function getMappingForEntityClass($class) {
		if (!class_exists($class, true)) {
			throw new Exception('\'' . $class . '\' is not a valid Class');
		}
		$class = $this->getDomainEntityNameFromClass($class);
		if (!is_array($this->map) || !array_key_exists($class, $this->map)) {
			throw new Exception("The entity class given is not valid");
		}
		if (!isset($this->_mappings[$class]) || !$this->_mappings[$class] instanceof SimDAL_Mapper_Entity) {
			$this->_mappings[$class] = new SimDAL_Mapper_Entity($class, $this->map[$class], $this);
		}
		
	    return $this->_mappings[$class];
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
		
		return $this->getDomainEntityNameFromClass($class);
	}
	
	public function getDomainEntityNameFromClass($class) {
		if (!is_string($class) && !is_numeric($class)) {
			return false;
		}
		$previous_class = $class;
		while (!array_key_exists($class, $this->map) && $class !== false) {
			$class = get_parent_class($class);
		}
		
		return $class;
	}
	
	public function getDescendentEntityClass($entity, $domain_entity_name) {
		$class = get_class($entity);
		$mapping = $this->getMappingForEntityClass($domain_entity_name);
		/* @var $descendent SimDAL_Mapper_Descendent */
		foreach ($mapping->getDescendents() as $descendent) {
			if ($descendent->getFullClassName() == $class) {
				return $descendent->getFullClassName();
			}
		}
		
		return $class;
	}
	
	public function getTypeMorphClass($class, $row) {
		$class = $this->getDomainEntityNameFromClass($class);
		
		if (!isset($this->map[$class]['type_binding'])) {
			return $class;
		}
		
		$column = $this->map[$class]['type_binding']['column'];
		$column = $this->map[$class]['columns'][$column][0];
		$types = $this->map[$class]['type_binding']['types'];
		if (array_key_exists($row[$column], $types)) {
			$class = $types[$row[$column]];
		}
		
		return $class;
	}
	
	public function getDescendantClass($class, $row) {
		$class = $this->getDomainEntityNameFromClass($class);
		
		if (!isset($this->map[$class]['descendants']) || !isset($this->map[$class]['descendantTypeField']) || !isset($this->map[$class]['descendantClassNamePrefix'])) {
			return $class;
		}
		
		$type_field = $this->map[$class]['descendantTypeField'];
		$prefix = isset($this->map[$class]['descendantClassNamePrefix']) ? $this->map[$class]['descendantClassNamePrefix'] : false; 
		foreach ($this->map[$class]['descendants'] as $descendantType=>$descendant) {
			if ($prefix === true) {
				$prefix = $class . '_';
			} else if ($prefix === false) {
				$pregix = '';
			}
			if (strtolower($prefix.$row[$type_field]) == strtolower($descendantType)) {
				return $descendantType;
			}
		}
		
		return $class;
	}
	
	public function getRelationMethod($relation) {
		if (isset($relation[2]['method'])) {
			return ucfirst($relation[2]['method']);
		}
		$method = $relation[1];
		if ($relation[0] == 'one-to-many') {
			$method .= 's';
		}
		
		return $method;
	}
	
}