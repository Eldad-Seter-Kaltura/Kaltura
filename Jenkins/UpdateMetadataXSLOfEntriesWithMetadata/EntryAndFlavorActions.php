<?php
require_once('ClientObject.php');


class EntryAndFlavorActions
{
	private $mrProfileId;
	private $mrMetadataSearch;
	private $xslFilePath;
	private $flavorParamsIdsArray;
	private $timeStampCreatedAtBefore;
	private $timeStampLastPlayedAtLessThanEqualOrNull;

	/* @var $clientObject ClientObject */
	public $clientObject;


	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_mrProfileId, $m_mrMetadataSearch, $m_xslFilePath, $m_flavorParamsIdsArray, $m_timeStampCreatedAtBefore, $m_timeStampLastPlayedAtLessThanEqualOrNull) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClientOrRefreshKsIfNeeded("start");

		$this->mrProfileId                              = $m_mrProfileId;
		$this->mrMetadataSearch                         = $m_mrMetadataSearch;
		$this->xslFilePath                              = $m_xslFilePath;
		$this->flavorParamsIdsArray                     = $m_flavorParamsIdsArray;
		$this->timeStampCreatedAtBefore                 = $m_timeStampCreatedAtBefore;
		$this->timeStampLastPlayedAtLessThanEqualOrNull = $m_timeStampLastPlayedAtLessThanEqualOrNull;
	}

	public function getMRProfileId() {
		return $this->mrProfileId;
	}

	public function getMRMetadataValues(): array {
		$arrayPairs = explode(";", $this->mrMetadataSearch);
		$arrayKeyValue = array();
		foreach($arrayPairs as $fieldValue) {
			$arrayFieldValue = explode("=", $fieldValue);
			$key = $arrayFieldValue[0];
			$value = $arrayFieldValue[1];
			$arrayKeyValue[$key] = $value;
		}
		return $arrayKeyValue;
	}

	public function getXslFilePath() {
		return $this->xslFilePath;
	}

	public function getFlavorParamsIdsArray(): array {
		return $this->flavorParamsIdsArray;
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
		$mediaEntryFilter->lastPlayedAtLessThanOrEqualOrNull = $this->timeStampLastPlayedAtLessThanEqualOrNull;

		return array($pager, $mediaEntryFilter);
	}

	public function getAllEntryIdsFlavorParamsIdsFromFile($inputEntryIdsFlavorParamsIdsFile): array {
		$entryIdFlavorParamsIdsArray        = array();
		$inputEntryIdsFlavorParamsIdsHandle = fopen($inputEntryIdsFlavorParamsIdsFile, 'r');
		fgetcsv($inputEntryIdsFlavorParamsIdsHandle);  //entry.id
		while($line = fgetcsv($inputEntryIdsFlavorParamsIdsHandle)) {
			//key-value
			$entryIdFlavorParamsIdsArray [$line[0]] = $line[1];
		}
		fclose($inputEntryIdsFlavorParamsIdsHandle);
		return $entryIdFlavorParamsIdsArray;
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

	public function gettingFlavorParamIdsToDelete($flavorParamsIdsToKeep, $flavorParamsIdsOfEntry): array {
		$flavorParamsIdsToDelete = array();
		foreach($flavorParamsIdsOfEntry as $flavorParamId) {
			if($flavorParamId != "0") {
				$isEqual = FALSE;
				foreach($flavorParamsIdsToKeep as $flavorParamIdToKeep) {
					if($flavorParamId == $flavorParamIdToKeep) {
						$isEqual = TRUE;
						break;
					}
				}
				if(!$isEqual) {
					//flavorParamId of entry != all of those of rule
					$flavorParamsIdsToDelete [] = $flavorParamId;
				}
			}
		}

		return $flavorParamsIdsToDelete;
	}

	public function gettingFlavorAssetIdsToDelete($flavorParamsIdsToDelete, $entryId): array {
		//getting flavors of entry
		$flavorAssetFilter               = new KalturaFlavorAssetFilter();
		$flavorAssetFilter->statusEqual  = KalturaFlavorAssetStatus::READY;
		$flavorAssetFilter->entryIdEqual = $entryId;

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
		$flavorAssetList       = $this->clientObject->doFlavorAssetList($flavorAssetFilter, $trialsExceededMessage, $firstTry);

		$flavorAssetIdsToDelete = array();

		if(count($flavorAssetList->objects)) {
			/* @var $flavorAsset KalturaFlavorAsset */
			foreach($flavorAssetList->objects as $flavorAsset) {
				$flavorParamId = (int)($flavorAsset->flavorParamsId);
				if($flavorParamId != 0) {
					foreach($flavorParamsIdsToDelete as $flavorParamIdToDelete) {
						if($flavorParamId == $flavorParamIdToDelete) {
							$flavorAssetIdsToDelete[] = $flavorAsset->id;
							break;
						}
					}
				}
			}
		}

		return $flavorAssetIdsToDelete;
	}


}
