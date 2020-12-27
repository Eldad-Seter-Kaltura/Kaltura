<?php
require_once ('SMUObject.php');

if($argc < 8) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {inputUsersCsv} {inputEntriesCsv} {consoleOutputCsv}' . "\n");
}

$jobType          = $argv[4];
$inputUsersCsv    = $argv[5];
$inputEntriesCsv  = $argv[6];
$consoleOutputCsv = $argv[7];

if($jobType != "Dry_run" && $jobType != "Smoke_test" && $jobType != "Full_migration") {
	die('Error in job type! Closing' . "\n");
}

$smu = new SMUObject($argv[1], $argv[2], $argv[3]);

$formatXml = "<metadata><Detail><Key>InstanceId</Key><Value></Value></Detail></metadata>";
echo 'Format xml is: ' . $formatXml . "\n" .
	'Value to be inserted is: ' . '1057192 or 505521' . "\n" .
	'Target metadata profile ID is: ' . '5794461' . "\n\n";

echo 'This is ' . $jobType . "!\n\n";
if($jobType == "Dry_run") {
	$arrayLMSUsers = $smu->getFirstColumnFromCsvFile($inputUsersCsv);
	$smu->doDryRun($arrayLMSUsers, $consoleOutputCsv);

} else {
	$smu->markLegacyContent($inputEntriesCsv, $formatXml);
}

echo 'Job finished' . "\n";
