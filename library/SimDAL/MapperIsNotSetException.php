<?php

class SimDAL_MapperIsNotSetException extends Exception {
	
	public $message = "You have not set a Mapper for the repository";
	
}