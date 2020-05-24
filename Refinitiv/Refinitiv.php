<?php
require_once ('BetaObject.php');
require_once ('ProdObject.php');

if($argc < 4) {
	die('Correct run form: php Refinitiv.php {serviceUrl} {partnerId} {adminSecret} {categoryArray}' . "\n");
}

define('ARRAY_NAME', 'arr');
parse_str($argv[4], $output);
$categoryArray = $output[ARRAY_NAME];  //argv[4]: arr[]=categoryId1&arr[]=categoryId2 etc.

$clientObject = new ClientObject($argv[1], $argv[2], $argv[3]);
$clientObject->startClient();
$isRealRun = false;

$timestampSixMonthsAgo = -15552000;
$Beta   = new BetaObject($timestampSixMonthsAgo, $categoryArray);
$Beta->deleteAllEntriesAccordingAccordingToCategories($isRealRun, $clientObject, $timestampSixMonthsAgo, 'output.csv');

$timestampThreeYearsAgo = -93312000;
$Prod = new ProdObject($timestampThreeYearsAgo, $categoryArray);
$Prod->deleteAllFlavorsAccordingAccordingToCategories($isRealRun, $clientObject, $timestampThreeYearsAgo, 'output.csv');
