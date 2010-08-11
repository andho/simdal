<?php

class DescribeUnitOfWork extends PHPSpec_Context {
	
	/**
	 * Unit of work
	 *
	 * @var SimDAL_UnitOfWork
	 */
	private $unitOfWork;
	private $adapter;
	private $mapper;
	
	public function beforeAll() {
		$this->mapper = mockery('SimDAL_Mapper');
		
		$this->mapper->shouldReceive('getTable')->with('TestDomain_Project')->andReturn('projects');
		$columns = array(
			'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
			'name' => array('name', 'varchar'),
			'description' => array('description', 'varchar'),
			'typeId' => array('type_id', 'int')
		);
		$relations = array(
			array('many-to-one', 'TestDomain_Type', array('fk'=>'typeId'))
		);
		$this->mapper->shouldReceive('getColumnData')->with('TestDomain_Project')->andReturn($columns);
		$this->mapper->shouldReceive('getManyToOneRelations')->with('TestDomain_Project')->andReturn($relations);
		
		$this->mapper->shouldReceive('getTable')->with('TestDomain_Type')->andReturn('types');
		$columns2 = array(
			'id' => array('id', 'int', array('pk'=>true, 'autoIncrement'=>true)),
			'name' => array('name', 'varchar')
		);
		$relations2 = array(
			array('one-to-many', 'TestDomain_Type', array('fk'=>'typeId'))
		);
		$this->mapper->shouldReceive('getColumnData')->with('TestDomain_Type')->andReturn($columns2);
		$this->mapper->shouldReceive('getOneToManyRelations')->with('TestDomain_Type')->andReturn($relations2);
		$this->mapper->shouldReceive('getManyToOneRelations')->with('TestDomain_Type')->andReturn(array());
		$this->mapper->shouldReceive('getPrimaryKey')->with('TestDomain_Type')->andReturn('id');
		$this->mapper->shouldReceive('getTable')->with('TestDomain_Type')->andReturn('types');
	}
	
	public function afterAll() {
		unset($this->mapper);
	}
	
	public function before() {
		$this->adapter = mockery('SimDAL_Persistence_AdapterInterface');
		
		$this->unitOfWork = new SimDAL_UnitOfWork($this->adapter, $this->mapper);
		
		SimDAL_Repository::setDefaultAdapter($this->adapter);
		SimDAL_Repository::setDefaultMapper($this->mapper);
	}
	
	public function after() {
		unset($this->unitOfWork);
		unset($this->adapter);
	}
	
	public function itShouldAddNewEntityToBeInserted() {
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test Project';
		
		$this->unitOfWork->add($project);
		
		$result = $this->unitOfWork->getNew();
		
		$this->spec($result)->should->equal(array('projects'=>array($project)));
	}
	
	public function itShouldAddEntityToBeUpdated() {
		$project = new TestDomain_Project();
		$project->id = 1;
		$project->name = 'Project';
		$project->description = 'changed';
		$project->typeId = 1;
		
		$actual_data = array(
			'id' => 1,
			'name' => 'Project',
			'description' => 'This is a test Project',
			'typeId' => 1
		);
		
		$this->unitOfWork->update($project, $actual_data);
		
		$result = $this->unitOfWork->getChanges();
		
		$this->spec($result)->should->equal(array('projects'=>array(1=>array('description'=>'changed'))));
	}
	
	public function itShouldAddEntityToBeDeleted() {
		$project = new TestDomain_Project();
		$project->id = 1;
		$project->name = 'Project';
		$project->description = 'changed';
		$project->typeId = 1;
		
		$this->unitOfWork->delete($project);
		
		$result = $this->unitOfWork->getDeleted();
		
		$this->spec($result)->should->equal(array('projects'=>array(1=>$project)));
	}
	
	public function itShouldAddEntityToBeDeletedById() {
		$this->unitOfWork->delete(1, 'projects');
		
		$result = $this->unitOfWork->getDeleted();
		
		$this->spec($result)->should->equal(array('projects'=>array(1=>1)));
	}
	
	public function itShouldAddRelatedEntitiesToBeInserted() {
		$this->adapter->shouldReceive('insert')->with('types', array('name'=>'Library'))->andReturn(1);
		$this->adapter->shouldReceive('insert')->with('projects', array(
			'name' => 'Project',
			'description' => 'This is a test Project',
			'type_id' => 1
		))->andReturn(1);
		
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test Project';
		
		$this->unitOfWork->add($project);
		
		$type = new TestDomain_Type();
		$type->name = 'Library';
		$project->setTestDomain_Type($type);
		
		$this->unitOfWork->commit();
		
		$this->adapter->mockery_verify();
	}
	
	public function itShouldAddRelatedEntitiesToBeUpdated() {
		$this->adapter->shouldReceive('findById')->with('types', 1, 'id')->andReturn(1);
		$type_data = array(
			'id' => 1,
			'name' => 'Library'
		);
		$this->adapter->shouldReceive('update')->with('types', array('name'=>'Library changed'), 1)->andReturn(1);
		$this->adapter->shouldReceive('insert')->with('projects', array(
			'name' => 'Project',
			'description' => 'This is a test Project',
			'type_id' => 1
		))->andReturn(1);
		
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test Project';
		$project->typeId = 1;
		
		$type = $project->getTestDomain_Type();
		
		$this->unitOfWork->add($project);
		
		$this->adapter->mockery_verify();
	}
	
	public function itShouldSaveNewEntitiesOnCommit() {
		$data = array(
			'name' => 'Project',
			'description' => 'This is a test Project'
		);
		$this->adapter->shouldReceive('insert')->with('projects', $data)->andReturn(1);
		
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test Project';
		
		$this->unitOfWork->add($project);
		$this->unitOfWork->commit();
		
		$this->adapter->mockery_verify();
	}
	
	public function itShouldInsertMultipleEntitiesOfTheSameTypeInOneQuery() {
		$sql = "INSERT INTO `projects` VALUES (NULL,'Project','This is a test Project',NULL),(NULL,'Project2','This is a test Project too',NULL)";
		$this->adapter->shouldReceive('query')->with($sql)->andReturn(2);
		
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test Project';
		
		$project2 = new TestDomain_Project();
		$project2->name = 'Project2';
		$project2->description = 'This is a test Project too';
		
		$this->unitOfWork->add($project);
		$this->unitOfWork->add($project2);
		
		$this->unitOfWork->commit();
		
		$this->adapter->mockery_verify();
	}
	
	public function itShouldUpdateLoadedModifiedEntitiesOnCommit() {
		$this->pending();
	}
	
	public function itShouldDeleteEntitiesToBeDeletedOnCommit() {
		$this->pending();
	}
	
	public function itShouldRelatedEntitiesOfNewEntitiesOnCommit() {
		$type_data = array(
			'name' => 'Library'
		);
		$project_data = array(
			'name' => 'Project',
			'description' => 'This is a test Project',
			'type_id' => 1
		);
		$this->adapter->shouldReceive('insert')->once()->with('types', $type_data)->andReturn(1);
		$this->adapter->shouldReceive('insert')->once()->with('projects', $project_data)->andReturn(1);
		
		$project = new TestDomain_Project();
		$project->name = 'Project';
		$project->description = 'This is a test Project';
		$type = new TestDomain_Type();
		$type->name = 'Library';
		$project->setTestDomain_Type($type);
		
		$this->unitOfWork->add($project);
		
		$this->unitOfWork->commit();
		
		$this->adapter->mockery_verify();
	}
	
	public function itShouldUpdateLoadedRelatedEntitiesOnCommit() {
		$this->pending();
	}
	
	public function itShouldCascadeDeleteIfSpecified() {
		$this->pending();
	}
	
}