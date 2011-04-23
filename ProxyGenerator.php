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

class SimDAL_ProxyGenerator {
	
	static public function generateProxies(SimDAL_Mapper $mapper, $cachedir) {
		$cachedir = preg_replace('/[\/\\\\]$/', '', $cachedir);
		if (!is_dir($cachedir)) {
			throw new Exception('.simdal directory doesn\'t exist in your Domain');
		}
		if (!is_dir($cachedir . DIRECTORY_SEPARATOR . 'config')) {
			throw new Exception('.simdal/config directory doesn\'t exist in your Domain');
		}
		$proxy_dir = $cachedir . DIRECTORY_SEPARATOR . 'proxies';
		if (!is_dir($proxy_dir) && !mkdir($proxy_dir, 0775, true)) {
			throw new Exception('Could not create the proxy directory \'' . $proxy_dir . '\' for SimDAL');
		}
		
		$classes = $mapper->getClasses();
		foreach ($classes as $class) {
			self::generateProxy($mapper->getMappingForEntityClass($class));
		}
		//echo '<pre>' . $output . '</pre>';
		
		//include $cachefile;
	}
	
	static public function generateProxy(SimDAL_Mapper_Entity $mapping, $proxy_file) {
		$class = $mapping->getClass();
		
		if (!class_exists($class)) {
			throw new Exception("Class '{$class}' in mapper does not exist");
		}
		
		$proxy_class = $class . 'Proxy';
		$class_name = $class;
		
		$class = self::_generateProxyClass($mapping);
		$helper_properties = self::_generateHelperProperties($mapping);
		$helper_methods = self::_generateHelperMethods($mapping);
		$proxy_methods = self::_generateProxyMethods($mapping);
		
		$class .= $helper_properties;
		$class .= $helper_methods;
		$class .= $proxy_methods;
		$class .= '}' . PHP_EOL . PHP_EOL;
		
		$dirname = dirname($proxy_file);
		if (!is_dir($dirname)) {
			if (!mkdir($dirname, 0775, true)) {
				throw new Exception('Could not create directory for Proxy file');
			}
		}
		
		if (!is_file($proxy_file)) {
			touch($proxy_file);
		}
		$output = '<?php' . PHP_EOL . PHP_EOL;
		$output .= $class;
		file_put_contents($proxy_file, $output);
		
		$descendents = $mapping->getDescendents();
		$prefix = $mapping->getDescendentPrefix();
		/* @var $descendents SimDAL_Mapper_Descendent */
		foreach ($descendents as $descendent) {
			if ($descendent->getType() === SimDAL_Mapper_Descendent::TYPE_NORMAL) {
				$descendent_class = self::_generateProxyClass($mapping, $prefix . $descendent->getClass());
				$descendent_class .= $helper_properties;
				$descendent_class .= $helper_methods;
				$descendent_class .= $proxy_methods;
				$descendent_class .= '}' . PHP_EOL . PHP_EOL;
				
				$proxy_class_filename = str_replace('_', '/', $class_name);
				$descendent_filename = str_replace('_', '/', $prefix . $descendent->getClass());
				$descendent_proxy_file = preg_replace('/'.preg_quote($proxy_class_filename, '/').'/', $descendent_filename, $proxy_file);
				$dirname = dirname($descendent_proxy_file);
				
				if (!is_dir($dirname)) {
					mkdir($dirname, 0755, true);
				}
				
				if (!is_file($descendent_proxy_file)) {
					touch($descendent_proxy_file);
					$output = '<?php' . PHP_EOL . PHP_EOL;
					$output .= $descendent_class;
					file_put_contents($descendent_proxy_file, $output);
				}
			}
		}
		
		return $class;
	}
	
	static protected function _generateProxyClass(SimDAL_Mapper_Entity $mapping, $class=null) {
		if (is_null($class)) {
			$class = $mapping->getClass();
		}
		$proxy_class = $class . 'SimDALProxy';
		$class = 'class ' . $proxy_class . ' extends ' . $class . ' implements SimDAL_ProxyInterface {' . PHP_EOL . PHP_EOL;
		
		return $class;
	}
	
	static protected function _generateHelperProperties(SimDAL_Mapper_Entity $mapping) {
		$associations = $mapping->getAssociations();
		$output = '';
		
		$output .= '	private $_session;' . PHP_EOL;
		
		/* @var $association SimDAL_Mapper_Association */
		if (count($associations)) {
			$output .= '	private $_loadedSimDALEntities = array(' . PHP_EOL;
			foreach ($associations as $association) {
				$output .= '		\'' . $association->getMethod() . '\' => false,' . PHP_EOL;
			}
			$output = substr($output, 0, -2) . PHP_EOL;
			$output .= '	);' . PHP_EOL . PHP_EOL;
		}
		
		return $output;
	}
	
	static protected function _generateHelperMethods(SimDAL_Mapper_Entity $mapping) {
		$associations = $mapping->getAssociations();
		
		$output = '';
		$output .= '	public function __construct($data, SimDAL_Session $session, $id=null) {' . PHP_EOL;
		$output .= '		if (!is_null($id)) {' . PHP_EOL;
		$output .= '			$this->id = $id;' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		if (!is_array($data) && !is_object($data)) {' . PHP_EOL;
		$output .= '			return false;' . PHP_EOL;
		$output .= '		}' . PHP_EOL . PHP_EOL;
		$output .= '		$this->_session = $session;' . PHP_EOL;
		$output .= '		if (is_array($data)) {' . PHP_EOL;
		$output .= '			foreach ($data as $key=>$value) {' . PHP_EOL;
		$output .= '				if (property_exists($this, $key)) {' . PHP_EOL;
		$output .= '					$this->$key = $value;' . PHP_EOL;
		$output .= '				}' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
		$output .= '		} else if (is_object($data)) {' . PHP_EOL;
		
		foreach ($mapping->getColumns() as $columnName=>$column) {
			if ($column->isAutoIncrement()) {
				continue;
			}
			$output .= '			if (method_exists($data, \'get' . $columnName . '\')) {' . PHP_EOL;
			$output .= '				$this->' . $columnName . ' = $data->get' . $columnName . '();' . PHP_EOL;
			$output .= '			}' . PHP_EOL;
		}
		
		/* @var $association SimDAL_Mapper_Association */
		foreach ($associations as $association) {
			$method = ucfirst($association->getMethod());
			$property = $association->getProperty();
			$setter = 'set' . $method;
			$getter = 'get' . $method;
			$foreignKey = $association->getForeignKey();
			$parentKey = $association->getParentKey();
			$parentKeyGetter = 'get' . ucfirst($parentKey);
			$output .= '			if (method_exists($data, \'' . $getter . '\')) {' . PHP_EOL;
			$output .= '				$reference = $data->' . $getter . '();' . PHP_EOL;
			if ($association->getType() == 'many-to-one' || $association->getType() == 'one-to-one') {
				$output .= '				if (!is_null($reference) && !$this->_getSession()->isLoaded($reference) && !$this->_getSession()->isAdded($reference)) {' . PHP_EOL;
				$output .= '					$this->_getSession()->addEntity($reference);' . PHP_EOL;
				$output .= '				}' . PHP_EOL;
			}
			if ($association->getType() == 'many-to-one' || ($association->getType() == 'one-to-one' && $association->isDependent())) {
				$output .= '				$this->' . $property . ' = $reference;' . PHP_EOL;
				$output .= '				if (!is_null($reference)) {' . PHP_EOL;
				$output .= '					$this->' . $foreignKey . ' = !is_null($reference)?$reference->' . $parentKeyGetter . '():null;' . PHP_EOL;
				$output .= '				}' . PHP_EOL;
			} else if ($association->getType() == 'one-to-many') {
				$output .= '				$this->' . $property . ' = $reference;' . PHP_EOL;
			}
			$output .= '				$this->_SimDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
			$output .= '			}' . PHP_EOL;
		}
		
		$output .= '		}' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	private function _isSimDALAssociationLoaded($association_name) {' . PHP_EOL;
		$output .= '		if (!array_key_exists($association_name, $this->_loadedSimDALEntities)) {' . PHP_EOL;
		$output .= '			throw new Exception(__METHOD__ . \' called with invalid association name\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		return $this->_loadedSimDALEntities[$association_name];' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	private function _SimDALAssociationIsLoaded($association_name) {' . PHP_EOL;
		$output .= '		if (!array_key_exists($association_name, $this->_loadedSimDALEntities)) {' . PHP_EOL;
		$output .= '			throw new Exception(__METHOD__ . \' called with invalid association name\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		$this->_loadedSimDALEntities[$association_name] = true;' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	private function _getSession() {' . PHP_EOL;
		$output .= '		return $this->_session;' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	public function _SimDAL_setPrimaryKey($values, $session) {' . PHP_EOL;
		$primary_key = $mapping->getPrimaryKey();
		$output .= '		if ($session !== $this->_getSession()) {' . PHP_EOL;
		$output .= '			throw new Exception(__METHOD__ . \' called from outside of library scope\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		$this->' . $primary_key . ' = $values;' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	public function _SimDAL_diff($data) {' . PHP_EOL;
		$output .= '		$output = array();' . PHP_EOL;
		$output .= '		foreach ($this as $key=>$value) {' . PHP_EOL;
		$output .= '			if ($key == \'id\') {' . PHP_EOL;
		$output .= '				continue;' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
		$output .= '			$method = \'get\' . ucfirst($key);' . PHP_EOL;
		$output .= '			if (method_exists($data, $method) && ((is_scalar($this->$key) || is_scalar($data->$method())) && (!is_null($this->$key) || !is_null($data->$method()))) && method_exists($data, $method)) {' . PHP_EOL;
		$output .= '				if ($this->$key != $data->$method()) {' . PHP_EOL;
		$output .= '					$output[$key] = $this->$key;' . PHP_EOL;
		$output .= '				}' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		return $output;' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	public function _SimDAL_GetAssociation($identifier) {' . PHP_EOL;
		$output .= '		$mapping = $this->_getSession()->getMapper()->getMappingForEntityClass(get_class($this));' . PHP_EOL;
		$output .= '		return $mapping->getAssociation($identifier);' . PHP_EOL;
		$output .= '	}' . PHP_EOL;
		
		$output .= '	public function _SimDAL_SetReference($entity, $otherside_association) {' . PHP_EOL;
		$output .= '		$association = $otherside_association->getMatchingAssociationFromAssociationClass();' . PHP_EOL;
		$output .= '		$method = $association->getMethod();' . PHP_EOL;
		$output .= '		if ($association->isOneToMany()) {' . PHP_EOL;
		$output .= '			$getter = \'get\' . ucfirst($method);' . PHP_EOL;
		$output .= '			$this->$getter()->add($entity);' . PHP_EOL;
		$output .= '		} else {' . PHP_EOL;
		$output .= '			$setter = \'set\' . ucfirst($method);' . PHP_EOL;
		$output .= '			$property = $association->getProperty();' . PHP_EOL;
		$output .= '			if ($this->$property !== $entity) {' . PHP_EOL;
		$output .= '				$this->$setter($entity);' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
		$output .= '		}' . PHP_EOL; 
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		return $output;
	}
	
	static protected function _generateProxyMethods(SimDAL_Mapper_Entity $mapping) {
		$associations = $mapping->getAssociations();
		$methods = '';
		
		/* @var $association SimDAL_Mapper_Association */
		if (count($associations)) {
			foreach ($associations as $association) {
				switch ($association->getType()) {
					case 'one-to-many': $methods .= self::_generateProxyMethodForOneToManyAssociation($association, $mapping); break;
					case 'one-to-one': $methods .= self::_generateProxyMethodForOneToOneAssociation($association, $mapping); break;
					case 'many-to-one': $methods .= self::_generateProxyMethodForManyToOneAssociation($association, $mapping); break;
				}
			}
		}
		
		return $methods;
	}
	
	static protected function _generateProxyMethodForOneToManyAssociation(SimDAL_Mapper_Association $association, SimDAL_Mapper_Entity $mapping) {
		$method = ucfirst($association->getMethod());
		$property = $association->getProperty();
		$getter = 'get' . $method;
		$setter = 'set' . $method;
		
		$output = '';
		$output .= '	public function ' . $getter . '() {' . PHP_EOL;
		$output .= '		if (!$this->' . $property . ' instanceof SimDAL_Collection) {' . PHP_EOL;
		$output .= '			$session = $this->_getSession();' . PHP_EOL;
		$output .= '			$mapper = $session->getMapper();' . PHP_EOL;
		$output .= '			$mapping = $mapper->getMappingForEntityClass(get_class($this));' . PHP_EOL;
		$output .= '			foreach ($mapping->getAssociations() as $assoc) {' . PHP_EOL;
		$output .= '				if (\'' . $property . '\' == $assoc->getProperty()) {' . PHP_EOL;
		$output .= '					$association = $assoc;' . PHP_EOL;
		$output .= '					break;' . PHP_EOL;
		$output .= '				}' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
		$output .= '			$this->' . $property . ' = new SimDAL_Persistence_Collection($this, $session, $association);' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		return parent::' . $getter . '();' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		return $output;
	}
	
	static protected function _generateProxyMethodForOneToOneAssociation(SimDAL_Mapper_Association $association, SimDAL_Mapper_Entity $mapping) {
		$method = ucfirst($association->getMethod());
		$getter = 'get' . $method;
		$setter = 'set' . $method;
		
		$output = '';
		$output .= '	public function ' . $getter . '($load=true) {' . PHP_EOL;
		$output .= '		if ($load && !$this->_isSimDALAssociationLoaded(\'' . $association->getMethod() . '\')) {' . PHP_EOL;
		$output .= '			$session = SimDAL_Session::factory()->getCurrentSession();' . PHP_EOL;
		if ($association->isDependent ()) {
			$output .= '                    $this->' . $setter . '(' . PHP_EOL;
			$output .= '                            $session->load(\'' . $association->getClass () . '\')' . PHP_EOL;
			$output .= '                            ->whereColumn(\'' . $association->getParentKey () . '\')' . PHP_EOL;
			$output .= '                            ->equals($this->get' . ucfirst ( $association->getForeignKey () ) . '())' . PHP_EOL;
			$output .= '                            ->fetch()' . PHP_EOL;
			$output .= '                    );' . PHP_EOL;
		} else if ($association->isParent ()) {
			$output .= '                    $this->' . $setter . '(' . PHP_EOL;
			$output .= '                            $session->load(\'' . $association->getClass () . '\')' . PHP_EOL;
			$output .= '                            ->whereColumn(\'' . $association->getForeignKey () . '\')' . PHP_EOL;
			$output .= '                            ->equals($this->get' . ucfirst ( $association->getParentKey () ) . '())' . PHP_EOL;
			$output .= '                            ->fetch()' . PHP_EOL;
			$output .= '                    );' . PHP_EOL;
		}
		$output .= '			$this->_SimDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		return parent::' . $getter . '();' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	public function ' . $setter . '(' . $association->getClass() . ' $value=null, $set_circlic_ref=true) {' . PHP_EOL;
		$output .= '		if (!is_null($value)) {' . PHP_EOL;
		if ($association->isDependent()) {
			$output .= '			$this->' . $association->getForeignKey() . ' = $value->get' . ucfirst($association->getParentKey()) . '();' . PHP_EOL;
		}
		$output .= '			if (!$this->_getSession()->isLoaded($value) && !$this->_getSession()->isAdded($value)) {' . PHP_EOL;
		$output .= '				$this->_getSession()->addEntity($value);' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		$this->_SimDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
		$output .= '		parent::' . $setter . '($value);' . PHP_EOL . PHP_EOL;
		$output .= '		if (!is_null($value) && $set_circlic_ref) {' . PHP_EOL;
		$output .= '			$value->_SimDAL_SetReference($this, $this->_SimDAL_GetAssociation(\'' . $association->getIdentifier() . '\'));' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		return $output;
	}
	
	static protected function _generateProxyMethodForManyToOneAssociation(SimDAL_Mapper_Association $association, SimDAL_Mapper_Entity $mapping) {
		$method = ucfirst($association->getMethod());
		$getter = 'get' . $method;
		$setter = 'set' . $method;
		
		$output = '';
		$output .= '	public function ' . $getter . '($load=true) {' . PHP_EOL;
		$output .= '		if ($load && !$this->_isSimDALAssociationLoaded(\'' . $association->getMethod() . '\')) {' . PHP_EOL;
		$output .= '			$session = SimDAL_Session::factory()->getCurrentSession();' . PHP_EOL;
		$output .= '			$this->' . $setter . '(' . PHP_EOL;
		$output .= '				$session->load(\'' . $association->getClass() . '\')' . PHP_EOL;
		$output .= '				->whereColumn(\'' . $association->getParentKey() . '\')' . PHP_EOL;
		$output .= '				->equals($this->get' . ucfirst($association->getForeignKey()) . '())' . PHP_EOL;
		$output .= '				->fetch()' . PHP_EOL;
		$output .= '			);' . PHP_EOL;
		$output .= '			$this->_SimDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		return parent::' . $getter . '();' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	public function ' . $setter . '(' . $association->getClass() . ' $value=null, $set_circlic_ref=true) {' . PHP_EOL;
		$output .= '		if (!is_null($value)) {' . PHP_EOL;
		$output .= '			$this->' . $association->getForeignKey() . ' = $value->get' . ucfirst($association->getParentKey()) . '();' . PHP_EOL;
		$output .= '			if (!$this->_getSession()->isLoaded($value) && !$this->_getSession()->isAdded($value)) {' . PHP_EOL;
		$output .= '				$this->_getSession()->addEntity($value);' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		$this->_SimDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
		$output .= '		parent::' . $setter . '($value);' . PHP_EOL . PHP_EOL;
		$output .= '		if (!is_null($value) && $set_circlic_ref) {' . PHP_EOL;
		$output .= '			$value->_simDAL_SetReference($this, $this->_SimDAL_GetAssociation(\'' . $association->getIdentifier() . '\'));' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		return $output;
	}
	
}