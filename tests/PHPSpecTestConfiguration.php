<?php

ini_set('display_errors', 'on');

$projectDir = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' ) . DIRECTORY_SEPARATOR;

$simdal_root = $projectDir;
$phpspec_root = realpath('../../../../PHPSpec/PHPSpec-1.0.2beta');
$mockery_root = realpath('../../../../mockery/library');


$paths = array(
	'SimDAL'=>$simdal_root,
	'PHPSpec'=>$phpspec_root,
	'Mockery'=>$mockery_root
);

set_include_path( implode( PATH_SEPARATOR, $paths ) . PATH_SEPARATOR . get_include_path() );

require_once 'SimDAL/Autoload.php';

spl_autoload_register(array('SimDAL_Autoload', 'autoload'));

$domain_dir = realpath('../TestDomain');
SimDAL_Autoload::setDomainDirectory($domain_dir);

require_once 'PHPSpec/Framework.php';
require_once 'Mockery/Loader.php';
$loader = new \Mockery\Loader();
$loader->register();

