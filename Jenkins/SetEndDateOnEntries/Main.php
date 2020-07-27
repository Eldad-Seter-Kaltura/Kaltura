<?php
require_once('EndDateObject.php');

if($argc < 11) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {consoleOutputCsv} {inputCsv} {metadataProfileId} {$metadataProfileFieldName} {timeStampEndDate} {timeStampCreatedAt}' . "\n");
}

$endDateObject = new EndDateObject($argv[1], $argv[2], $argv[3], $argv[7], $argv[8], $argv[9], $argv[10]);

$jobType = $argv[4];
if($jobType == "Dry_run") {
	echo 'This is Dry Run!' . "\n";
	$consoleOutputCsv = $argv[5];
	$endDateObject->doDryRun($consoleOutputCsv);

} else {
	if($jobType == "Full_migration") {
		echo 'This is Full Migration!' . "\n";
		$inputCsv = $argv[6];
		$endDateObject->setEndDateOnEntriesFromInputFile($inputCsv);

	} else {
		die('This is error!' . "\n");
	}
}

die('Job finished' . "\n");
