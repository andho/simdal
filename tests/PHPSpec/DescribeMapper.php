<?php

class DescribeMapper extends PHPSpec_Context {
	
	private $mapper = null;
	
	public function beforeAll() {
		$this->mapper = new TestDomain_Mapper();
	}
	
	public function afterAll() {
		unset($this->mapper);
	}
	
	public function itShouldGetTableForTheSpecifiedClass() {
		$result = $this->mapper->getTable('TestDomain_Project');
		
		$this->spec($result)->should->be('projects');
	}
	
}