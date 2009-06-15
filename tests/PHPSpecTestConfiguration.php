<?php

$projectDir = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' ) . DIRECTORY_SEPARATOR;

$simdal_root = $projectDir . 'library';
$phpspec_root = $projectDir . '..' . DIRECTORY_SEPARATOR . 'PHPSpec';

$paths = array(
	'SimDAL'=>$simdal_root,
	'PHPSpec'=>$phpspec_root
);

set_include_path( implode( PATH_SEPARATOR, $paths ) . PATH_SEPARATOR . get_include_path() );

require_once 'PHPSpec/Framework.php';