<?php
require_once ('PopulateEndDateObject.php');

if($argc < 6) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {consoleOutputCsv}' . "\n");
}

$HCC = new PopulateEndDateObject($argv[1], $argv[2], $argv[3]);

$jobType = $argv[4];
if($jobType == "Dry_run") {
	echo 'This is Dry Run!' . "\n";
	$consoleOutputCsv = $argv[5];
	$HCC->doDryRun($consoleOutputCsv);

} else if($jobType == "Full_migration") {
	echo 'This is Full Migration!' . "\n";
} else {
	echo 'This is error!' . "\n";
}

die('Job finished' . "\n");
