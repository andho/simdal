<?php

require_once 'init.php';

$mapper = SimDAL_Session::factory()->getMapper();
SimDAL_ProxyGenerator::generateProxies($mapper, DOMAIN_PATH . DIRECTORY_SEPARATOR . 'cache');