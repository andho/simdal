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
		
		$start = self::_generateProxyClass($mapping);
		$methods = self::_generateProxyMethods($mapping);
	}
	
	static protected function _generateProxyClass(SimDAL_Mapper_Entity $mapping) {
		$class = $mapping->getClass();
		$proxy_class = $class . 'Proxy';
		$class = 'class ' . $proxy_class . ' extends ' . $class . ' implements SimDAL_ProxyInterface {' . PHP_EOL;
	}
	
	static protected function _generateProxyMethods(SimDAL_Mapper_Entity $mapping) {
		$associations = $mapping->getAssociations();
		$methods = '';
		/* @var $association SimDAL_Mapper_Association */
		foreach ($associations as $association) {
			switch ($association->getType()) {
				case 'one-to-many': $methods .= self::_generateProxyMethod($association, $mapping); break;
				case 'one-to-one': $methods .= self::_ge
			}
		}
		
		return $methods;
	}
	
	static protected function _generateProxyMethod(SimDAL_Mapper_Association $association, SimDAL_Mapper_Entity $mapping) {
		$method = ucfirst($association->getMethod());
		$getter = 'get' . $method;
		$setter = 'set' . $method;
		
		$method = '';
		$method .= '	public function ' . $getter . '() {' . PHP_EOL;
		$method .= '		$session = Session::factory()->getCurrentSession();' . PHP_EOL;
		$method .= '		$session->load(\'' . $association->getClass() . '\')' . PHP_EOL;
		$method .= '			->whereColumn(\'' . $association->
		$method .= '		return parent::' . $getter . '();' . PHP_EOL;
		$method .= '	}' . PHP_EOL;
		
		return $method;
	}
	
}