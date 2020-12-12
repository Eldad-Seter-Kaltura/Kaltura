<?php
require_once('ClientObject.php');


class Actions
{
	/* @var $clientObject ClientObject */
	public $clientObject;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClientOrRefreshKsIfNeeded("start");
	}

	public function definePagerAndFilter($type) {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$filter = null;
		switch($type) {
			case "baseEntryFilter":
				$filter              = new KalturaBaseEntryFilter();
				$filter->orderBy     = KalturaBaseEntryOrderBy::CREATED_AT_ASC;
				break;
			case "mediaEntryFilter":
				$filter          = new KalturaMediaEntryFilter();
				$filter->orderBy = KalturaMediaEntryOrderBy::CREATED_AT_ASC;
				break;
			case "categoryEntryFilter":
				$filter = new KalturaCategoryEntryFilter();
				$filter->orderBy = KalturaCategoryEntryOrderBy::CREATED_AT_ASC;
				break;
			case "categoryUserFilter":
				$filter = new KalturaCategoryUserFilter();
				break;
			case "categoryFilter":
				$filter = new KalturaCategoryFilter();
				$filter->orderBy = KalturaCategoryOrderBy::CREATED_AT_ASC;
				break;
		}


		return array($pager, $filter);
	}

	public function getAllIdsFromFile($inputIdsFile): array {
		$idsArray       = array();
		$inputIdsHandle = fopen($inputIdsFile, 'r');
		fgetcsv($inputIdsHandle);  //column header
		while($line = fgetcsv($inputIdsHandle)) {
			$idsArray [] = $line[0];
		}
		fclose($inputIdsHandle);
		return $idsArray;
	}

	public function printingTypeOfEntry($mediaType) {
		switch($mediaType) {
			case KalturaMediaType::VIDEO:
				$type = "VIDEO";
				break;
			case KalturaMediaType::IMAGE:
				$type = "IMAGE";
				break;
			case KalturaMediaType::AUDIO:
				$type = "AUDIO";
				break;
			case KalturaMediaType::LIVE_STREAM_FLASH;
			case KalturaMediaType::LIVE_STREAM_WINDOWS_MEDIA;
			case KalturaMediaType::LIVE_STREAM_REAL_MEDIA;
			case KalturaMediaType::LIVE_STREAM_QUICKTIME;
				$type = "LIVE_STREAM";
				break;
			default:
				$type = "OTHER";
				break;
		}
		return $type;
	}

	public function gettingCategoryFullNamesOfEntry($entryId): array {
		$categoryIdsOfEntry = $this->gettingCategoryIdsOfEntry($entryId);

		$categoryFullNamesOfEntry = array();
		foreach($categoryIdsOfEntry as $categoryId) {
			$firstTry = 1;
			$trialsExceededMessage = 'Exceeded number of trials for category ' . $categoryId . '. Moving on to next category' . "\n\n";
			$category              = $this->clientObject->doCategoryGet($categoryId, $trialsExceededMessage, $firstTry);

			if($category) {
				$categoryFullNamesOfEntry[] = $category->fullName;
			}
			else {
				echo 'Could not get full name of category ' . $categoryId . ' that entry ' . $entryId . ' belongs to ' . "\n\n";
			}

		}
		return $categoryFullNamesOfEntry;
	}

	public function gettingCategoryIdsOfEntry($entryId): array {
		$categoryEntryFilter               = new KalturaCategoryEntryFilter();
		$categoryEntryFilter->entryIdEqual = $entryId;

		$pager = new KalturaFilterPager();

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
		$categoryEntryList     = $this->clientObject->doCategoryEntryList($categoryEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);

		$categoryIdsOfEntry = array();
		if(count($categoryEntryList->objects)) {
			foreach($categoryEntryList->objects as $categoryEntry) {
				/* @var $categoryEntry KalturaCategoryEntry */
				if($categoryEntry->status = KalturaCategoryEntryStatus::ACTIVE) {
					$categoryIdsOfEntry[] = $categoryEntry->categoryId;
				}
			}
		}
		return $categoryIdsOfEntry;
	}


}
