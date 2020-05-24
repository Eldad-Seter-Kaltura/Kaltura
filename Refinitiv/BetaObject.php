<?php
require_once ('ClientObject.php');

class BetaObject
{
	private $timeStamp;

	private $categoryArray;

	public function __construct($m_timeStamp, $m_categoryArray) {
		$this->timeStamp = $m_timeStamp;
		$this->categoryArray = $m_categoryArray;
	}

	public function deleteAllEntriesAccordingAccordingToCategories($isRealRun, $clientObject, $timeStamp, $outputPathCsv) {

		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->mediaTypeIn = KalturaMediaType::VIDEO . "," . KalturaMediaType::IMAGE . "," . KalturaMediaType::AUDIO;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		$mediaEntryFilter->createdAtLessThanOrEqual          = $timeStamp;
		$mediaEntryFilter->updatedAtLessThanOrEqual          = $timeStamp;
		$mediaEntryFilter->lastPlayedAtLessThanOrEqualOrNull = $timeStamp;


		$mediaEntryFilter->categoriesIdsNotContains = implode(',', $this->categoryArray);

		/* @var $clientObject ClientObject */

		$message   = 'Total number of entries: ';
		$mediaList = $clientObject->doMediaList($mediaEntryFilter, $pager, $message, 1, $outputCsv);


		while(count($mediaList->objects)) {

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {

				$type = $this->gettingTypeOfEntry($currentEntry);

				$categoriesFullNameString = $this->gettingCategoryNamesOfEntry($currentEntry, $clientObject, $outputCsv);

				//now we're ready to delete
				if($isRealRun) {
					$clientObject->doMediaDelete($currentEntry->id, 1);
				}

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $categoriesFullNameString, $currentEntry->createdAt, $currentEntry->updatedAt, $currentEntry->lastPlayedAt));
			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$mediaList = $clientObject->doMediaList($mediaEntryFilter, $pager, $message, 1, $outputCsv);
		}

	}

	private function gettingTypeOfEntry(KalturaMediaEntry $currentEntry) {
		$type = $currentEntry->mediaType;
		switch($type) {
			case KalturaMediaType::VIDEO:
				$type = "VIDEO";
				break;
			case KalturaMediaType::IMAGE:
				$type = "IMAGE";
				break;
			case KalturaMediaType::AUDIO:
				$type = "AUDIO";
				break;
			default:
				$type = "OTHER";
				break;
		}
		return $type;
	}

	private function gettingCategoryNamesOfEntry(KalturaMediaEntry $currentEntry, ClientObject $clientObject, $outputCsv) {
		$categoryEntryFilter               = new KalturaCategoryEntryFilter();
		$categoryEntryFilter->entryIdEqual = $currentEntry->id;
		$categoryEntryList                 = $clientObject->doCategoryEntryList($categoryEntryFilter, 1, $outputCsv);   //TODO: can return multiple results (categories)

		$categoriesOfEntry = array();
		if(count($categoryEntryList->objects)) {
			foreach($categoryEntryList->objects as $categoryEntry) {
				/* @var $categoryEntry KalturaCategoryEntry */
				$categoriesOfEntry[] = $categoryEntry->categoryId;
			}
		}

		$categoriesFullName = array();
		foreach($categoriesOfEntry as $categoryId) {
			$category = $clientObject->doCategoryGet($categoryId, 1);
			if($category && $category->id == $categoryId) {
				$categoriesFullName[] = $category->fullName;
			}
		}
		return implode(",", $categoriesFullName);
	}
}
