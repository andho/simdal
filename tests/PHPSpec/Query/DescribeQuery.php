<?php

class DescribeQuery extends \PHPSpec\Context {
	
	/**
	 * 
	 * @var SimDAL_Query
	 */
	private $query = null;
	
	public function before() {
		$this->query = $this->spec(new SimDAL_Query());
	}
	
	public function itShouldSelectFromWhereDataWillBeRetrieved() {
		$this->query->from('Something');
		
		$this->query->getFrom()->should->equal('Something');
	}
	
	public function itShouldSelectPropertiesToRetieve() {
		$this->query->from('Something', array('name', 'type'));
		
		$this->query->getColumns()->should->equal(array('name', 'type'));
	}
	
	public function itShouldSpecifyFilters() {
		$this->query->from('Something');
		$this->query->whereProperty('name')->isEqualTo('The Name');
		
		$this->query->getWheres()->should->equal(array('name'=>'The Name'));
	}
	
}