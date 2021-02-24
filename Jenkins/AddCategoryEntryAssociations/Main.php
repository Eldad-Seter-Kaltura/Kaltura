<?php
require_once('EntryCategoryObject.php');


if($argc < 8) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {consoleOutputCsv} {inputCsv} {separator}' . "\n");
}

$jobType          = $argv[4];
$consoleOutputCsv = $argv[5];
$inputCsv         = $argv[6];

if($jobType != "Dry_run" && $jobType != "Smoke_test" && $jobType != "Full_run") {
	die('Bad job type! Closing' . "\n");
}

$separator = $argv[7] ? $argv[7] : ",";
if(strlen($separator) != 1) {
	die("Bad separator! Closing");
}

$job = new EntryCategoryObject($argv[1], $argv[2], $argv[3]);

echo 'This is ' . $jobType . "!\n";
if($jobType == "Dry_run") {
//	$job->doDryRun($consoleOutputCsv);
	die("Error!");
} else if($jobType == "Smoke_test" || $jobType == "Full_run") {
	$job->addCategoryEntriesFromFile($inputCsv, $separator);
}
echo 'Job finished' . "\n";
