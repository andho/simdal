<?php

interface SimDAL_Query_Where_Interface {
	
	public function getRightValue();

	public function getLeftValue();
	
	public function getOperator();
	
}