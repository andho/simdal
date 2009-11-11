<?php

class DescribeRepository extends PHPSpec_Context {
	
	private $adapter;
	private $repo;
	
	public function before() {
		$this->storage = array(
			'projects'=>array(
				'1'=>array(
					'id'=>1,
					'name'=>'Project',
					'description'=>'This is a test Project'
				)
			)
		);
		$this->adapter = mockery('SimDAL_Persistence_AdapterInterface');
		$this->repo = new TestDomain_ProjectRepository($this->adapter);
	}
	
	public function after() {
		unset($this->repo);
		unset($this->adapter);
	}
	
	public function itShouldThrowExceptionIfNoPersistenceAdapterIsSet() {
		$this->spec('TestDomain_ProjectRepository', '__construct')->should->throw('SimDAL_PersistenceAdapterIsNotSetException');
	}
	
	public function itShouldAddNewEntityToBeSaved() {
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test project';
		
		$this->repo->add($project);
		
		$result = $this->repo->getNew();
		
		$this->spec($project)->should->be($result[0]);
	}
	
	public function itShouldLoadUnloadedEntityFromAdapterWhenFindingById() {
		$this->adapter->shouldReceive('findById')->with('project', 1)->andReturn($this->storage['projects']['1']);
		
		$prj = new TestDomain_Project();
		$prj->id = 1;
		$prj->name = 'Project';
		$prj->description = 'This is a test Project';
		
		$project = $this->repo->findById(1);
		
		$this->spec($project)->should->be($prj);
	}
	
	public function itShouldLoadLoadedEntityFromHashWhenFindingById() {
		$this->adapter->shouldReceive('findById')->with('project', 1)->andReturn($this->storage['projects']['1'])->ordered();
		
		$project = $this->repo->findById(1);
		
		$project2 = $this->repo->findById(1);
		
		$this->spec($project)->should->be($project2);
	}
	
	public function itShouldOnlySendChangedFieldsToUpdate() {
		$this->adapter->shouldReceive('findById')->with('project', 1)->andReturn($this->storage['projects']['1']);
		
		$project = $this->repo->findById(1);
		$project->description = 'changed';
		
		$result = $this->repo->getChanges();
		
		$this->spec($result)->should->be(array(1=>array('description'=>'changed')));
	}
	
	public function itShouldSetEntityForRemovalWhenEntityIsPassedToDeleteMethod() {
		$this->adapter->shouldReceive('findById')->with('project', 1)->andReturn($this->storage['projects']['1']);
		
		$project = $this->repo->findById(1);
		
		$this->repo->delete($project);
		
		$result = $this->repo->getDeleted();
		
		$this->spec($result)->should->equal(array(1));
	}
	
	public function itShouldSetEntityForRemovalWhenEntityIdIsPassedToDeleteMethod() {
		$this->repo->delete(1);
		
		$result = $this->repo->getDeleted();
		
		$this->spec($result)->should->equal(array(1));
	}
	
	public function itShouldRevertEntityToItsOriginalValues() {
		$this->adapter->shouldReceive('findById')->with('project', 1)->andReturn($this->storage['projects']['1']);
		
		$project = $this->repo->findById(1);
		
		$project->description = 'changed';
		
		$this->repo->revert($project);
		
		$result = $this->repo->getChanges();
		
		$this->spec($result)->should->be(array());
	}

}