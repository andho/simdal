<?php

class DescribeSession extends PHPSpec\Context {
	
	private $session;
	
	public function before() {
		$project_m = mock('SimDAL_Mapper_Entity');
		
		$mapper = mock('SimDAL_Mapper');
		$mapper->stub('getMappingForEntityClass')
			->shouldReceive('Project')
			->andReturn($project_m)
			->exactly(1);
		
		$adapter = mock('SimDAL_Persistence_AdapterAbstract');
		//$adapter->shouldReceive('autoCommit')->once();
		
		$this->session = new SimDAL_Session($mapper, $adapter);
	}
	
	public function after() {
		
	}
	
	public function itShouldLoadEntities() {
		$this->session->load('Project');
	}
	
}
