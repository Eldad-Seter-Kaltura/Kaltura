<?php
require_once('ClientObject.php');


class EntryAndFlavorActions
{
	/* @var $clientObject ClientObject */
	public $clientObject;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClientOrRefreshKsIfNeeded("start");
	}

	public function definePagerAndFilterForDryRun() {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->mediaTypeIn = KalturaMediaType::VIDEO;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		return array($pager, $mediaEntryFilter);
	}

	public function gettingTypeOfEntry($mediaType) {
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
			default:
				$type = "OTHER";
				break;
		}
		return $type;
	}

	public function getAllLinesAndCountFromFile($inputEntryIdsCategoryIdsFile): array {
		$inputEntryIdsCategoryIdsHandle = fopen($inputEntryIdsCategoryIdsFile, 'r');
		fgetcsv($inputEntryIdsCategoryIdsHandle);  //entry.id cat.id column header
		$linesArray = array();

		$count = 0;
		while($line = fgets($inputEntryIdsCategoryIdsHandle, 100)) {
			$linesArray [] = rtrim($line);
			$count++;
		}
		fclose($inputEntryIdsCategoryIdsHandle);
		return array($linesArray, $count);
	}


}
