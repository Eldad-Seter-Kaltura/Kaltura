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

	public function getEntryId($inputFilePath): array {
		$inputFile = fopen($inputFilePath, 'r');
		$res       = array();
		while($line = fgetcsv($inputFile)) {
			if(in_array("entry", $line)) {
				$entryId = $line[1];
				$res[] = $entryId;
			}

		}
		return $res;
	}

	public function getEntryIdUserId($inputFilePath): array {
		$inputFile = fopen($inputFilePath, 'r');
		$res       = array();
		while($line = fgetcsv($inputFile)) {
			if(in_array("entry", $line)) {
				$entryId = $line[1];
				$oldUser = $line[2];

				if(!$line[3]) {
					$oldUser = $line[4];
					echo 'Entry ' . $entryId . ' should be fixed!' . "\n";
				}
				if(!isset($res[$entryId])) {
					$res[$entryId] = $oldUser;
				}
			}
		}
		return $res;
	}

	public function updateEntitledUsers($inputEntryIdUserIdArray, $outputCsv) {
		$outCsv = fopen($outputCsv, 'w');

		echo 'Number of entries to update: ' . count($inputEntryIdUserIdArray) . "\n";
		foreach($inputEntryIdUserIdArray as $key => $value) {
			$trialsExceeded = 'Exceeded trials for this entry' . $key . '\n';
			try {
				$currentEntry   = $this->clientObject->doMediaGet($key, $trialsExceeded, 1);
			} catch(Exception $e) {
				echo('An error occurred getting entry ' . $key . ' : ' . $e->getMessage() . "\n" . "Skipping\n");
				continue;
			}

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

	public function removeEntitledUserFromEntries($inputEntryIdArray, $entitledUser, $outputCsv) {
		$outCsv = fopen($outputCsv, 'w');

		$currentCount         = 0;
		$totalCount           = count($inputEntryIdArray);
		$numberOfProgressBars = ($totalCount < 50) ? $totalCount : 50;
		$progressBarIncrement = ceil($totalCount / $numberOfProgressBars);
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);


		foreach($inputEntryIdArray as $entryId) {
			$currentCount++;
			if($currentCount % $progressBarIncrement == 0) {
				$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
			}

			$trialsExceeded = 'Exceeded trials for this entry' . $entryId . '\n';
			$currentEntry   = $this->clientObject->doMediaGet($entryId, $trialsExceeded, 1);

			if(isset($currentEntry) && isset($currentEntry->id) && $currentEntry->id == $entryId) {
				$updatedEntry = new KalturaMediaEntry();

				$coEditors    = explode(',', $currentEntry->entitledUsersEdit);
				$coPublishers = explode(',', $currentEntry->entitledUsersPublish);
				$coViewers = explode(',', $currentEntry->entitledUsersView);

				if(in_array($entitledUser, $coEditors)) {
					$entitledKey                     = array_search($entitledUser, $coEditors);
					$coEditors[$entitledKey]         = "";
				}
				$updatedEntry->entitledUsersEdit = implode(',', $coEditors);

				if(in_array($entitledUser, $coPublishers)) {
					$entitledKey                     = array_search($entitledUser, $coPublishers);
					$coPublishers[$entitledKey]         = "";
				}
				$updatedEntry->entitledUsersPublish = implode(',', $coPublishers);

				if(in_array($entitledUser, $coViewers)) {
					$entitledKey                     = array_search($entitledUser, $coViewers);
					$coViewers[$entitledKey]         = "";
				}
				$updatedEntry->entitledUsersView = implode(',', $coViewers);

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
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
		echo "End of entries" . "\n";
		fclose($outCsv);
	}

	private function calculateProgressBar(int $currentCount, int $progressBarIncrement, int $numberOfProgressBars, int $totalCount) {
		$progressIteration = intdiv($currentCount, $progressBarIncrement);
		$doneString        = "";
		for($j = 0; $j < $progressIteration; $j++) {
			$doneString .= "=";
		}
		$remainingString = "";
		for($j = 0; $j < $numberOfProgressBars - ($progressIteration); $j++) {
			$remainingString .= "-";
		}
		echo "\n" . $currentCount . "/" . $totalCount . "\t" . "[" . $doneString . ">" . $remainingString . "]" . "\t" . floor($currentCount / $totalCount * 100) . "%" . "\n\n";
		return;
	}

}
