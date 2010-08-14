<?php

class SimDAL_PersistenceAdapterIsNotSetException extends Exception {
	
	public $message = "You have not set an adapter for the repository";
	
}