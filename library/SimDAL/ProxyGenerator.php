<?php

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
		
		include $cachefile;
	}
	
	static protected function _generateProxy(SimDAL_Mapper_Entity $mapping) {
		$class = $mapping->getClass();
		
		if (!class_exists($class)) {
			throw new Exception("Class '{$class}' in mapper does not exist");
		}
		
		$proxy_class = $class . 'Proxy';
		
		$class = self::_generateProxyClass($mapping);
		$class .= self::_generateHelperProperties($mapping);
		$class .= self::_generateHelperMethods($mapping);
		$class .= self::_generateProxyMethods($mapping);
		$class .= '}' . PHP_EOL . PHP_EOL;
		
		return $class;
	}
	
	static protected function _generateProxyClass(SimDAL_Mapper_Entity $mapping) {
		$class = $mapping->getClass();
		$proxy_class = $class . 'SimDALProxy';
		$class = 'class ' . $proxy_class . ' extends ' . $class . ' implements SimDAL_ProxyInterface {' . PHP_EOL . PHP_EOL;
		
		return $class;
	}
	
	static protected function _generateHelperProperties(SimDAL_Mapper_Entity $mapping) {
		$associations = $mapping->getAssociations();
		$output = '';
		
		/* @var $association SimDAL_Mapper_Association */
		if (count($associations)) {
			$output = '';
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
		$output = '';
		$output .= '	public function __construct(array $data) {' . PHP_EOL;
		$output .= '		foreach ($data as $key=>$value) {' . PHP_EOL;
		$output .= '			if (property_exists($this, $key)) {' . PHP_EOL;
		$output .= '				$this->$key = $value;' . PHP_EOL;
		$output .= '			}' . PHP_EOL;
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
		$output .= '		if (!$this->_isSimDALAssociationLoaded(\'' . $association->getMethod() . '\')) {' . PHP_EOL;
		$output .= '			$session = SimDAL_Session::factory()->getCurrentSession();' . PHP_EOL;
		$output .= '			$this->' . $property . ' =' . PHP_EOL;
		$output .= '				$session->load(\'' . $association->getClass() . '\')' . PHP_EOL;
		$output .= '				->whereColumn(\'' . $association->getForeignKey() . '\')' . PHP_EOL;
		$output .= '				->equals($this->get' . ucfirst($association->getParentKey()) . '())' . PHP_EOL;
		$output .= '				->fetch(0)' . PHP_EOL;
		$output .= '			;' . PHP_EOL;
		$output .= '			$this->_simDALAssociationIsLoaded(\'' . $association->getMethod() . '\');' . PHP_EOL;
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
		$output .= '	public function ' . $getter . '() {' . PHP_EOL;
		$output .= '		if (!$this->_isSimDALAssociationLoaded(\'' . $association->getMethod() . '\')) {' . PHP_EOL;
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
		
		return $output;
	}
	
	static protected function _generateProxyMethodForManyToOneAssociation(SimDAL_Mapper_Association $association, SimDAL_Mapper_Entity $mapping) {
		$method = ucfirst($association->getMethod());
		$getter = 'get' . $method;
		$setter = 'set' . $method;
		
		$output = '';
		$output .= '	public function ' . $getter . '() {' . PHP_EOL;
		$output .= '		if (!$this->_isSimDALAssociationLoaded(\'' . $association->getMethod() . '\')) {' . PHP_EOL;
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
		
		return $output;
	}
	
}