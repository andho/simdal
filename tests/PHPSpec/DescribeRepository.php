<?php

class DescribeRepository extends PHPSpec_Context {
	
	private $repo;
	
	public function before() {
		$this->repo = new TestDomain_ProjectRepository();
	}
	
	public function after() {
		unset($this->repo);
	}
	
	public function itShouldSaveNewEntityToStorage() {
		$project = new TestDomain_Project();
		$project->name = "Project";
		$project->description = "This is a test Project";
		
		$this->repo->save($project);
		
		$this->spec($project->id)->shouldNot->beNull();
	}

}