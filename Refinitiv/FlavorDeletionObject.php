<?php
require_once('EntryAndFlavorActions.php');

class FlavorDeletionObject
{

	private EntryAndFlavorActions $entryAndFlavorActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_timeStamp, $m_categoryArray) {
		$this->entryAndFlavorActions = new EntryAndFlavorActions($serviceUrl, $partnerId, $adminSecret, $m_timeStamp, $m_categoryArray);
	}


	public function deleteAllFlavorsAccordingAccordingToCategories($outputPathCsvRule5, $outputPathCsvOtherRules) {

		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilter();
		$flavorAssetFilter              = new KalturaFlavorAssetFilter();
		$flavorAssetFilter->statusEqual = KalturaFlavorAssetStatus::READY;

		$categoryArray = $this->entryAndFlavorActions->getCategoryArray();

		$categoryString                             = implode(",", $categoryArray);
		$mediaEntryFilter->categoriesIdsNotContains = $categoryString;

		$outputCsvRule5 = fopen($outputPathCsvRule5, 'w');
		fputcsv($outputCsvRule5, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'FlavorNames', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		echo 'Deleting all flavors of rule 5:' . "\n\n";
		$flavorParamsIdsArrayRule5 = array(1248522, 487061, 1248502, 487041, 2027202);
		$this->deleteAllFlavorsRule5($flavorParamsIdsArrayRule5, $flavorAssetFilter, $mediaEntryFilter, $pager, $outputCsvRule5);

		fclose($outputCsvRule5);

		$mediaEntryFilter->categoriesIdsNotContains = NULL;   //IMPORTANT!! RESET before going to OTHER RULES!!

		$outputCsvOtherRules = fopen($outputPathCsvOtherRules, 'w');
		fputcsv($outputCsvOtherRules, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'FlavorNames', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		$flavorParamsIdsArrayOtherRules = array(1248532, 487071, 1248502, 487041, 2027202);
		for($i = 0; $i < count($categoryArray); $i++) {

			echo 'Deleting all flavors of rule ' . ($i + 6) . ':' . "\n\n";
			$mediaEntryFilter->categoriesIdsMatchAnd = $categoryArray[$i];
			$this->deleteAllFlavorsOtherRules($flavorParamsIdsArrayOtherRules, $flavorAssetFilter, $mediaEntryFilter, $pager, $outputCsvOtherRules);

			fputcsv($outputCsvOtherRules, array('===', '===', '===', '===', '===', '===', '===', '==='));
			fputcsv($outputCsvOtherRules, array('===', '===', 'End', 'Of', 'Rule', $i + 6, '===', '==='));
			fputcsv($outputCsvOtherRules, array('===', '===', '===', '===', '===', '===', '===', '==='));
		}
		fclose($outputCsvOtherRules);
	}

	private function deleteAllFlavorsRule5($flavorParamsIdsArrayRule5, $flavorAssetFilter, $mediaEntryFilter, $pager, $outputCsv) {
		$this->deleteAllFlavorsAccordingToRule($flavorParamsIdsArrayRule5, $flavorAssetFilter, $mediaEntryFilter, $pager, $outputCsv);
	}

	private function deleteAllFlavorsOtherRules($flavorParamsIdsArrayOtherRules, $flavorAssetFilter, $mediaEntryFilter, $pager, $outputCsv) {
		$this->deleteAllFlavorsAccordingToRule($flavorParamsIdsArrayOtherRules, $flavorAssetFilter, $mediaEntryFilter, $pager, $outputCsv);
	}

	private function deleteAllFlavorsAccordingToRule($flavorParamsIdsOfRule, $flavorAssetFilter, $mediaEntryFilter, $pager, $outputCsv) {

		list($metadataProfileId, $scheduledTaskProfileIdString, $xmlMRP) = $this->entryAndFlavorActions->generateXMLForMRP($flavorParamsIdsOfRule);

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

				list($flavorAssetIdParamIdWithoutSrcAndOthers, $flavorParamNamesToDelete) = $this->entryAndFlavorActions->gettingFlavorNamesAndAssetIdsToDelete($flavorParamsIdsOfRule, $flavorAssetFilter, $currentEntry);

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $categoriesFullNameString, $flavorParamNamesToDelete, $currentEntry->createdAt, $currentEntry->updatedAt,
					$currentEntry->lastPlayedAt));

				//now we're ready to delete
				foreach(array_keys($flavorAssetIdParamIdWithoutSrcAndOthers) as $flavorAssetId) {
					$this->entryAndFlavorActions->clientObject->doFlavorAssetDelete($flavorAssetId, $firstTry);
				}

				//mark this entry for MR
				$successMessage        = "Metadata- " . "MRPsOnEntry: " . $scheduledTaskProfileIdString . " -added for entry " . $currentEntry->id . "\n";
				$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
				$this->entryAndFlavorActions->clientObject->doMetadataAdd($metadataProfileId, $currentEntry->id, $xmlMRP, $successMessage, $trialsExceededMessage, $firstTry);
			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$baseEntryList                                 = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		$mediaEntryFilter->createdAtGreaterThanOrEqual = NULL;  //IMPORTANT!! RESET AFTER EACH RULE!!
	}


	public function printAllFlavorsAccordingAccordingToCategories($outputPathCsvRule5, $outputPathCsvOtherRules) {

		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilter();

		$categoryArray = $this->entryAndFlavorActions->getCategoryArray();

		$categoryString                             = implode(",", $categoryArray);
		$mediaEntryFilter->categoriesIdsNotContains = $categoryString;

		$outputCsvRule5 = fopen($outputPathCsvRule5, 'w');
		fputcsv($outputCsvRule5, array('EntryID', 'Name', 'MediaType', 'FlavorParamsIDs', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		echo 'Printing all flavors of rule 5:' . "\n\n";
		$flavorParamsIdsArrayRule5 = array(1248522, 487061, 1248502, 487041, 2027202);
		$this->printAllFlavorsRule5($flavorParamsIdsArrayRule5, $mediaEntryFilter, $pager, $outputCsvRule5);

		fclose($outputCsvRule5);

		$mediaEntryFilter->categoriesIdsNotContains = NULL;   //IMPORTANT!! RESET before going to OTHER RULES!!

		$outputCsvOtherRules = fopen($outputPathCsvOtherRules, 'w');
		fputcsv($outputCsvOtherRules, array('EntryID', 'Name', 'MediaType', 'FlavorParamsIDs', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		$flavorParamsIdsArrayOtherRules = array(1248532, 487071, 1248502, 487041, 2027202);
		for($i = 0; $i < count($categoryArray); $i++) {

			echo 'Printing all flavors of rule ' . ($i + 6) . ':' . "\n\n";
			$mediaEntryFilter->categoriesIdsMatchAnd = $categoryArray[$i];
			$this->printAllFlavorsOtherRules($flavorParamsIdsArrayOtherRules, $mediaEntryFilter, $pager, $outputCsvOtherRules);

			fputcsv($outputCsvOtherRules, array('===', '===', '===', '===', '===', '===', '==='));
			fputcsv($outputCsvOtherRules, array('===', 'End', 'Of', 'Rule', $i + 6, '===', '==='));
			fputcsv($outputCsvOtherRules, array('===', '===', '===', '===', '===', '===', '==='));
		}
		fclose($outputCsvOtherRules);
	}

	private function printAllFlavorsRule5($flavorParamsIdsArrayRule5, $mediaEntryFilter, $pager, $outputCsv) {
		$this->printAllFlavorsAccordingToRule($flavorParamsIdsArrayRule5, $mediaEntryFilter, $pager, $outputCsv);
	}

	private function printAllFlavorsOtherRules($flavorParamsIdsArrayOtherRules, $mediaEntryFilter, $pager, $outputCsv) {
		$this->printAllFlavorsAccordingToRule($flavorParamsIdsArrayOtherRules, $mediaEntryFilter, $pager, $outputCsv);
	}

	private function printAllFlavorsAccordingToRule($flavorParamsIdsOfRule, $mediaEntryFilter, $pager, $outputCsv) {

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

				//getting flavor params..
				$flavorParamsIdsToDelete = $currentEntry->flavorParamsIds;
				$flavorParamsIdsToDelete = ltrim($flavorParamsIdsToDelete, "0,");
				foreach($flavorParamsIdsOfRule as $flavorParamIdOfRule) {
					$flavorParamsIdsToDelete = str_replace($flavorParamIdOfRule, '', $flavorParamsIdsToDelete);
				}
				$flavorParamsIdsToDelete = trim($flavorParamsIdsToDelete, ",");
				$flavorParamsIdsToDelete = str_replace(",,", ",", $flavorParamsIdsToDelete);


				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $flavorParamsIdsToDelete, $currentEntry->createdAt, $currentEntry->updatedAt,
					$currentEntry->lastPlayedAt));
			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$baseEntryList                                 = $this->entryAndFlavorActions->clientObject->doBaseEntryList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		$mediaEntryFilter->createdAtGreaterThanOrEqual = NULL;  //IMPORTANT!! RESET AFTER EACH RULE!!
	}


	public function deleteFlavorsOfEntriesFromInputFile($inputEntryIdsFile, $flavorParamsIdsOfRule, $outputPathCsv) {

		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'FlavorNames', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));

		//0. build xml for MR (<MRPsOnEntry>MR_number</MRPsOnEntry>)
		list($metadataProfileId, $scheduledTaskProfileIdString, $xmlMRP) = $this->entryAndFlavorActions->generateXMLForMRP($flavorParamsIdsOfRule);

		$flavorAssetFilter              = new KalturaFlavorAssetFilter();
		$flavorAssetFilter->statusEqual = KalturaFlavorAssetStatus::READY;

		//0a. get all entry ids from file
		$entryIdsArray = $this->entryAndFlavorActions->getAllEntryIdsFromFile($inputEntryIdsFile);

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

				//1. get entry and flavor info and flavor asset ids to delete
				$type                     = $this->entryAndFlavorActions->gettingTypeOfEntry($currentEntry);
				$categoriesFullNameString = $this->entryAndFlavorActions->gettingCategoryNamesOfEntry($currentEntry);
				list($flavorAssetIdParamIdWithoutSrcAndOthers, $flavorParamNamesToDelete) = $this->entryAndFlavorActions->gettingFlavorNamesAndAssetIdsToDelete($flavorParamsIdsOfRule, $flavorAssetFilter, $currentEntry);

				//save data
				$dataArray = array($currentEntry->id, $currentEntry->name, $type, $categoriesFullNameString, $flavorParamNamesToDelete, $currentEntry->createdAt, $currentEntry->updatedAt,
					$currentEntry->lastPlayedAt);

				//2. delete flavor asset ids
				foreach(array_keys($flavorAssetIdParamIdWithoutSrcAndOthers) as $flavorAssetId) {
					$this->entryAndFlavorActions->clientObject->doFlavorAssetDelete($flavorAssetId, $firstTry);
				}

				//3. mark this entry for MR
				$successMessage        = "Metadata- " . "MRPsOnEntry: " . $scheduledTaskProfileIdString . " -added for entry " . $currentEntry->id . "\n";
				$trialsExceededMessage = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
				$this->entryAndFlavorActions->clientObject->doMetadataAdd($metadataProfileId, $currentEntry->id, $xmlMRP, $successMessage, $trialsExceededMessage, $firstTry);

				//print data
				fputcsv($outputCsv, $dataArray);
			}

			echo "Last entry: " . $currentEntry->id . "\n";
			echo "End of page\n\n";

			//media . list - next page (iteration)
			$entryIds500       = array_slice($entryIdsArray, $i * 500, 500);
			$entryIds500String = implode(",", $entryIds500);
			if($entryIds500String == "") {
				echo "End of entries" . "\n";
				break;
			}

			$mediaEntryFilter->idIn = $entryIds500String;
			$trialsExceededMessage  = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$mediaList              = $this->entryAndFlavorActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		echo "End of function\n";
	}

}
