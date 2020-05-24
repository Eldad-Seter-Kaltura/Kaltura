<?php
require_once ('ClientObject.php');

class ProdObject
{
	private $timeStamp;

	private $categoryArray;

	public function __construct($m_timeStamp, $m_categoryArray) {
		$this->timeStamp = $m_timeStamp;
		$this->categoryArray = $m_categoryArray;
	}

	public function deleteAllFlavorsAccordingAccordingToCategories($isRealRun, $clientObject, $timeStamp, $outputPathCsv) {

		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'FlavorNames', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

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

		$flavorAssetFilter              = new KalturaFlavorAssetFilter();
		$flavorAssetFilter->statusEqual = KalturaFlavorAssetStatus::READY;

		$flavorParamsIdsArrayRule5 = array(1248522, 487061, 1248502, 487041, 2027202);
		$this->deleteAllFlavorsRule5($isRealRun, $mediaEntryFilter, $pager, $this->categoryArray, $flavorAssetFilter, $flavorParamsIdsArrayRule5, $clientObject, $outputCsv);

		$flavorParamsIdsArrayOtherRules = array(1248532, 487071, 1248502, 487041, 2027202);
		for($i = 0; $i < count($this->categoryArray); $i++) {
			$this->deleteAllFlavorsRule($i + 6, $isRealRun, $mediaEntryFilter, $pager, $this->categoryArray[$i], $flavorAssetFilter, $flavorParamsIdsArrayOtherRules, $clientObject, $outputCsv);
		}

	}

	private function deleteAllFlavorsRule5($isRealRun, $mediaEntryFilter, $pager, $categoryArray, $flavorAssetFilter, $flavorParamsIdsArrayRule5, $clientObject, $outputCsv) {

		echo 'Deleting all flavors of rule 5' . ' if real run: ' . ($isRealRun ? 'true' : 'false') . "\n\n";

		$categoryString                             = implode(",", $categoryArray);
		$mediaEntryFilter->categoriesIdsNotContains = $categoryString;

		$this->deleteAllFlavorsAccordingToRule($isRealRun, $mediaEntryFilter, $pager, $flavorAssetFilter, $flavorParamsIdsArrayRule5, $clientObject, $outputCsv);
	}

	private function deleteAllFlavorsRule($number, $isRealRun, $mediaEntryFilter, $pager, $categoryId, $flavorAssetFilter, $flavorParamsIdsArrayOtherRules, $clientObject, $outputCsv) {

		echo 'Deleting all flavors of rule ' . $number . ' if real run ' . ($isRealRun ? 'true' : 'false') . "\n\n";

		$mediaEntryFilter->categoriesIdsMatchAnd = $categoryId;
		$this->deleteAllFlavorsAccordingToRule($isRealRun, $mediaEntryFilter, $pager, $flavorAssetFilter, $flavorParamsIdsArrayOtherRules, $clientObject, $outputCsv);
	}

	private function deleteAllFlavorsAccordingToRule($isRealRun, $mediaEntryFilter, $pager, $flavorAssetFilter, $flavorParamsIdsOfRule, $clientObject, $outputCsv) {

		/* @var $clientObject ClientObject */

		$message   = 'Total number of entries: ';
		$mediaList = $clientObject->doMediaList($mediaEntryFilter, $pager, $message, 1, $outputCsv);

		while(count($mediaList->objects)) {

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {

				$type = $this->gettingTypeOfEntry($currentEntry);

				$categoriesFullNameString = $this->gettingCategoryNamesOfEntry($currentEntry, $clientObject, $outputCsv);

				//getting flavors..
				$flavorAssetFilter->entryIdEqual = $currentEntry->id;
				$flavorAssetList = $clientObject->doFlavorAssetList($flavorAssetFilter, 1, $outputCsv);

				$flavorAssetIdParamIdWithoutSrcAndOthers = array();
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

				//now we're ready to delete
				if($isRealRun) {
					foreach(array_keys($flavorAssetIdParamIdWithoutSrcAndOthers) as $flavorAssetId) {
						$clientObject->doFlavorAssetDelete($flavorAssetId, 1);
					}
				}

				$flavorParamNamesArray = array();
				foreach($flavorAssetIdParamIdWithoutSrcAndOthers as $flavorParamIdToDelete) {
					$flavorParamObject = $clientObject->doFlavorParamsGet($flavorParamIdToDelete, 1);
					$flavorParamNamesArray[$flavorParamIdToDelete] = $flavorParamObject->name;
				}
				$flavorParamNamesToDelete = implode(",", $flavorParamNamesArray);

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $categoriesFullNameString, $flavorParamNamesToDelete, $currentEntry->createdAt, $currentEntry->updatedAt,
					$currentEntry->lastPlayedAt));

			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$clientObject->doMediaList($mediaEntryFilter, $pager, "", 1, $outputCsv);
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
