<?php
require_once('EntryActions.php');


class EndDateObject
{
	/* @var $entryActions EntryActions */
	private $entryActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $metadataProfileId, $metadataProfileFieldName, $metadataProfileFieldValue, $createdAtBeforeString, $createdAtAfterString) {
		$this->entryActions = new EntryActions($serviceUrl, $partnerId, $adminSecret, $metadataProfileId, $metadataProfileFieldName, $metadataProfileFieldValue, $createdAtBeforeString, $createdAtAfterString);
	}



	public function doDryRun($outputCsvPath) {
		$outputCsv = fopen($outputCsvPath, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'OwnerID', 'CreatedAt'));

		//1. get metadata profile field name & build end date xml
		list($metadataProfileId, $metadataProfileFieldName, $timeStampEndDate, $xmlEndDate) = $this->entryActions->getMetadataProfileFieldNameAndBuildEndDateXml();

		//2. print all entries affected

		list($pager, $mediaEntryFilter) = $this->entryActions->definePagerAndFilter();

		$firstTry              = 1;
		$message               = 'Total number of entries: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$mediaList             = $this->entryActions->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($mediaList->objects)) {
			echo "Beginning of page: " . ++$i . "\n";
			echo "Count: " . count($mediaList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {
				$currentEntryMediaType = $this->entryActions->gettingTypeOfEntry($currentEntry);
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $currentEntryMediaType, $currentEntry->userId, $currentEntry->createdAt));
			}

			echo "Last entry of page: " . $currentEntry->id . "\n";
			echo "End of page" . "\n\n";

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$mediaList                                     = $this->entryActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		fclose($outputCsv);
	}

	public function setEndDateOnEntriesFromInputFile($inputEntryIdsFile, $consoleOutputFile) {
		$consoleOutputHandle = fopen($consoleOutputFile, 'w');

		//0. get metadata profile id, field name & build end date xml
		list($metadataProfileId, $metadataProfileFieldName, $timeStampEndDate, $xmlEndDate) = $this->entryActions->getMetadataProfileFieldNameAndBuildEndDateXml();

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";

		//1. for each entry, do metadata.add
		$inputEntryIdsHandle = fopen($inputEntryIdsFile, 'r');
		fgetcsv($inputEntryIdsHandle);  //entry.id
		while($line = fgetcsv($inputEntryIdsHandle)) {
			$entryId = $line[0];

			$successMessage = "Metadata- " . $metadataProfileFieldName . ": " . $timeStampEndDate . " -added for entry " . $entryId . "\n";
			$this->entryActions->clientObject->doMetadataAdd($metadataProfileId, $entryId, $xmlEndDate, $successMessage, $consoleOutputHandle, $trialsExceededMessage, $firstTry);
		}

		fclose($inputEntryIdsHandle);
		fclose($consoleOutputHandle);
	}

}
