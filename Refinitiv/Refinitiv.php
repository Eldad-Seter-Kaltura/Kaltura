<?php
require_once ('BetaObject.php');
require_once ('ProdObject.php');

if($argc < 4) {
	die('Correct run form: php Refinitiv.php {serviceUrl} {partnerId} {adminSecret} {categoryArray}' . "\n");
}

define('ARRAY_NAME', 'arr');
parse_str($argv[4], $output);
$categoryArray = $output[ARRAY_NAME];  //argv[4]: arr[]=categoryId1&arr[]=categoryId2 etc.

$Beta   = new BetaObject($argv[1], $argv[2], $argv[3], $categoryArray);
$client = $Beta->startClient();

$timestampSixMonthsAgo = -15552000;
$Beta->printAllEntriesGoingToBeDeleted($client, $timestampSixMonthsAgo, 'output.csv');

$Prod = new ProdObject($argv[1], $argv[2], $argv[3], $categoryArray);
$client = $Prod->startClient();

$timestampThreeYearsAgo = -93312000;
$Prod->printAllFlavorsGoingToBeDeleted($client, $timestampThreeYearsAgo, 'output.csv');
