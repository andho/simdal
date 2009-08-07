<?php

require_once '../PHPSpecTestConfiguration.php';

$options = new stdClass();
$options->recursive = true;
$options->specdocs = true;
$options->reporter = 'html';

PHPSpec_Runner::run($options);