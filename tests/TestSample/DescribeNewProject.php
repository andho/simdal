<?php

require_once 'Project.php';

class DescribeNewProject extends PHPSpec_Context {
	
	protected $_project = null;
	
	public function before() {
		//$this->_project = new Project();
	}
	
	public function after() {
		$this->_project = null;
	}
	
	public function itShouldThrowNoRepositoryExceptionIfNoRepositoryIsPassedToTheConstructor() {
		$this->spec('Project', array())->should->throw('SimDAL_Entity_NoRepositoryException');
	}
	
	/*public function itShouldAutomaticallyHaveMutatorsForItsProperties() {
		$this->_project->setName( "Spec Ops" );
		$this->_project->setDescription( "Lightweight application for project analysis" );
		$this->_project->setType( 1 );

		$this->spec($this->_project->getName)->should->equal("Spec Ops");
	}
	
	public function itShouldThrowInvalidSetterExceptionIfSettingANonExistentProperty() {
		try {
			$this->_project->setNonExistent( "This won't work" );
		} catch (InvalidSetterException $e) {
			$this->spec($e)->should->beAnInstanceOf('InvalidSetterException');
		}
	}*/
	
}

