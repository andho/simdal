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
			if (!mkdir($cachedir, 0775, true)) {
				echo "Could not create cache directory";
				return false;
			}
		}
		
		$cachefile = $cachedir . DIRECTORY_SEPARATOR . 'simdal_proxies.inc';
		if (!is_file($cachefile)) {
			touch($cachefile);
		}
		
		$classes = $mapper->getClasses();
		$output = '<?php' . PHP_EOL . PHP_EOL;
		foreach ($classes as $class) {
			$output .= self::_generateProxy($mapper->getMappingForEntityClass($class));
		}
		//echo '<pre>' . $output . '</pre>';
		file_put_contents($cachefile, $output);
		
		//include $cachefile;
	}
	
	static protected function _generateProxy(SimDAL_Mapper_Entity $mapping) {
		$class = $mapping->getClass();
		
		if (!class_exists($class)) {
			throw new Exception("Class '{$class}' in mapper does not exist");
		}
		
		$proxy_class = $class . 'Proxy';
		
		$class = self::_generateProxyClass($mapping);
		$helper_properties .= self::_generateHelperProperties($mapping);
		$helper_methods .= self::_generateHelperMethods($mapping);
		$proxy_methods .= self::_generateProxyMethods($mapping);
		
		$class .= $helper_properties;
		$class .= $helper_methods;
		$class .= $proxy_methods;
		$class .= '}' . PHP_EOL . PHP_EOL;
		
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
				$class .= $descendent_class;
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
			$output .= '			if (method_exists($data, \'' . $getter . '\')) {' . PHP_EOL;
			if ($association->getType() == 'many-to-one' || $association->getType() == 'one-to-one') {
				$output .= '				$this->' . $setter . '($data->' . $getter . '());' . PHP_EOL;
			} else if ($association->getType() == 'one-to-many') {
				$output .= '				$this->' . $property . ' = $data->' . $getter . '();' . PHP_EOL;
			}
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
		$output .= '	private function _simDALAssociationIsLoaded($association_name) {' . PHP_EOL;
		$output .= '		if (!array_key_exists($association_name, $this->_loadedSimDALEntities)) {' . PHP_EOL;
		$output .= '			throw new Exception(__METHOD__ . \' called with invalid association name\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		$this->_loadedSimDALEntities[$association_name] = true;' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		$output .= '	private function _getSession() {' . PHP_EOL;
		$output .= '		return $this->_session;' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		$output .= '	public function _SimDAL_setPrimaryKey($values) {' . PHP_EOL;
		$primary_key = $mapping->getPrimaryKey();
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
		$output .= '			$this->_simDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		return parent::' . $getter . '();' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		if ($association->isDependent()) {
			$output .= '	public function ' . $setter . '(' . $association->getClass() . ' $value=null) {' . PHP_EOL;
			$output .= '		if (!is_null($value)) {' . PHP_EOL;
			$output .= '			$this->set' . ucfirst($association->getForeignKey()) . '($value->get' . ucfirst($association->getParentKey()) . '());' . PHP_EOL;
			$output .= '		}' . PHP_EOL;
			$output .= '		parent::' . $setter . '($value);' . PHP_EOL;
			$output .= '	}' . PHP_EOL . PHP_EOL;
		}
		
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
		$output .= '			$this->_simDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		return parent::' . $getter . '();' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		$output .= '	public function ' . $setter . '(' . $association->getClass() . ' $value=null) {' . PHP_EOL;
		$output .= '		if (!is_null($value)) {' . PHP_EOL;
		$output .= '			$this->set' . ucfirst($association->getForeignKey()) . '($value->get' . ucfirst($association->getParentKey()) . '());' . PHP_EOL;
		$output .= '		}' . PHP_EOL;
		$output .= '		parent::' . $setter . '($value);' . PHP_EOL;
		$output .= '	}' . PHP_EOL . PHP_EOL;
		
		return $output;
	}
	
}