<?php
require_once('EntryAndFlavorActions.php');

class MRPruningObject
{

	/* @var $entryAndFlavorActions EntryAndFlavorActions */
	private $entryAndFlavorActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $mrProfileId, $mrMetadataSearch, $xslFilePath, $flavorParamsIdsArray, $timeStampCreatedAtBefore, $timeStampLastPlayedAtLessThanEqualOrNull) {
		$this->entryAndFlavorActions = new EntryAndFlavorActions($serviceUrl, $partnerId, $adminSecret, $mrProfileId, $mrMetadataSearch, $xslFilePath, $flavorParamsIdsArray, $timeStampCreatedAtBefore, $timeStampLastPlayedAtLessThanEqualOrNull);
	}

	public function doDryRun($outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'FlavorIdsToDelete', 'CreatedAt', 'LastPlayedAt'));

		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilterForDryRun();

		$flavorsToKeep    = $this->entryAndFlavorActions->getFlavorParamsIdsArray();
		$mrPruningProfile = $this->entryAndFlavorActions->getMRProfileId();
		$mrMetadataArray = $this->entryAndFlavorActions->getMRMetadataValues();

		echo 'Printing flavors to be deleted of entries with MR Pruning profile- ' . $mrPruningProfile . '- and Retirement Policy- "Prune"' . ":\n\n";
		$this->printFlavorsToBeDeletedOfEntriesWithMRPruningProfile($mrPruningProfile, $mrMetadataArray, $flavorsToKeep, $mediaEntryFilter, $pager, $outputCsv);
		fclose($outputCsv);
	}

	private function printFlavorsToBeDeletedOfEntriesWithMRPruningProfile($mrPruningProfile, $mrMetadataArray, $flavorsToKeep, $mediaEntryFilter, $pager, $outputCsv) {
		$metadataFilter                          = new KalturaMetadataFilter();
		$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
		$metadataFilter->metadataProfileIdEqual  = $mrPruningProfile;

		$firstTry              = 1;
		$message               = 'Total number of entries for dry run: ';
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

				//TODO: metadata.list
				$metadataFilter->objectIdEqual = $currentEntry->id;
				$metadataList                  = $this->entryAndFlavorActions->clientObject->doMetadataList($metadataFilter, $trialsExceededMessage, $firstTry);
				if($metadataList->totalCount) {
					/* @var $metadataObject KalturaMetadata */
					$metadataObject = $metadataList->objects[0];
					$xml              = $metadataObject->xml;

					$doc = new DOMDocument();
					$doc->loadXML($xml);


					//TODO: RetirementPolicy = 'Prune' , 'Pruned' = false
					/* @var $node DOMNode */
					$arrayKey = "";
					$arrayValue = "";
					$fieldNotMatch = FALSE;
					foreach($mrMetadataArray as $key => $value) {
						$arrayKey = $key;
						$arrayValue = $value;
						$node = $doc->getElementsByTagName($key)->item(0);
						if(!$node || isset($node->nodeValue) && $node->nodeValue != $value) {
							$fieldNotMatch = TRUE;
							break;
						}
					}

					if($fieldNotMatch) {
						echo "Entry " . $currentEntry->id . " doesn't have metadata " . $arrayKey . ": " . $arrayValue . ". Skipping\n";
						continue;
					}

					$type                    = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry->mediaType);
					$flavorParamsIdsToDelete = $this->entryAndFlavorActions->gettingFlavorParamIdsToDelete($flavorsToKeep, explode(",", $currentEntry->flavorParamsIds));
					$dataArray = array($currentEntry->id, $currentEntry->name, $type, implode(",", $flavorParamsIdsToDelete), $currentEntry->createdAt, $currentEntry->lastPlayedAt);
					fputcsv($outputCsv, $dataArray);
				}

			}
			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$baseEntryList                                 = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
		echo "Finished printing entries of dry run." . "\n\n";
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
