<?php

class DescribeRepository extends PHPSpec_Context {
	
	private $unitOfWork;
	private $mapper;
	private $adapter;
	private $repo;
	
	public function beforeAll() {
		$this->mapper = mockery('SimDAL_Mapper');
		$this->mapper->shouldReceive('getTable')->with('TestDomain_Project')->andReturn('projects');
		
		$columns = array(
			'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
			'name' => array('name', 'varchar'),
			'description' => array('description', 'varchar')
		);
		$this->mapper->shouldReceive('getColumnData')->with('TestDomain_Project')->andReturn($columns);
	}
	
	public function afterAll() {
		unset($this->mapper);
	}
	
	public function before() {
		$this->storage = array(
			'projects'=>array(
				'1'=>array(
					'id'=>1,
					'name'=>'Project',
					'description'=>'This is a test Project'
				),
				'2'=>array(
					'id'=>2,
					'name'=>'Project2',
					'description'=>'This is a test Project'
				)
			)
		);
		$this->adapter = mockery('SimDAL_Persistence_AdapterInterface');
		
		$this->unitOfWork = mockery('SimDAL_UnitOfWork');
		
		$this->repo = new TestDomain_ProjectRepository($this->adapter, $this->mapper, $this->unitOfWork);
	}
	
	public function after() {
		unset($this->repo);
		unset($this->adapter);
		unset($this->unitOfWork);
	}
	
	public function itShouldThrowExceptionIfNoPersistenceAdapterIsSet() {
		$this->spec('TestDomain_ProjectRepository', '__construct')->should->throw('SimDAL_PersistenceAdapterIsNotSetException');
	}
	
	public function itShouldThrowExceptionIfNoMapperIsSet() {
		$this->spec('TestDomain_ProjectRepository', '__construct', array($this->adapter))->should->throw('SimDAL_MapperIsNotSetException');
	}
	
	public function itShouldAddNewEntityToBeSaved() {
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test project';
		$project->typeId = 1;
		
		$this->repo->add($project);
		
		$result = $this->repo->getNew();
		
		$this->spec($project)->should->be($result[0]);
	}
	
	public function itShouldLoadUnloadedEntityFromAdapterWhenFindingById() {
		$this->adapter->shouldReceive('findById')->with('projects', 1)->andReturn($this->storage['projects']['1']);
		
		$prj = new TestDomain_Project();
		$prj->id = 1;
		$prj->name = 'Project';
		$prj->description = 'This is a test Project';
		
		$project = $this->repo->findById(1);
		
		$this->spec($project)->should->be($prj);
	}
	
	public function itShouldLoadLoadedEntityFromHashWhenFindingById() {
		$this->adapter->shouldReceive('findById')->with('projects', 1)->andReturn($this->storage['projects']['1'])->ordered();
		
		$project = $this->repo->findById(1);
		
		$project2 = $this->repo->findById(1);
		
		$this->spec($project)->should->be($project2);
	}
	
	public function itShouldOnlySendChangedFieldsToUpdate() {
		$this->adapter->shouldReceive('findById')->with('projects', 1)->andReturn($this->storage['projects']['1']);
		
		$project = $this->repo->findById(1);
		$project->description = 'changed';
		
		$result = $this->repo->getChanges();
		
		$this->spec($result)->should->be(array(1=>array('description'=>'changed')));
	}
	
	public function itShouldSetEntityForRemovalWhenEntityIsPassedToDeleteMethod() {
		$this->adapter->shouldReceive('findById')->with('projects', 1)->andReturn($this->storage['projects']['1']);
		
		$project = $this->repo->findById(1);
		
		$this->repo->delete($project);
		
		$result = $this->repo->getDeleted();
		
		$this->spec($result)->should->equal(array(1=>1));
	}
	
	public function itShouldSetEntityForRemovalWhenEntityIdIsPassedToDeleteMethod() {
		$this->repo->delete(1);
		
		$result = $this->repo->getDeleted();
		
		$this->spec($result)->should->equal(array(1=>1));
	}
	
	public function itShouldRevertEntityToItsOriginalValues() {
		$this->adapter->shouldReceive('findById')->with('projects', 1)->andReturn($this->storage['projects']['1']);
		
		$project = $this->repo->findById(1);
		
		$project->description = 'changed';
		
		$this->repo->revert($project);
		
		$result = $this->repo->getChanges();
		
		$this->spec($result)->should->be(array());
	}
	
	public function itShouldLoadEntitySetsBasedOnSql() {
		$this->adapter
			->shouldReceive('query')
			->with("SELECT * FROM `projects` WHERE `description`='This is a test Project'")
			->andReturn(array($this->storage['projects']['1'], $this->storage['projects']['2']));
			
		$project = new TestDomain_Project();
		$project->id = 1;
		$project->name = 'Project';
		$project->description = 'This is a test Project';
		$project2 = new TestDomain_Project();
		$project2->id = 2;
		$project2->name = 'Project2';
		$project2->description = 'This is a test Project';
		$expect = array(
			1=>$project,
			2=>$project2
		);
		
		$projects = $this->repo->query("SELECT * FROM `projects` WHERE `description`='This is a test Project'");
		
		$this->spec($projects)->should->be($expect);
	}
	
	public function itShouldBeAbleToUpdateEntireSetsInOneCommand() {
		$this->pending();
	}

	public function itShouldSaveChangesToEntitySetsThroughEntitySetCommandsInOneQueryToStorage() {
		$this->pending();
	}
	
	public function itShouldUpdateEntitiesWithoutLoading() {
		$this->pending();
	}
	
}