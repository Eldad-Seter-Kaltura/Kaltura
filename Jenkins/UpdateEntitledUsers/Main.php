<?php
require_once ('UserObject.php');

if($argc < 6) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {inputCsv} {outputCsv}' . "\n");
}

$userObject = new UserObject($argv[1], $argv[2], $argv[3]);

$inputCsv  = $argv[4];
$outputCsv = $argv[5];

//$entryIdUserIdArray = $userObject->getEntryIdUserId($inputCsv);
//$userObject->updateEntitledUsers($entryIdUserIdArray, $outputCsv);
$entryIdArray = $userObject->getEntryId($inputCsv);
$userObject->removeEntitledUserFromEntries($entryIdArray, "lblan3@unh.newhaven.edu", $outputCsv);
echo "Finished\n";
