<?php
require_once('MRPruningObject.php');


if($argc < 12) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {consoleOutputCsv} {inputCsv} {mrProfileId} {mrMetadataSearch} {flavorParamsArray} {timeStampCreatedAtBefore} {timeStampLastPlayedAtLessThanEqualOrNull}' . "\n");
}

$jobType          = $argv[4];
$consoleOutputCsv = $argv[5];
$inputCsv         = $argv[6];

if($jobType != "Dry_run" && $jobType != "Smoke_test" && $jobType != "Full_run") {
	die('Error in job type! Closing' . "\n");
}

$mrProfileId  = $argv[7];
$mrMetadataSearch = $argv[8];

$flavorParamsIdsString = $argv[9];
$flavorParamsIdsArray = explode(",", $flavorParamsIdsString);
if($jobType == "Dry_run" && !$flavorParamsIdsArray) {
	die('Error in flavor params array! Closing' . "\n");
}

$timeStampCreatedAtBefore = $argv[10];
$timeStampLastPlayedAtLessThanEqualOrNull = $argv[11];

$xslFilePath = 'accenture.xsl';

$job = new MRPruningObject($argv[1], $argv[2], $argv[3], $mrProfileId, $mrMetadataSearch, $xslFilePath, $flavorParamsIdsArray, $timeStampCreatedAtBefore, $timeStampLastPlayedAtLessThanEqualOrNull);

echo 'This is ' . $jobType . "!\n";
if($jobType == "Dry_run") {
	$job->doDryRun($consoleOutputCsv);
} else {
	die();
}

echo 'Job finished' . "\n";
