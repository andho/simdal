<?php

//require_once 'tests/TestSample/Project.php';

require_once '../PHPUnitTestConfiguration.php';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Project test case.
 */
class ProjectTest extends PHPUnit_Framework_TestCase {
	
	private $Project;
	private $_manager;
	
	protected function setUp() {
		$this->_manager = $this->getMock('SimDAL_Entity_ManagerInterface');
		SimDAL_Entity::setDefaultEntityManager( $this->_manager );
		$this->_project = new TestSample_Project(array(), $this->_manager);
	}
	
	protected function tearDown() {
		$this->_project = null;
		SimDAL_Entity::reset();
	}
	
	public function testThatItThrowNoManagerExceptionIfNoEntityManagerIsPassedToTheConstructorWhenThereIsNoDefaultManagerSet() {
		SimDAL_Entity::reset();
		
		$this->setExpectedException('SimDAL_Entity_NoEntityManagerException');
		$project = new TestSample_Project();
	}
	
	public function itShouldUseSetDefaultManagerWhenNoEntityManagerIsPassedToTheConstructor() {
		$this->pending();
	}
	
	public function itShouldUseTheEntityManagerPassedToTheConstructor() {
		$this->pending();
	}
	
	public function testIfItAutomaticallyHasMutatorsForItsProperties() {
		$this->_manager->expects($this->once())
			->method('hasRelation')
			->with($this->equalTo('name'))
			->will($this->returnValue(false))
		;
		$this->_manager->expects($this->once())
			->method('hasRelation')
			->with($this->equalTo('description'))
			->will($this->returnValue(false))
		;
		
		$this->_project->setName( "Spec Ops" );
		$this->_project->setDescription( "Lightweight application for project analysis" );
		
		$this->assertEquals("Spec Ops", $this->_project->getName());
		$this->assertEquals("Lightweight application for project analysis", $this->_project->getDescription());
		//$this->assertType("SimDAL_Entity", $this->_project->getType());
	}
	
	public function itShouldThrowInvalidMutatorExceptionWhenTryingToGetOrSetNonExistentProperty() {
		
		$this->spec($this->_project, 'setNonExistent', array("This won't work"))
			->should->throw('SimDAL_Entity_NonExistentMutatorException');
		
		$this->spec($this->_project, 'getNonExistent')
			->should->throw('SimDAL_Entity_NonExistentMutatorException');
	}
	
	public function itShouldGetRelatedEntityWhenTheRespectivePropertyIsCalledFor() {
		$this->pending();
	}

}

