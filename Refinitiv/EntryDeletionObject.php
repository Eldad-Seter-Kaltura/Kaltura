<?php
require_once ('EntryAndFlavorActions.php');

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
		$mediaList = $this->entryAndFlavorActions->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($mediaList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($mediaList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {

				$type = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry);

				$categoriesFullNameString = $this->entryAndFlavorActions->gettingCategoryNamesOfEntry($currentEntry);

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $categoriesFullNameString, $currentEntry->createdAt, $currentEntry->updatedAt, $currentEntry->lastPlayedAt));

				//now we're ready to delete
				$successMessage = 'Entry ' . $currentEntry->id . ' was deleted' . "\n";
				$trialsExceededMessage = 'Exceeded number of trials for this entry ' . $currentEntry->id . '. Moving on to next entry' . "\n\n";
				$this->entryAndFlavorActions->clientObject->doMediaDelete($currentEntry->id, $successMessage, $trialsExceededMessage, $firstTry);

			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$mediaList                                     = $this->entryAndFlavorActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
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
		$mediaList = $this->entryAndFlavorActions->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($mediaList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($mediaList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {

				$type = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry);

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $currentEntry->createdAt, $currentEntry->updatedAt, $currentEntry->lastPlayedAt));
			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$mediaList                                     = $this->entryAndFlavorActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

	}

}
