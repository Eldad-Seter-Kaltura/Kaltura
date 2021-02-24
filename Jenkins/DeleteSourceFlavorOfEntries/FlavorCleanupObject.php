<?php
require_once('EntryAndFlavorActions.php');

class FlavorCleanupObject
{

	/* @var $entryAndFlavorActions EntryAndFlavorActions */
	private $entryAndFlavorActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $timeStampCreatedAtBefore) {
		$this->entryAndFlavorActions = new EntryAndFlavorActions($serviceUrl, $partnerId, $adminSecret, $timeStampCreatedAtBefore);
	}


	public function doDryRun($outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CreatedAt'));

		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilterForDryRun();

		echo 'Printing entries created at more than a year ago- ' . "\n\n";
		$this->printEntriesAccordingToFilter($mediaEntryFilter, $pager, $outputCsv);

		fclose($outputCsv);
	}

	private function printEntriesAccordingToFilter($mediaEntryFilter, $pager, $outputCsv) {
		$firstTry              = 1;
		$message               = 'Total number of entries for rule: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$baseEntryList         = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i                    = 0;
		$currentCount         = 0;
		$totalCount           = $baseEntryList->totalCount;
		$numberOfProgressBars = ($totalCount < 50) ? $totalCount : 50;
		$progressBarIncrement = $numberOfProgressBars ? ceil($totalCount / $numberOfProgressBars) : 1;
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);

		while(count($baseEntryList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($baseEntryList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($baseEntryList->objects as $currentEntry) {
				$currentCount++;
				if($currentCount % $progressBarIncrement == 0) {
					$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
				}

				$type = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry->mediaType);

				$dataArray = array($currentEntry->id, $currentEntry->name, $type, $currentEntry->createdAt);
				fputcsv($outputCsv, $dataArray);
			}

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$baseEntryList                                 = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
		echo "Finished printing source flavor of entries." . "\n\n";
	}

//	public function deleteFlavorsOfEntriesWithMRProfile($inputEntryIdsFile) {
//
//		//0. build xml for MR: <MRPsOnEntry>MR_number</MRPsOnEntry>
//		list($metadataProfileId, $scheduledTaskProfileIdString, $xmlMRP) = $this->entryAndFlavorActions->generateXMLForMRP();
//
//		$entryIdFlavorParamsIdsArray=array();
//
//		$currentCount         = 0;
//		$totalCount           = count($entryIdFlavorParamsIdsArray);
//		$numberOfProgressBars = ($totalCount < 50) ? $totalCount : 50;
//		$progressBarIncrement = ceil($totalCount / $numberOfProgressBars);
//		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
//
//		foreach(array_keys($entryIdFlavorParamsIdsArray) as $currentEntryId) {
//			$currentCount++;
//			if($currentCount % $progressBarIncrement == 0) {
//				$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
//			}
//
//			//1. get flavor asset ids to delete
//			$flavorParamsIdsToDelete = explode(",", $entryIdFlavorParamsIdsArray[$currentEntryId]);
//			$flavorAssetIdsToDelete  = $this->entryAndFlavorActions->gettingFlavorAssetIdsToDelete($flavorParamsIdsToDelete, $currentEntryId);
//
//			$firstTry = 1;
//			echo 'Deleting flavor assets of entry ' . $currentEntryId . ":\n";
//
//			//2. delete flavor asset ids
//			foreach($flavorAssetIdsToDelete as $flavorAssetId) {
//				$successMessage        = 'Flavor asset ' . $flavorAssetId . ' was deleted' . "\n";
//				$trialsExceededMessage = 'Exceeded number of trials for this flavor ' . $flavorAssetId . '. Moving on to next flavor' . "\n\n";
//				$this->entryAndFlavorActions->clientObject->doFlavorAssetDelete($flavorAssetId, $successMessage, $trialsExceededMessage, $firstTry);
//			}
//
//			//3. mark this entry for MR
//			$successMessage        = "Metadata- " . "MRPsOnEntry: " . $scheduledTaskProfileIdString . " -added for entry " . $currentEntryId . "\n\n";
//			$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
//			$this->entryAndFlavorActions->clientObject->doMetadataAdd($metadataProfileId, $currentEntryId, $xmlMRP, $successMessage, $trialsExceededMessage, $firstTry);
//		}
//
//		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
//		echo "End of entries" . "\n";
//	}

	public function deleteSourceFlavorOfEntriesInFile($inputEntryIdsFile, $outputPathCsv) {
		$entryIdsArray = $this->entryAndFlavorActions->getFirstColumnFromCsvFile($inputEntryIdsFile);
		if(!$entryIdsArray) {
			die("Something wrong with input file!\n");
		}

		$outputCsv = fopen($outputPathCsv, 'w');

		$currentCount         = 0;
		$totalCount           = count($entryIdsArray);
		$numberOfProgressBars = ($totalCount < 50) ? $totalCount : 50;
		$progressBarIncrement = ceil($totalCount / $numberOfProgressBars);
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);

		foreach($entryIdsArray as $currentEntryId) {
			$currentCount++;
			if($currentCount % $progressBarIncrement == 0) {
				$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
			}

			//1. get flavor asset id to delete
			$flavorAssetIdToDelete = $this->entryAndFlavorActions->gettingSourceFlavorAssetIdOfEntry($currentEntryId);

			echo 'Deleting source flavor asset of entry ' . $currentEntryId . ":\n";

			//2. delete flavor asset
			if($flavorAssetIdToDelete) {
				$successMessage        = 'Flavor asset ' . $flavorAssetIdToDelete . ' was deleted' . "\n";
				$trialsExceededMessage = 'Exceeded number of trials for this source flavor ' . $flavorAssetIdToDelete . '. Moving on to next entry' . "\n\n";
				$this->entryAndFlavorActions->clientObject->doFlavorAssetDelete($flavorAssetIdToDelete, $successMessage, $trialsExceededMessage, 1);
			}

		}
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
		echo "End of entries" . "\n";
		fclose($outputCsv);
	}

	private function calculateProgressBar(int $currentCount, int $progressBarIncrement, int $numberOfProgressBars, int $totalCount) {
		$progressIteration = ceil($currentCount / $progressBarIncrement);
		if($currentCount == $totalCount) {
			$progressIteration = $numberOfProgressBars;
		}
		$doneString = "";
		for($j = 0; $j < $progressIteration; $j++) {
			$doneString .= "=";
		}
		$remainingString = "";
		for($j = 0; $j < $numberOfProgressBars - ($progressIteration); $j++) {
			$remainingString .= "-";
		}
		$percent = $totalCount ? floor($currentCount / $totalCount * 100) : 100;
		echo "\n" . $currentCount . "/" . $totalCount . "\t" . "[" . $doneString . ">" . $remainingString . "]" . "\t" . $percent . "%" . "\n\n";
		return;
	}

}
