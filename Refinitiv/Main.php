<?php
require_once('EntryDeletionObject.php');
require_once('FlavorDeletionObject.php');


if($argc < 8) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {categoryArray} {flavorParamsArray} {isRealRun} {action}' . "\n");
}

define('ARRAY_NAME_CATEGORY', 'arr');
parse_str($argv[4], $output);
$categoryArray = $output[ARRAY_NAME_CATEGORY];  //argv[4]: arr[]=categoryId1&arr[]=categoryId2 etc.

define('ARRAY_NAME_FLAVOR', 'b');
parse_str($argv[5], $output);
$flavorParamsIdsArray = $output[ARRAY_NAME_FLAVOR];  //argv[5]: b[]=flavorParam1&b[]=flavorParam2 etc.


$isRealRun = $argv[6];
$action    = $argv[7];

switch($action) {
	case "entryDeletion":
		$timestampSixMonthsAgo = -15552000;
		$Beta                  = new EntryDeletionObject($argv[1], $argv[2], $argv[3], $timestampSixMonthsAgo, $categoryArray);
		if($isRealRun) {
			$Beta->deleteAllEntriesAccordingAccordingToCategories('Beta.csv');
		} else {
			$Beta->printAllEntriesAccordingAccordingToCategories('outputEntryDeletionDryRun.csv');
		}
		break;
	case "flavorDeletion":
		$timestampThreeYearsAgo = -94694255;
		$Prod                   = new FlavorDeletionObject($argv[1], $argv[2], $argv[3], $timestampThreeYearsAgo, $categoryArray);
		if($isRealRun) {
//			$Prod->deleteAllFlavorsAccordingAccordingToCategories('outputFlavorDeletionRealRunRule5.csv', 'outputFlavorDeletionRealRunOtherRules.csv');
//			$flavorParamsIdsArrayRule5 = array(1248522, 487061, 1248502, 487041, 2027202);
//			$flavorParamsIdsArrayOtherRules = array(1248532, 487071, 1248502, 487041, 2027202);

			$Prod->deleteFlavorsOfEntriesFromInputFile('input.csv', $flavorParamsIdsArray, 'output.csv');
		} else {
			$Prod->printAllFlavorsAccordingAccordingToCategories('outputFlavorDeletionDryRunRule5.csv', 'outputFlavorDeletionDryRunOtherRules.csv');
		}
		break;
	default:
		die('Action must be one of the following: {entryDeletion, flavorDeletion}');
}
