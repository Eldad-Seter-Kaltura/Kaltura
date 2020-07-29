<?php
require_once('EndDateObject.php');

if($argc < 12) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {consoleOutputCsv} {inputCsv} {metadataProfileId} {metadataProfileFieldName} {metadataProfileFieldValue} {createdAtBeforeString} {createdAtAfterString}' . "\n");
}

$endDateObject = new EndDateObject($argv[1], $argv[2], $argv[3], $argv[7], $argv[8], $argv[9], $argv[10], $argv[11]);

$jobType          = $argv[4];
$consoleOutputCsv = $argv[5];

if($jobType == "Dry_run") {
	echo 'This is Dry Run!' . "\n";
	$endDateObject->doDryRun($consoleOutputCsv);

} else {
	if($jobType == "Full_migration") {
		echo 'This is Full Migration!' . "\n";
		$inputCsv = $argv[6];
		$endDateObject->setEndDateOnEntriesFromInputFile($inputCsv, $consoleOutputCsv);

	} else {
		die('This is error in job type!' . "\n");
	}
}

die('Job finished' . "\n");
