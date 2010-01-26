<?php

class SimDAL_Entity extends SimDAL_ErrorTriggerer {
	
	static protected $_defaultMapper = null;
	static protected $_defaultAdapter = null;
	
	protected $_mapper = null;
	protected $_adapter = null;
	
	protected $_messages = array();
	protected $_validators = array();

	static public function setDefaultAdapter(SimDAL_Persistence_AdapterAbstract $adapter) {
		self::$_defaultAdapter = $adapter;
	}
	
	static public function setDefaultMapper(SimDAL_Mapper $mapper) {
		self::$_defaultMapper = $mapper;
	}
	
	public function __call($method, $arguments) {
		$matches = array();
		if (!preg_match('/(get|set)(.*)/', $method, $matches)) {
			return;
		}
		
		switch ($matches[1]) {
			case 'get':
				$relation = $this->getMapper()->getRelation($this, $matches[2]);
				if ($relation === false) {
					return;
				}
				
				$fk = $relation[2]['fk'];
				$key = isset($relation[2]['key']) ? $relation[2]['key'] : 'id';
				
				if ($relation[0] == 'many-to-one') {
					$relation_key = $this->getMapper()->getPrimaryKey($relation[1]);
					$property = strtolower( substr($relation[1],0,1) ) . substr($relation[1],1);
					
					if (is_null($this->$property) && is_null($this->$fk)) {
						return null;
					}
					
					if (is_null($this->$property)) {
						if ($key == 'id') {
							$this->$property = $this->getAdapter()->findById($relation[1], $this->$fk);
							$this->$fk = $this->$property->$key;
						} else {
							$this->$property = $this->getAdapter()->findByColumn($relation[1], $this->$fk, $key);
							$this->$fk = $this->$property->$key;
						}
					}
				} else if ($relation[0] == 'one-to-many') {
					$property = strtolower( substr($relation[1],0,1) ) . substr($relation[1],1);
					$property = isset($this->$property{s}) ? "{$property}s" : $property;
					
					if (!$this->$property->isPopulated()) {
						$this->$property = $this->getAdapter()->findByColumn($relation[1], $this->$key, $fk, null);
					}
				} else if ($relation[0] == 'one-to-one') {
					$relation_key = $this->getMapper()->getPrimaryKey($relation[1]);
					$property = $relation[2]['method'];
					$property = strtolower( substr($property,0,1) ) . substr($property,1);
					
					$setter = 'set'.$relation[2]['parentMethod'];
					
					if (is_null($this->$property) && is_null($this->$fk)) {
						return null;
					}
					
					if (is_null($this->$property)) {
						if ($key == 'id') {
							$this->$property = $this->getAdapter()->findById($relation[1], $this->$fk);
							$this->$property->$setter($this);
						} else {
							$this->$property = $this->getAdapter()->findByColumn($relation[1], $this->$fk, $key);
							$this->$fk = $this->$property->$relation_key;
							$this->$property->$setter($this);
						}
					}
				}
				
				return $this->$property;
				break;
			case 'set':
				$relation = $this->getMapper()->getRelation($this, $matches[2]);
				if ($relation === false) {
					return;
				}
				
				$fk = $relation[2]['fk'];
				$key = isset($relation[2]['key']) ? $relation[2]['key'] : 'id';
				
				if ($relation[0] == 'many-to-one') {
					$property = strtolower( substr($relation[1],0,1) ) . substr($relation[1],1);
					
					$this->$fk = $arguments[0]->$key;
					$this->$property = $arguments[0];
				} else if ($relation[0] == 'one-to-one') {
					if ($relation[2]['method'] == $matches[2]) {
						$property = $relation[2]['method'];
						$property = strtolower( substr($property,0,1) ) . substr($property,1);
						
						$this->$fk = $arguments[0]->$key;
						$this->$property = $arguments[0];
						
						if (isset($relation[2]['parentMethod'])) {
							$parentSetter = 'set'.$relation[2]['parentMethod'];
							$this->$property->$parentSetter($this);
						}
					} else {
						$property = $relation[2]['parentMethod'];
						$property = strtolower( substr($property,0,1) ) . substr($property,1);
						
						$this->$fk = $arguments[0]->$key;
						$this->$property = $arguments[0];
						
						if (isset($relation[2]['method'])) {
						}
					}
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
		$relations = $this->getMapper()->getRelations($this);
		if (count($relations) > 0) {
			foreach ($this->getMapper()->getRelations($this) as $relation) {
				if ($relation[0] == 'one-to-many') {
					$property = strtolower( substr($relation[1],0,1) ) . substr($relation[1],1) . 's';
					if (!property_exists($this, $property)) {
						throw new Exception("Property '$property' is not defined in the object of type '".get_class($this)."' yet a relationship exists");
					}
					$this->$property = new SimDAL_Collection();
				}
			}
		}
	}
	
	/**
	 * returns the Mapper
	 *
	 * @return SimDAL_Mapper
	 */
	protected function getMapper() {
		return $this->_mapper;
	}
	
	/**
	 * returns the Adapter
	 *
	 * @return SimDAL_Persistence_AdapterAbstract
	 */
	protected function getAdapter() {
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
					$this->_errorMessages[$property] = array_shift($validator->getMessages());
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
	
	public function isValid() {
		if (!$this->_validateValidators()) {
			return false;
		}
		
		return true;
	}
	
	public function setData($data) {
		if (!is_array($data) && !is_object($data)) {
			return false;
		}
		
		$class = $this->getMapper()->getClassFromEntity($this);
		$pk = $this->getMapper()->getPrimaryKey($class);
		$pkcolumn = $this->getMapper()->getColumn($class, $pk);
		
		foreach ($data as $key=>$value) {
			if (!property_exists($this, $key)) {
				continue;
			}
			if ($key == $pk && $pkcolumn[2]['autoIncrement'] == true) {
				continue;
			}
			$this->$key = $value;
		}
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