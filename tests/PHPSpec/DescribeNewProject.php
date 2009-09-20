<?php

//require_once 'Project.php';

class DescribeNewProject extends PHPSpec_Context {
	
	protected $_project = null;
	protected $_manager = null;
	
	public function before() {
		$this->_manager = mockery('SimDAL_Entity_ManagerInterface');
		SimDAL_Entity::setDefaultEntityManager( $this->_manager );
		$this->_project = new TestDomain_Project(array(), $this->_manager);
	}
	
	public function after() {
		$this->_project = null;
		$this->_manager = null;
		SimDAL_Entity::reset();
	}
	
	public function itShouldThrowNoManagerExceptionIfNoEntityManagerIsPassedToTheConstructorWhenThereIsNoDefaultManagerSet() {
		SimDAL_Entity::reset();
		
		$this->spec('TestDomain_Project', array())->should->throw('SimDAL_Entity_NoEntityManagerException');
	}
	
	public function itShouldUseSetDefaultManagerWhenNoEntityManagerIsPassedToTheConstructor() {
		$project = new TestDomain_Project();
		
		$this->spec($project->getEntityManager())->should->equal($this->_manager);
	}
	
	public function itShouldUseTheEntityManagerPassedToTheConstructor() {
		$manager = mockery('SimDAL_Entity_ManagerInterface');
		$project = new TestDomain_Project(array(), $manager);
		
		$this->spec($project->getEntityManager())->should->equal($manager);
	}
	
	public function itShouldAutomaticallyHaveMutatorsForItsProperties() {
		$this->_manager->shouldReceive('hasRelation')->once()->with('Name', 'TestDomain_Project')->andReturn( false );
		$this->_manager->shouldReceive('hasRelation')->once()->with('Description', 'TestDomain_Project')->andReturn( false );
		$this->_manager->shouldReceive('hasRelation')->once()->with('TypeId', 'TestDomain_Project')->andReturn( false );
		//$this->_manager->shouldReceive('getBy')->once()->with(1, 'type')->andReturn( new SimDAL_Entity() );
		
		$this->_project->setName( "Spec Ops" );
		$this->_project->setDescription( "Lightweight application for project analysis" );
		$this->_project->setTypeId( 1 );

		$this->spec($this->_project->getName())->should->equal("Spec Ops");
		$this->spec($this->_project->getDescription())->should->equal("Lightweight application for project analysis");
		$this->spec($this->_project->getTypeId())->should->equal(1);
		
		mockery_verify();
	}
	
	public function itShouldThrowInvalidMutatorExceptionWhenTryingToGetOrSetNonExistentProperty() {
		
		$this->spec($this->_project, 'setNonExistent', array("This won't work"))
			->should->throw('SimDAL_Entity_NonExistentMutatorException');
		
		$this->spec($this->_project, 'getNonExistent')
			->should->throw('SimDAL_Entity_NonExistentMutatorException');
	}
	
	public function itShouldGetTheRelationIfItIsDefined() {
		$type = new TestDomain_Type(array('id'=>1, 'type'=>'the type'));
		
		$this->_manager->shouldReceive('hasRelation')->once()->with('Type', 'TestDomain_Project')->andReturn( true );
		$this->_manager->shouldReceive('getBy')->once()->with(1, 'type')->andReturn( $type );
		$this->_manager->shouldReceive('hasRelation')->once()->with('Id', 'TestDomain_Type')->andReturn( false );
		$this->_manager->shouldReceive('hasRelation')->once()->with('Type', 'TestDomain_Type')->andReturn( false );
		
		$this->_project->setTypeId( 1 );
		
		$rType = $this->_project->getType();
		
		$this->spec( $rType )->should->equal( $type );
		
		$this->spec( $rType->getId() )->should->equal( 1 );
		$this->spec( $rType->getType() )->should->equal( 'the type' );
		
		mockery_verify();
	}
	
}

