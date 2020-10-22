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
	case "Entitled_users_entry":
		echo 'Doing entitled users report -' . "\n";
		$job->doEntitledUsersEntryOwnerReport($outputCsv);
		break;
	case "Category_entry":
		echo 'Doing category entry report -' . "\n";
		$job->doCategoryEntryReport($outputCsv);
		break;
	case "Category_user":
		echo 'Doing category user report -' . "\n";
		$job->doCategoryUserPermissionsReport($inputCsv, $outputCsv);;
		break;
	case "Category_list":
		echo 'Doing category list report -' . "\n";
		$job->doCategoryListReport($outputCsv);
		break;
	case "Entry_lastplayedat_categories":
		echo 'Doing entry last played at categories report -' . "\n";
		$job->doEntryLastPlayedAtCategories($outputCsv);
	default:
		die('no job selected');
}

echo 'Job finished' . "\n";
