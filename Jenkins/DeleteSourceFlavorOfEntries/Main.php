<?php
require_once('FlavorCleanupObject.php');


if($argc < 8) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {consoleOutputCsv} {inputCsv} {timeStampCreatedAtBefore}' . "\n");
}

$jobType          = $argv[4];
$consoleOutputCsv = $argv[5];
$inputCsv         = $argv[6];

if($jobType != "Dry_run" && $jobType != "Smoke_test" && $jobType != "Full_run") {
	die('Error in job type! Closing' . "\n");
}

$timeStampCreatedAtBefore = $argv[7];

$job = new FlavorCleanupObject($argv[1], $argv[2], $argv[3], $timeStampCreatedAtBefore);

echo 'This is ' . $jobType . "!\n";
if($jobType == "Dry_run") {
	$job->doDryRun($consoleOutputCsv);
} else {
	$job->deleteSourceFlavorOfEntriesInFile($inputCsv);
}
echo 'Job finished' . "\n";
