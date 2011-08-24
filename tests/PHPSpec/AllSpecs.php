<?php

ini_set('display_errors', 1);
error_reporting(E_ALL|E_STRICT);

$dir = '/home/likewise-open/ALLIEDINSURE/amjad/src/phpspec/src/';

require_once $dir . 'PHPSpec/Mocks/Functions.php';
require_once $dir . 'PHPSpec/Loader/UniversalClassLoader.php';
$loader = new \PHPSpec\Loader\UniversalClassLoader();
$loader->registerNamespace('PHPSpec', $dir);
$loader->register();
unset($dir);


/*$mockery_path = '/home/likewise-open/ALLIEDINSURE/amjad/Projects/mockery/library/';
set_include_path(get_include_path() . PATH_SEPARATOR . $mockery_path);
require_once 'Mockery/Loader.php';
$loader = new \Mockery\Loader();
$loader->register();*/

$simdal_path = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../' ) . DIRECTORY_SEPARATOR;
set_include_path(get_include_path() . PATH_SEPARATOR . $simdal_path);
require_once 'SimDAL/Autoload.php';
spl_autoload_register(array('SimDAL_Autoload', 'autoload'));
$domain_dir = realpath('../TestDomain');
SimDAL_Autoload::setDomainDirectory($domain_dir);

$testdir = './';

$phpspec = new \PHPSpec\PHPSpec(array($testdir, '-f', 'h'));
$phpspec->execute();