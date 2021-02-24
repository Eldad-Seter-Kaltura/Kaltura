<?php
require_once('ClientObject.php');


class EntryAndFlavorActions
{
	private $timeStampCreatedAtBefore;

	/* @var $clientObject ClientObject */
	public $clientObject;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_timeStampCreatedAtBefore) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClientOrRefreshKsIfNeeded("start");

		$this->timeStampCreatedAtBefore                 = $m_timeStampCreatedAtBefore;
	}

	public function getFirstColumnFromCsvFile($inputFile): array {
		$outputArray = array();
		$inputHandle = fopen($inputFile, 'r');
		fgetcsv($inputHandle);  //entry.id header
		while($line = fgetcsv($inputHandle)) {
			$outputArray [] = $line[0];
		}
		fclose($inputHandle);
		return $outputArray;
	}


	public function definePagerAndFilterForDryRun() {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->mediaTypeIn = KalturaMediaType::VIDEO;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		$mediaEntryFilter->createdAtLessThanOrEqual          = $this->timeStampCreatedAtBefore;

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

	public function gettingSourceFlavorAssetIdAndExtensionOfEntry($entryId): array {
		$flavorAssetFilter               = new KalturaFlavorAssetFilter();
		$flavorAssetFilter->entryIdEqual = $entryId;

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
		$flavorAssetList       = $this->clientObject->doFlavorAssetList($flavorAssetFilter, $trialsExceededMessage, $firstTry);

		$fileExt = "";
		$flavorAssetIdSource = "";
		if($flavorAssetList->totalCount) {
			foreach($flavorAssetList->objects as $flavorAsset) {
				/* @var $flavorAsset KalturaFlavorAsset */
				$flavorParamId = (int)($flavorAsset->flavorParamsId);
				if($flavorParamId == 0) {
					$flavorAssetIdSource = $flavorAsset->id;
					$fileExt = $flavorAsset->fileExt;
					break;
				}
			}
		}
		return array($flavorAssetIdSource, $fileExt);
	}

	public function gettingSourceFlavorAssetIdOfEntry($entryId) {
		$flavorAssetFilter               = new KalturaFlavorAssetFilter();
		$flavorAssetFilter->entryIdEqual = $entryId;

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
		$flavorAssetList       = $this->clientObject->doFlavorAssetList($flavorAssetFilter, $trialsExceededMessage, $firstTry);

		$flavorAssetIdSource = "";
		if($flavorAssetList->totalCount) {
			foreach($flavorAssetList->objects as $flavorAsset) {
				/* @var $flavorAsset KalturaFlavorAsset */
				$flavorParamId = (int)($flavorAsset->flavorParamsId);
				if($flavorParamId == 0) {
					$flavorAssetIdSource = $flavorAsset->id;
					break;
				}
			}
		}
		return $flavorAssetIdSource;
	}

}
