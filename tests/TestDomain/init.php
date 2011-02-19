<?php
$domain_path = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'TestDomain');
define ( 'DOMAIN_PATH', $domain_path );

if (!isset($paths)) {
	$paths = array('curr_path' => get_include_path());
	$autoload = true;
}

if (!isset($dir)) {
	$paths['library'] = realpath(DOMAIN_PATH .DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'library');
	$paths['domain'] = DOMAIN_PATH;
	$paths = array_reverse($paths, true);
	set_include_path(implode(PATH_SEPARATOR, $paths));
}

if ($autoload) {
	require_once 'SimDAL/Autoload.php';
	SimDAL_Autoload::setDomainDirectory(DOMAIN_PATH);
	spl_autoload_register(array('SimDAL_Autoload', 'autoload'));
}

SimDAL_Session::factory(include 'config.php');