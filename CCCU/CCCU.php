<?php
require_once('php5/KalturaClient.php');
require_once 'createNewObjectsCCCU.php';
require_once 'addMetadataToEntries.php';


if($argc < 4) {
	die('Correct run form: php CCCU.php {serviceUrl} {partnerId} {adminSecret}' . "\n");
}

$lastCreatedAt = null;
if ($argc == 5) {
	$lastCreatedAt = $argv[4];
}

$createNewObjects = new ConstructNewObjectsCCCU($argv[1], $argv[2], $argv[3]);
$metadataProfileId = $createNewObjects->getMetadataProfileId();

$metadataClass = new MetadataToEntries($argv[1], $argv[2], $argv[3], $metadataProfileId, $lastCreatedAt ? $lastCreatedAt : null);
$createdAt = $metadataClass->add();
echo "Last Created at: " . $createdAt . "\n";

die ('Adding of metadata to entries complete. Metadata Profile [' . $metadataProfileId .'] added to entries' . "\n");
