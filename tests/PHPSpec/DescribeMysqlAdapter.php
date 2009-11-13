<?php

class DescribeMysqlAdapter extends PHPSpec_Context {
	
	private $adapter;
	private $id;
	
	public function beforeAll() {
		$this->adapter = new SimDAL_Persistence_MysqlAdapter('localhost', 'root', '', 'testdomain');
	}
	
	public function afterAll() {
		$conn = mysql_connect('localhost', 'root', '');
		mysql_select_db('testdomain');
		mysql_query("TRUNCATE TABLE `projects`", $conn);
		unset($this->adapter);
	}
	
	public function itShouldBeAbleToSaveDataToDatabase() {
		$data = array(
			'name' => 'Project',
			'description' => 'This is a test project'
		);
		
		$id = $this->adapter->insert('projects', $data);
		
		$this->spec($id)->should->beInteger();
		
		$this->id = $id;
	}
	
	public function itShouldBeAbleToRetrieveRowsFromTheDatabase() {
		$project = $this->adapter->findById('projects', $this->id);
		
		$this->spec($project['name'])->should->equal('Project');
	}
	
	public function itShouldBeAbleToUpdateRowsInTheDatabase() {
		$data = array('description'=>'changed');
		
		$this->adapter->update('projects', $data, $this->id);
		
		$project = $this->adapter->findById('projects', $this->id);
		
		$this->spec($project['description'])->should->equal('changed');
	}
	
	public function itShouldBeAbleToDeleteRowsInTheDatabase() {
		$this->adapter->delete('projects', $this->id);
		
		$project = $this->adapter->findById('projects', $this->id);
		
		$this->spec($project)->should->beNull();
	}
	
}