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

class SimDAL_Mapper_Association {
	
	protected $_entity;
	protected $_type;
	protected $_class;
	protected $_foreignKey;
	protected $_parentKey;
	protected $_method;
	protected $_parentMethod;
	protected $_isParentAssociation;
	protected $_dependentMethod;
	
	public function __construct(SimDAL_Mapper_Entity $entity, $data) {
		$this->_entity = $entity;
		$this->_type = $data[0];
		$this->_class = $data[1];
		$this->_foreignKey = $data[2]['fk'];
		$this->_parentKey = isset($data[2]['key']) ? $data[2]['key'] : 'delayed';
		$this->_method = isset($data[2]['method']) ? $data[2]['method'] : $this->_getDefaultMethod();
		$this->_parentMethod = isset($data[2]['parentMethod']) ? $data[2]['parentMethod'] : null;
		$this->_dependentMethod = isset($data[2]['dependentMethod']) ? $data[2]['dependentMethod'] : null;
		$this->_isParentAssociation = $this->_determineIfParentAssociation($data);
	}
	
	public function getIdentifier() {
		return $this->getMethod();
	}
	
	public function getMethod() {
		return ucfirst($this->_method);
	}
	
	public function getDependentMethod() {
		if (is_null($this->_dependentMethod)) {
			return null;
		}
		
		return ucfirst($this->_dependentMethod);
	}
	
	public function getProperty() {
		$property = $this->getMethod();
		$property = strtolower(substr($property, 0, 1)) . substr($property, 1);
		
		return $property;
	}
	
	public function getParentMethod() {
		if (is_null($this->_parentMethod)) {
			return null;
		}
		
		return ucfirst($this->_parentMethod);
	}
	
	public function getType() {
		return $this->_type;
	}
	
	public function getForeignKey() {
		return $this->_foreignKey;
	}
	
	public function getParentKey() {
		if ($this->_parentKey == 'delayed') {
			$association_entity = $this->getAssociationEntity ();
			$this->_parentKey = $association_entity->getPrimaryKey ();
		}
		
		return $this->_parentKey;
	}
	
	public function getClass() {
		return $this->_class;
	}
	
	public function getAssociationEntity() {
		$entity = $this->getMapping ()->getMapper ()->getMappingForEntityClass ( $this->getClass () );
		
		return $entity;
	}
	
	public function isParent() {
		return $this->_isParentAssociation;
	}
	
	public function isDependent() {
		return !$this->_isParentAssociation;
	}
	
	/**
	 * @return SimDAL_Mapper_Association
	 */
	public function getMatchingAssociationFromAssociationClass() {
		$foreignKey = $this->getForeignKey();
		$parentKey = $this->getParentKey();
		
		$othersidemapping = $this->getMapping()->getMapper()->getMappingForEntityClass($this->getClass());
		$method = $this->getDependentMethod();
		/* @var $otherside_association SimDAL_Mapper_Association */
		foreach ($othersidemapping->getAssociations() as $otherside_association) {
			if ( (is_null($method) && $otherside_association->getClass() == $this->getMapping()->getClass()) || $otherside_association->getMethod() == $method ) {
				if ( ($this->getType() == 'one-to-many' && $otherside_association->getType() == 'many-to-one') ||
					($this->getType() == 'many-to-one' && $otherside_association->getType() == 'one-to-many') ) {
					$otherside_foreignKey = $otherside_association->getForeignKey();
					if ($foreignKey == $otherside_foreignKey && $parentKey == $otherside_association->getParentKey()) {
						return $otherside_association;
					}
				} else if ($this->getType() == 'one-to-one' && $otherside_association->getType() == 'one-to-one') {
					$otherside_foreignKey = $otherside_association->getForeignKey();
					if ($foreignKey == $otherside_foreignKey && $parentKey == $otherside_association->getParentKey()) {
						return $otherside_association;
					}
				}
			}
		}
	}
	
	/**
	 * @return SimDAL_Mapper_Entity
	 */
	public function getMapping() {
		return $this->_entity;
	}
	
	protected function _getDefaultMethod() {
		$method = $this->_class;
		
		if ($this->getType() == 'one-to-many') {
			$method = $method . 's';
		}
		
		return $method;
	}
	
	protected function _determineIfParentAssociation($data) {
		if ($data[0] === 'one-to-one') {
			if (isset($data[2]) && isset($data[2]['dependentMethod'])) {
				return true;
			}
		}
		
		if ($data[0] === 'one-to-many') {
			return true;
		}
		
		return false;
	}
	
	public function isOneToMany() {
		return $this->_type == 'one-to-many';
	}
	
	public function isManyToOne() {
		return $this->_type == 'many-to-many';
	}
	
	public function isOneToOne() {
		return $this->_type == 'one-to-one';
	}
	
}