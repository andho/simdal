<?php

class DescribeQueryProvider extends \PHPSpec\Context {
	
	private $queryProvider = null;
	
	public function before() {
		$this->queryProvider = $this->spec(new SimDAL_Session_QueryProvider);
	}
	
	public function itShouldProvideAQueryObject() {
		$this->queryProvider->getQuery()->should->beAnInstanceOf('SimDAL_Query');
	}
	
}