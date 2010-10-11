<?php

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