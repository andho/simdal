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

class SimDAL_Mapper_Descendent extends SimDAL_Mapper_Entity {
	
	const TYPE_NORMAL = 'normal';
	
    protected $_entity;
	protected $_parentKey;
	protected $_foreignKey;
	protected $_type;
	
	public function __construct(SimDAL_Mapper_Entity $entity, $class, $data) {
	  $this->_entity = $entity;
	  $this->_parentKey = isset($data['parentKey'])?$data['parentKey']:'';
	  $this->_foreignKey = isset($data['foreignKey'])?$data['foreignKey']:'';
	  if (!isset($data['type'])) {
	  	throw new Exception("Descendent type not given");
	  }
	  $this->_type = $data['type'];
	  parent::__construct($class, $data, $entity->getMapper());
	}
	
	public function getParentKey() {
		return $this->_parentKey;
	}
	
	public function getForeignKey() {
		return $this->_foreignKey;
	}
	
	public function getIdentifier() {
	  return $this->_class;
	}
	
	/**
	 * @var SimDAL_Mapper_Entity
	 */
	public function getEntity() {
	  return $this->_entity;
	}
	
	public function getType() {
		return $this->_type;
	}
	
	public function getFullClassName() {
		return $this->getEntity()->getDescendentPrefix() . $this->getClass();
	}
	
}