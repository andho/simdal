<?php

class Simdal_Simdal extends Zend_Application_Resource_ResourceAbstract {
	
	private $adapterConfig = array();
	private $domainPath;
	
	public function setAdapterConfig($value) {
		$this->adapterConfig = $value;
	}
	
	public function setDomainPath($value) {
		$this->domainPath = $value;
	}
	
	public function init() {
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace('SimDAL_');
		SimDAL_Autoload::setDomainDirectory($this->domainPath);
		SimDAL_Session::factory(array('db'=>$this->adapterConfig));
		$autoloader->pushAutoloader(array('SimDAL_Autoload', 'autoload'));
	}
	
}