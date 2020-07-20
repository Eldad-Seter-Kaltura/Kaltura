<?php
require_once('ClientObject.php');


class EntryAndFlavorActions
{
	private $timeStamp;
	private $categoryArray;

	public ClientObject $clientObject;


	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_timeStamp, $m_categoryArray) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClient();
		$this->timeStamp     = $m_timeStamp;
		$this->categoryArray = $m_categoryArray;
	}

	public function getCategoryArray(): array {
		return $this->categoryArray;
	}

	public function definePagerAndFilter() {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->mediaTypeIn = KalturaMediaType::VIDEO;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		$mediaEntryFilter->createdAtLessThanOrEqual          = $this->timeStamp;
		$mediaEntryFilter->updatedAtLessThanOrEqual          = $this->timeStamp;
		$mediaEntryFilter->lastPlayedAtLessThanOrEqualOrNull = $this->timeStamp;

		return array($pager, $mediaEntryFilter);
	}

	public function definePagerAndFilterForInputFile(string $entryIds500String): array {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k case)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_DESC;
		$mediaEntryFilter->idIn        = $entryIds500String;
		return array($pager, $mediaEntryFilter);
	}

	public function getAllEntryIdsFromFile($inputEntryIdsFile): array {
		$entryIdsArray       = array();
		$inputEntryIdsHandle = fopen($inputEntryIdsFile, 'r');
		fgetcsv($inputEntryIdsHandle);  //entry.id
		while($line = fgetcsv($inputEntryIdsHandle)) {
			$entryIdsArray [] = $line[0];
		}
		fclose($inputEntryIdsHandle);
		return $entryIdsArray;
	}


	public function gettingTypeOfEntry(KalturaMediaEntry $currentEntry) {
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

	public function gettingCategoryNamesOfEntry(KalturaMediaEntry $currentEntry) {
		$categoryEntryFilter               = new KalturaCategoryEntryFilter();
		$categoryEntryFilter->entryIdEqual = $currentEntry->id;

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
		$categoryEntryList     = $this->clientObject->doCategoryEntryList($categoryEntryFilter, $trialsExceededMessage, $firstTry);   //TODO: can return multiple results (categories)

		$categoriesOfEntry = array();
		if(count($categoryEntryList->objects)) {
			foreach($categoryEntryList->objects as $categoryEntry) {
				/* @var $categoryEntry KalturaCategoryEntry */
				$categoriesOfEntry[] = $categoryEntry->categoryId;
			}
		}

		$categoriesFullName = array();
		foreach($categoriesOfEntry as $categoryId) {
			$trialsExceededMessage = 'Exceeded number of trials for category ' . $categoryId . '. Moving on to next category' . "\n\n";
			$category              = $this->clientObject->doCategoryGet($categoryId, $trialsExceededMessage, $firstTry);

			if($category && $category->id == $categoryId) {
				$categoriesFullName[] = $category->fullName;
			}
		}
		return implode(",", $categoriesFullName);
	}

	public function gettingFlavorNamesAndAssetIdsToDelete($flavorParamsIdsOfRule, $flavorAssetFilter, KalturaMediaEntry $currentEntry): array {
		//getting flavors..
		$flavorAssetFilter->entryIdEqual = $currentEntry->id;

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
		$flavorAssetList       = $this->clientObject->doFlavorAssetList($flavorAssetFilter, $trialsExceededMessage, $firstTry);

		$flavorAssetIdParamIdWithoutSrcAndOthers = array();

		if(count($flavorAssetList->objects)) {
			/* @var $flavorAsset KalturaFlavorAsset */
			foreach($flavorAssetList->objects as $flavorAsset) {
				$flavorParamId = (int)($flavorAsset->flavorParamsId);
				if($flavorParamId != 0) {
					$isEqual = FALSE;
					foreach($flavorParamsIdsOfRule as $flavorParamIdOfRule) {
						if($flavorParamId == $flavorParamIdOfRule) {
							$isEqual = TRUE;
							break;
						}
					}
					//flavorParamId != all of those
					if(!$isEqual) {
						//key-value
						$flavorAssetIdParamIdWithoutSrcAndOthers[$flavorAsset->id] = $flavorParamId;
					}
				}
			}
		}


		$flavorParamNamesArray = array();
		foreach($flavorAssetIdParamIdWithoutSrcAndOthers as $flavorParamIdToDelete) {
			$trialsExceededMessage                         = 'Exceeded number of trials for this flavor. Moving on to next flavor' . "\n\n";
			$flavorParamObject                             = $this->clientObject->doFlavorParamsGet($flavorParamIdToDelete, $trialsExceededMessage, $firstTry);
			$flavorParamNamesArray[$flavorParamIdToDelete] = $flavorParamObject->name;
		}
		$flavorParamNamesToDelete = implode(",", $flavorParamNamesArray);
		return array($flavorAssetIdParamIdWithoutSrcAndOthers, $flavorParamNamesToDelete);
	}

	public function generateXMLForMRP($flavorParamsIdsOfRule) {

		$metadataProfileFilter                  = new KalturaMetadataProfileFilter();
		$metadataProfileFilter->systemNameEqual = "MRP";

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$metadataProfileList   = $this->clientObject->doMetadataProfileList($metadataProfileFilter, $trialsExceededMessage, $firstTry);

		$metadataProfileId = "";
		if(count($metadataProfileList->objects)) {
			/* @var $metadataProfile KalturaMetadataProfile */
			$metadataProfile   = $metadataProfileList->objects[0];
			$metadataProfileId = $metadataProfile->id;

			$scheduledTaskProfileFilter                  = new KalturaScheduledTaskProfileFilter();
			$scheduledTaskProfileFilter->systemNameEqual = "MRP";

			$scheduledTaskProfileList = $this->clientObject->doScheduledTaskProfileList($scheduledTaskProfileFilter, $trialsExceededMessage, $firstTry);
			if(count($scheduledTaskProfileList->objects)) {
				$scheduledTaskProfileIds = array();
				/* @var $scheduledTaskProfile KalturaScheduledTaskProfile */
				foreach($scheduledTaskProfileList->objects as $scheduledTaskProfile) {
					$scheduledTaskProfileIds [] = $scheduledTaskProfile->id;
				}

				$scheduledTaskProfileIdString = "";
				//Rule 5
				if($flavorParamsIdsOfRule[0] == 1248522) {
					$scheduledTaskProfileIdString = "MR_" . $scheduledTaskProfileIds[0];
				}
				//Other Rules
				else {
					if($flavorParamsIdsOfRule[0] == 1248532) {
						$scheduledTaskProfileIdString = "MR_" . $scheduledTaskProfileIds[1];
					}
				}
				$xmlMRP = "<metadata><MRPsOnEntry>" . $scheduledTaskProfileIdString . "</MRPsOnEntry></metadata>";
			}
		}
		return array($metadataProfileId, $scheduledTaskProfileIdString, $xmlMRP);
	}


}
