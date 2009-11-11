<?php

class DescribeMysqlAdapter extends PHPSpec_Context {
	
	private $adapter;
	
	public function beforeAll() {
		$this->adapter = new SimDAL_Persistence_MysqlAdapter('localhost', 'root', '', 'test_domain');
	}
	
	public function afterAll() {
		unset($this->adapter);
	}
	
	public function itShouldBeAbleToSaveDataToDatabase() {
		$data = array(
			'name' => 'Project',
			'description' => 'This is a test project'
		);
		
		$id = $this->adapter->insert('projects', $data);
		
		$this->spec($id)->should->beInteger();
	}
	
}