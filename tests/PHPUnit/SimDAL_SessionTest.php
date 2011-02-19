<?php

require_once 'SimDAL_Unit_TestCase.php';

/**
 * SimDAL_Session test case.
 */
class SimDAL_SessionTest extends SimDAL_Unit_TestCase {
	
	/**
	 * 
	 * @var SimDAL_Session
	 */
	protected $session;
	
	public function setUp() {
		parent::setUp();
		
		/*$config = include('config.php');
		$mapper = new SimDAL_Mapper($config['map']);
		$adapter = 
		$session = new SimDAL_Session($mapper, $adapter);*/
		
		$this->session = SimDAL_Session::factory()->getCurrentSession();
	}
	
	public function testIfSessionLoadReturnsQuery() {
		$query = $this->session->load('Project');
		
		$this->assertType('SimDAL_Query', $query);
	}
	
	public function testIfSessionReturnsAnEntityWhenFetchIsCalledOnQuery() {
		$project = $this->session->load('Project')->fetch();
		
		$this->assertType('Project', $project);
	}
	
	public function testIfSessionCanAddEntity() {
		$project = new Project();
		
		$project->setName('My Project');
		
		$this->session->addEntity($project);
		
		$this->assertTrue($this->session->isAdded($project));
	}
	
}

