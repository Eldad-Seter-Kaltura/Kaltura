<?php
require_once('EndDateObject.php');

if($argc < 9) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {consoleOutputCsv} {metadataProfileId} {timeStampEndDate} {timeStampCreatedAt}' . "\n");
}

$endDateObject = new EndDateObject($argv[1], $argv[2], $argv[3], $argv[6], $argv[7], $argv[8]);

$jobType = $argv[4];
if($jobType == "Dry_run") {
	echo 'This is Dry Run!' . "\n";
	$consoleOutputCsv = $argv[5];
	$endDateObject->doDryRun($consoleOutputCsv);

} else {
	if($jobType == "Full_migration") {
		echo 'This is Full Migration!' . "\n";

	} else {
		die('This is error!' . "\n");
	}
}

die('Job finished' . "\n");
