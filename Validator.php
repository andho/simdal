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

class SimDAL_Validator extends SimDAL_ErrorTriggerer {
	
	protected $_messages = array();
	protected $_validators = array();
	protected $_validationGroups = array();
	
	public function __construct() {
		if (method_exists($this, '_init')) {
			$this->_init();
		}
	}
	
	protected function _validateValidators($group=null) {
		$valid = true;
		
		if (!is_null($group) && is_string($group)) {
			if (!array_key_exists($group, $this->_validationGroups)) {
				throw new Exception("Validation group '$group' does not exist");
			}
			$group = $this->_validationGroups[$group];
		}
		
		foreach ($this->_validators as $property=>$validators) {
			if (!property_exists($this, $property)) {
				continue;
			}
			if (is_array($group) && !in_array($property, $group)) {
				continue;
			}
			foreach ($validators as $validator) {
				if (is_string($validator)) {
					$validator = new $validator();
				}
				if (!$validator instanceof Zend_Validate_Interface && (!method_exists($validator, 'isValid') || !method_exists($validator, 'getMessages'))) {
					continue;
				}
				
				if (!$validator->isValid($this->$property)) {
					$this->_errorMessages[$property] = array_shift($validator->getMessages());
					$valid = false;
					break;
				}
			}
		}
		
		return $valid;
	}
	
	protected function _setValidators(array $validators, $overwrite=false) {
		if ($overwrite) {
			$this->_validators = $validators;
		}
		
		foreach ($validators as $property=>$validators2) {
			foreach ($validators2 as $validator) {
				if (!array_key_exists($property, $this->_validators)) {
					$this->_validators[$property] = array();
				}
				if (in_array($validator, $this->_validators[$property])) {
					continue;
				}
				$this->_validators[$property][] = $validator;
			}
		}
	}
	
	protected function _setValidationGroups(array $groups) {
		$this->_validationGroups = $groups;
	}
	
	public function isValid() {
		if (!$this->_validateValidators()) {
			return false;
		}
		
		return true;
	}
	
	protected function _message($msg, $key=null) {
		if (!is_null($key)) {
			$this->_messages[$key] = $msg;
		} else {
			$this->_messages[] = $msg;
		}
	}
	
	public function getMessages() {
		return $this->_messages;
	}
	
	public function hasMessages() {
		return count($this->_messages) > 0;
	}
	
}