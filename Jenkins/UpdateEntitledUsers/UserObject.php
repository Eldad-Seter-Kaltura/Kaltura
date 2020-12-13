<?php
require_once('ClientObject.php');


class UserObject
{
	/* @var $clientObject ClientObject */
	private $clientObject;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$clientObject->startClient();
		$this->clientObject = $clientObject;
	}

	public function getEntryIdUserId($inputFilePath): array {
		$inputFile = fopen($inputFilePath, 'r');
		$res       = array();
		while($line = fgetcsv($inputFile)) {
			if(in_array("entry", $line)) {
				$entryId = $line[1];
				$oldUser = $line[2];

				if(!isset($res[$entryId])) {
					$res[$entryId] = $oldUser;
				}
			}
		}
		return $res;
	}

	public function updateEntitledUsers($inputEntryIdUserIdArray, $outputCsv) {
		$outCsv = fopen($outputCsv, 'w');

		foreach($inputEntryIdUserIdArray as $key => $value) {
			$trialsExceeded = 'Exceeded trials for this entry' . $key . '\n';
			$currentEntry   = $this->clientObject->doMediaGet($key, $trialsExceeded, 1);

			if(isset($currentEntry) && isset($currentEntry->id) && $currentEntry->id == $key) {
				$updatedEntry = new KalturaMediaEntry();

				$currentUserId = $currentEntry->userId;

				$coEditors    = explode(',', $currentEntry->entitledUsersEdit);
				$coPublishers = explode(',', $currentEntry->entitledUsersPublish);

				if(in_array($currentUserId, $coEditors)) {
					$entitledKey                     = array_search($currentUserId, $coEditors);
					$coEditors[$entitledKey]         = $value;
				} else {
					$coEditors[] = $value;
				}
				$updatedEntry->entitledUsersEdit = implode(',', $coEditors);

				if(in_array($currentUserId, $coPublishers)) {
					$entitledKey                        = array_search($currentUserId, $coPublishers);
					$coPublishers[$entitledKey]         = $value;
				} else {
					$coPublishers[] = $value;
				}
				$updatedEntry->entitledUsersPublish = implode(',', $coPublishers);


				$dataArray = array('entry', $currentEntry->id, $currentEntry->userId, $updatedEntry->userId,
					$currentEntry->entitledUsersPublish, $updatedEntry->entitledUsersPublish,
					$currentEntry->entitledUsersEdit, $updatedEntry->entitledUsersEdit,
					$currentEntry->entitledUsersView, $updatedEntry->entitledUsersView);
				$success = 'Entry update succeeded: ' . $currentEntry->id . "\n";

				try {
					$this->clientObject->doMediaUpdate($currentEntry->id, $updatedEntry, $success, $dataArray, $outCsv, $trialsExceeded, 1);

				} catch(Exception $e) {
					echo('An error occurred updating entry ' . $currentEntry->id . ' : ' . $e->getMessage());
				}

			}

		}

		fclose($outCsv);
	}
}
