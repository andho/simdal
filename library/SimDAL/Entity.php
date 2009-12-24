<?php

class SimDAL_Entity {
	
	static protected $_defaultMapper = null;
	static protected $_defaultAdapter = null;
	
	protected $_mapper = null;
	protected $_adapter = null;
	
	protected $_validators = array();
	protected $_errorMessages = array();

	static public function setDefaultAdapter(SimDAL_Persistence_AdapterAbstract $adapter) {
		self::$_defaultAdapter = $adapter;
	}
	
	static public function setDefaultMapper(SimDAL_Mapper $mapper) {
		self::$_defaultMapper = $mapper;
	}
	
	public function __call($method, $arguments) {
		$matches = array();
		if (!preg_match('/(get|set)(.*)/', $method, $matches)) {
			return false;
		}
		
		switch ($matches[1]) {
			case 'get':
				$relation = $this->getMapper()->getRelation($this, $matches[2]);
				if ($relation === false) {
					return false;
				}
				
				$fk = $relation[2]['fk'];
				$key = isset($relation[2]['key']) ? $relation[2]['key'] : 'id';
				
				if ($relation[0] == 'many-to-one') {
					$relation_pk = $this->getMapper()->getPrimaryKey($relation[1]);
					$property = preg_replace("/^(.)/", strtolower('$1'), $relation[1]);
					
					if (is_null($this->$property) && is_null($this->$fk)) {
						return null;
					}
					
					if (is_null($this->$property)) {
						if ($key == 'id') {
							$this->$property = $this->getAdapter()->findById($relation[1], $this->$fk);
							$this->$fk = $this->$property->$relation_pk;
						} else {
							$this->$property = $this->getAdapter()->findByColumn($relation[1], $this->$fk, $key);
							$this->$fk = $this->$property->$relation_pk;
						}
					}
				} else if ($relation[0] == 'one-to-many') {
					$property = preg_replace("/^(.)/", strtolower('$1'), $relation[1]);
					$property = isset($this->$property{s}) ? "{$property}s" : $property;
					
					if (!$this->$property->isPopulated()) {
						$this->$property = $this->getAdapter()->findByColumn($relation[1], $this->$key, $fk, null);
					}
				}
				
				return $this->$property;
				break;
			case 'set':
				$relation = $this->getMapper()->getRelation($this, $matches[2]);
				if ($relation === false) {
					return false;
				}
				
				$fk = $relation[2]['fk'];
				$key = isset($relation[2]['key']) ? $relation[2]['key'] : 'id';
				
				if ($relation[0] == 'many-to-one') {
					$property = preg_replace("/^(.)/", strtolower('$1'), $relation[1]);
					
					$this->$fk = $arguments[0]->$key;
					$this->$property = $arguments[0];
				}
				break;
			default:
				throw Exception("Undefined method: $method");
		}
	}
	
	public function __construct($adapter=null, $mapper=null) {
		if ($adapter instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = $adapter;
		} else if (self::$_defaultAdapter instanceof SimDAL_Persistence_AdapterAbstract) {
			$this->_adapter = self::$_defaultAdapter;
		} else {
			throw new Simdal_PersistenceAdapterIsNotSetException();
		}
		
		if ($mapper instanceof SimDAL_Mapper) {
			$this->_mapper = $mapper;
		} else if (self::$_defaultMapper instanceof SimDAL_Mapper) {
			$this->_mapper = self::$_defaultMapper;
		} else {
			throw new SimDAL_MapperIsNotSetException();
		}
		
		$this->_createCollections();
		$this->_init();
	}
	
	private function _createCollections() {
		foreach ($this->getMapper()->getRelations($this) as $relation) {
			$property = preg_replace("/^(.)/", strtolower('$1'), $relation[1]);
			if (!property_exists($this, $property)) {
				throw new Exception("Property is not defined in the object yet a relationship exists");
			}
			
			if ($relation[0] == 'one-to-many') {
				$this->$property = new SimDAL_Collection();
			}
		}
	}
	
	/**
	 * returns the Mapper
	 *
	 * @return SimDAL_Mapper
	 */
	private function getMapper() {
		return $this->_mapper;
	}
	
	/**
	 * returns the Adapter
	 *
	 * @return SimDAL_Persistence_AdapterAbstract
	 */
	private function getAdapter() {
		return $this->_adapter;
	}

	protected function _validateValidators() {
		$valid = true;
		
		foreach ($this->_validators as $property=>$validators) {
			if (!property_exists($this, $property)) {
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
					$this->_errorMessages[$property] = $validator->getMessages();
					$valid = false;
					return false;
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
				if (in_array($validator, $this->_validators[$property])) {
					continue;
				}
				$this->_validators[$property][] = $validator;
			}
		}
	}
	
	protected function _error($msg, $key=null) {
		if (!is_null($key)) {
			$this->_errorMessages[$key] = $msg;
		} else {
			$this->_errorMessages[] = $msg;
		}
	}
	
	public function getErrorMessages() {
		return $this->_errorMessages();
	}
	
	public function getErrorMessage($key) {
		if (!array_key_exists($key, $this->_errorMessages)) {
			return false;
		}
		return $this->_errorMessages[$key];
	}
	
	public function isError() {
		return count($this->_errorMessages) > 0;
	}
	
	public function isValid() {
		$valid = true;
		
		$valid = $this->_validateValidators();
		
		return $valid;
	}
	
}