<?php

define ( 'DOMAIN_PATH', dirname ( __FILE__ ) );

$dir = dirname ( realpath ( __FILE__ ) ) . DIRECTORY_SEPARATOR;
$libs = $dir . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR;

$paths = array(
	'domain' => $dir,
	'libraries' => $libs
);

set_include_path(implode(PATH_SEPARATOR, $paths) . PATH_SEPARATOR . get_include_path());

require_once 'SimDAL/Autoload.php';
spl_autoload_register(array('SimDAL_Autoload', 'autoload'));

SimDAL_Session::factory(include'config.php');