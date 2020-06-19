<?php
require_once('EntryDeletionObject.php');
require_once('FlavorDeletionObject.php');


if($argc < 7) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {categoryArray} {isRealRun} {action}' . "\n");
}

define('ARRAY_NAME', 'arr');
parse_str($argv[4], $output);
$categoryArray = $output[ARRAY_NAME];  //argv[4]: arr[]=categoryId1&arr[]=categoryId2 etc.

$isRealRun = $argv[5];
$action    = $argv[6];

switch($action) {
	case "entryDeletion":
		$timestampSixMonthsAgo = -15552000;
		$Beta                  = new EntryDeletionObject($argv[1], $argv[2], $argv[3], $timestampSixMonthsAgo, $categoryArray);
		if($isRealRun) {
			$Beta->deleteAllEntriesAccordingAccordingToCategories('outputEntryDeletionRealRun.csv');
		} else {
			$Beta->printAllEntriesAccordingAccordingToCategories('outputEntryDeletionDryRun.csv');
		}
		break;
	case "flavorDeletion":
		$timestampThreeYearsAgo = -94694255;
		$Prod                   = new FlavorDeletionObject($argv[1], $argv[2], $argv[3], $timestampThreeYearsAgo, $categoryArray);
		if($isRealRun) {
			$Prod->deleteAllFlavorsAccordingAccordingToCategories('outputFlavorDeletionRealRunRule5.csv', 'outputFlavorDeletionRealRunOtherRules.csv');
		} else {
			$Prod->printAllFlavorsAccordingAccordingToCategories('outputFlavorDeletionDryRunRule5.csv', 'outputFlavorDeletionDryRunOtherRules.csv');
		}
		break;
	default:
		die('Action must be one of the following: {entryDeletion, flavorDeletion}');
}
