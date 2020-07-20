<?php
require_once('EntryAndFlavorActions.php');

class EntryDeletionObject
{
	private EntryAndFlavorActions $entryAndFlavorActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_timeStamp, $m_categoryArray) {
		$this->entryAndFlavorActions = new EntryAndFlavorActions($serviceUrl, $partnerId, $adminSecret, $m_timeStamp, $m_categoryArray);
	}


	public function deleteAllEntriesAccordingAccordingToCategories($outputPathCsv) {

		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilter();
		$mediaEntryFilter->categoriesIdsNotContains = implode(',', $this->entryAndFlavorActions->getCategoryArray());

		$firstTry              = 1;
		$message               = 'Total number of entries: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$baseEntryList         = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($baseEntryList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($baseEntryList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($baseEntryList->objects as $currentEntry) {

				$type = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry);

				$categoriesFullNameString = $this->entryAndFlavorActions->gettingCategoryNamesOfEntry($currentEntry);

				//saving data to print..
				$dataArray = array($currentEntry->id, $currentEntry->name, $type, $currentEntry->userId, $categoriesFullNameString, $currentEntry->createdAt, $currentEntry->lastPlayedAt);

				//now we're ready to delete
				$successMessage        = 'Entry ' . $currentEntry->id . ' was deleted' . "\n";
				$trialsExceededMessage = 'Exceeded number of trials for this entry ' . $currentEntry->id . '. Moving on to next entry' . "\n\n";
				$this->entryAndFlavorActions->clientObject->doMediaDelete($currentEntry->id, $successMessage, $outputCsv, $dataArray, $trialsExceededMessage, $firstTry);
			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$baseEntryList                                 = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

	}

	public function printAllEntriesAccordingAccordingToCategories($outputPathCsv) {

		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilter();
		$mediaEntryFilter->categoriesIdsNotContains = implode(',', $this->entryAndFlavorActions->getCategoryArray());

		$firstTry              = 1;
		$message               = 'Total number of entries: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$baseEntryList         = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($baseEntryList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($baseEntryList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($baseEntryList->objects as $currentEntry) {

				$type = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry);

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $currentEntry->createdAt, $currentEntry->updatedAt, $currentEntry->lastPlayedAt));
			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$baseEntryList                                 = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}
	}

	public function deleteEntriesFromInputFile($inputEntryIdsFile, $outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'FlavorNames', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		//0. get all entry ids from file
		$entryIdsArray = $this->entryAndFlavorActions->getAllEntryIdsFromFile($inputEntryIdsFile);

		//0a. do media list on those entries
		$entryIds500       = array_slice($entryIdsArray, 0, 500);
		$entryIds500String = implode(",", $entryIds500);

		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilterForInputFile($entryIds500String);

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$mediaList             = $this->entryAndFlavorActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($mediaList->objects)) {
			echo "Beginning of page: " . ++$i . "\n";
			echo "Count: " . count($mediaList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {

				//1. get entry info and save it to print
				$type = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry);
				$categoriesFullNameString = $this->entryAndFlavorActions->gettingCategoryNamesOfEntry($currentEntry);
				$dataArray = array($currentEntry->id, $currentEntry->name, $type, $currentEntry->userId, $categoriesFullNameString, $currentEntry->createdAt, $currentEntry->lastPlayedAt);

				//2. delete entry
				$successMessage        = 'Entry ' . $currentEntry->id . ' was deleted' . "\n";
				$trialsExceededMessage = 'Exceeded number of trials for this entry ' . $currentEntry->id . '. Moving on to next entry' . "\n\n";
				$this->entryAndFlavorActions->clientObject->doMediaDelete($currentEntry->id, $successMessage, $outputCsv, $dataArray, $trialsExceededMessage, $firstTry);
			}

			echo "Last entry: " . $currentEntry->id . "\n";
			echo "End of page\n\n";

			//media . list - next page (iteration)
			$entryIds500        = array_slice($entryIdsArray, $i * 500, 500);
			$entryIds500String  = implode(",", $entryIds500);
			if($entryIds500String == "") {
				echo "End of entries" . "\n";
				break;
			}

			$mediaEntryFilter->idIn = $entryIds500String;
			$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$mediaList             = $this->entryAndFlavorActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}
		echo "End of function\n";
	}

}
