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
		$class = 'class ' . $proxy_class . ' implements SimDAL_ProxyInterface {' . PHP_EOL;
	}
	
	static protected function _generateProxyMethods(SimDAL_Mapper_Entity $mapping) {
		$columns = $mapping->getColumns();
		$methods = '';
		foreach ($columns as $column) {
			$methods .= self::_generateProxyMethod($column);
		}
		
		return $methods;
	}
	
	static protected function _generateProxyMethod(SimDAL_Mapper_Column $column) {
		$property = ucfirst($column->getProperty());
		$getter = 'get' . $property;
		$setter = 'set' . $property;
		
		$method = '';
		$method .= '	public function ' . $getter . '() {' . PHP_EOL;
		$method .= '		$this->load();' . PHP_EOL;
		$method .= '		return parent::' . $getter . '();' . PHP_EOL;
		$method .= '	}' . PHP_EOL;
		
		return $method;
	}
	
}