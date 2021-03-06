# SimDAL stands for Simple Domain Abstraction Library.

It is a PHP library which will help you create applications that only
has to worry about the domain and leave the persistence of objects to
the library. It enables you to create POPO (Pure xxx PHP Objects) for
the domain which makes the domain more portal if you wanted to change
the Domain Library.
***
Setup:
1. copy the SimDAL directory in the library directory into you include
path.

2. put these in your index.php or bootstrap file
		define('DOMAIN_PATH', '<path/to/domain/classes>');
		require_once 'SimDAL/Autoload.php';
		spl_autoload_register(array('SimDAL_Autoload', 'autoload'));
		SimDAL_Sesssion:factory(include('<path/to/config/file>');

3. create the generate proxy file on the path with the following:
		$mapper = SimDAL_Session::factory()->getMapper();
		SimDAL_ProxyGenerator::generateProxies($mapper, '<path/to/cachedir>');
4. create the configuration file as per the template config provided in
config.example.php

Now you are ready to use SimDAL

Usage:
Anywhere in your application you can use the SimDAL library as follows.
    $session = SimDAL_Session::factory()->getCurrentSession();
    $house = $session->load('House')->whereIdIs(1)->fetch();
    $person = $session->load('Person')->whereColumn('name')->equals('John Smith')->fetch();
    
    $house->getPersons()->add($person);
    
    $session->commit();