<?php

class SimDAL_ProxyGenerator {
	
	static public function generateProxies(SimDAL_Mapper $mapper) {
		$classes = $mapper->getClasses();
		foreach ($classes as $class) {
			self::_generateProxy($mapper->getMappingForEntityClass($class));
		}
	}
	
	static protected function _generateProxy(SimDAL_Mapper_Entity $mapping) {
		$class = $mapping->getClass();
		
		if (!class_exists($class)) {
			throw new Exception("Class '{$class}' in mapper does not exist");
		}
		
		$proxy_class = $class . 'Proxy';
		
		$class = self::_generateProxyClass($mapping);
		$class .= self::_generateProxyMethods($mapping);
		$class .= '}';
		
		echo '<pre>' . $class . '</pre>';
	}
	
	static protected function _generateProxyClass(SimDAL_Mapper_Entity $mapping) {
		$class = $mapping->getClass();
		$proxy_class = $class . 'Proxy';
		$class = 'class ' . $proxy_class . ' extends ' . $class . ' implements SimDAL_ProxyInterface {' . PHP_EOL;
		
		return $class;
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
		$getter = 'get' . $method;
		$setter = 'set' . $method;
		
		$method = '';
		$method .= '	public function ' . $getter . '() {' . PHP_EOL;
		$method .= '		$session = SimDAL_Session::factory()->getCurrentSession();' . PHP_EOL;
		$method .= '		$this->' . $setter . '($session->load(\'' . $association->getClass() . '\')' . PHP_EOL;
		$method .= '			->whereColumn(\'' . $association->getForeignKey() . '\')' . PHP_EOL;
		$method .= '			->equals($this->get' . ucfirst($association->getParentKey()) . '));' . PHP_EOL;
		$method .= '		return parent::' . $getter . '();' . PHP_EOL;
		$method .= '	}' . PHP_EOL;
		
		return $method;
	}
	
	static protected function _generateProxyMethodForOneToOneAssociation(SimDAL_Mapper_Association $association, SimDAL_Mapper_Entity $mapping) {
		
	}
	
}