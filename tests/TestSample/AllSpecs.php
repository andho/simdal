<?php

require_once '../PHPSpecTestConfiguration.php';

require_once 'PHPSpec/Framework.php';

$options = new stdClass();
$options->recursive = true;
$options->specdocs = true;
$options->reporter = 'html';

PHPSpec_Runner::run($options);