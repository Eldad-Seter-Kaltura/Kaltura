<?php
require_once ('PopulateEndDateObject.php');

if($argc < 5) {
	die('Correct run form: php Main.php {serviceUrl} {partnerId} {adminSecret} {jobType}' . "\n");
}

$HCC = new PopulateEndDateObject($argv[1], $argv[2], $argv[3]);

if($argv[4] == "Dry_run") {
	echo "This is Dry Run!\n";
} else if($argv[4] == "Full_migration") {
	echo "This is Full Migration!\n";
} else {
	echo "This is error!\n";
}

die("Job finished");
