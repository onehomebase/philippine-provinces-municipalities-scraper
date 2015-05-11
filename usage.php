<?php
include "vendor/autoload.php";

use WarrenCA\PhilippineTowns\Parser;
use WarrenCA\PhilippineTowns\IoC;

// // Basic Usage
// // Create IoC for 'parser', you must always call this
// // before using the Parser::getArea()
// IoC::bind('parser', function(){
// 	return new Parser(new WarrenCA\PhilippineTowns\ParserHook);
// });

// Alternatively use this binding to save parse data to any data store
include "DatabaseHook.php";
IoC::bind('parser', function(){
	// You can always change DatabaseHook to any class as long as it
	// implements WarrenCA\PhilippineTowns\ParserHookInterface
	return new Parser(new DatabaseHook);
});

// Will only return list of regions
// look for build/regions-only.json
$regions = Parser::getArea("Regions");

// // Will only return list of provinces
// // look for build/provinces-only.json
// $provinces = Parser::getArea("Provinces");

// // Will only return list of municipalities
// // look for build/municipalities-only.json
// $municipalities = Parser::getArea("Municipalities");

// // Will return list of regions
// // look for build/regions-all.json
// $regions = Parser::getArea("Regions")->all();

// // Will return list of provinces with respective region
// // look for build/provinces-all.json
// $provinces = Parser::getArea("Provinces")->all();

// // Will return list of municipalities with respective province and region
// // look for build/municipalities-all.json
// $municipalities = Parser::getArea("Municipalities")->all();