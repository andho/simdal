<?php

class SimDAL_Entity_NonExistentMutatorException extends Exception {

	protected $_message = "You have tried to set or get a non existent property";
	
}