<?php
require_once('ReportObject.php');


if($argc < 7) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType} {outputCsv} {inputCsv}' . "\n");
}

$jobType   = $argv[4];
$outputCsv = $argv[5];
$inputCsv  = $argv[6];

$job = new ReportObject($argv[1], $argv[2], $argv[3]);

switch($jobType) {
	case "First_report":
		echo 'Doing first report -' . "\n";
		$job->doFirstReport($outputCsv);
		break;
	case "Second_report":
		echo 'Doing second report -' . "\n";
		$job->doSecondReport($outputCsv);
		break;
	case "Third_report":
		echo 'Doing third report -' . "\n";
		$job->doThirdReport($inputCsv, $outputCsv);;
		break;
	case "Fourth_report":
		echo 'Doing fourth report -' . "\n";
		$job->doFourthReport($outputCsv);
		break;
	default:
		die('no job selected');
}

echo 'Job finished' . "\n";
