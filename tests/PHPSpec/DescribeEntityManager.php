<?php

class DescribeEntityManager extends PHPSpec_Context {
	
	private $_manager = null;
	
	public function before() {
		$this->_manager = TestDomain_EntityManager::getInstance();
	}
	
	public function after() {
		$this->_manager = null;
	}
	
	public function itShouldBeASingleton() {
		$entitymanager = TestDomain_EntityManager::getInstance();
		
		$this->spec($entitymanager)->should->equal($this->_manager);
	}
	
}