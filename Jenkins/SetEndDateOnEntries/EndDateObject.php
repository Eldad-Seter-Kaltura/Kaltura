<?php
require_once('EntryActions.php');


class EndDateObject
{
	/* @var $entryActions EntryActions */
	private $entryActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $metadataProfileId, $timeStampEndDate, $timeStampCreatedAt) {
		$this->entryActions = new EntryActions($serviceUrl, $partnerId, $adminSecret, $metadataProfileId, $timeStampEndDate, $timeStampCreatedAt);
	}

	public function doDryRun($outputCsvPath) {
		$outputCsv = fopen($outputCsvPath, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'OwnerID', 'CreatedAt'));

		//1. get metadata profile field name & build end date xml

		$firstTry                  = 1;
		$trialsExceededMessage     = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$metadataProfileId         = $this->entryActions->getMetadataProfileId();
		$metadataProfileListFields = $this->entryActions->clientObject->doMetadataProfileListFields($metadataProfileId, $trialsExceededMessage, $firstTry);
		$metadataProfileFieldName  = $metadataProfileListFields->objects[0]->key;

		$timeStampEndDate = $this->entryActions->getTimeStampEndDate();
		$xmlEndDate       = "<metadata>" . "<" . $metadataProfileFieldName . ">" . $timeStampEndDate . "</" . $metadataProfileFieldName . ">" . "</metadata>";
		echo 'End date to be added: ' . $xmlEndDate . "\n";

//		//2. print all entries affected
//
//		list($pager, $mediaEntryFilter) = $this->entryActions->definePagerAndFilter();
//
//		$firstTry              = 1;
//		$message               = 'Total number of entries: ';
//		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
//		$mediaList             = $this->entryActions->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

//		$i = 0;
//		while(count($mediaList->objects)) {
//			echo "Beginning of page: " . ++$i . "\n";
//			echo "Count: " . count($mediaList->objects) . "\n\n";
//
//			/* @var $currentEntry KalturaMediaEntry */
//			foreach($mediaList->objects as $currentEntry) {
//				$currentEntryMediaType = $this->entryActions->gettingTypeOfEntry($currentEntry);
//				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $currentEntryMediaType, $currentEntry->userId, $currentEntry->createdAt));
//			}
//
//			echo "Last entry: " . $currentEntry->id . "\n";
//			echo "End of page\n\n";
//
//			//media . list - next iterations
//			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
//			$mediaList                                     = $this->entryActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
//		}

		fclose($outputCsv);
		echo 'End of dry run' . "\n";
	}

}
