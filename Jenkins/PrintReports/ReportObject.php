<?php
require_once('Actions.php');


class ReportObject
{
	/* @var $actions Actions */
	private $actions;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->actions = new Actions($serviceUrl, $partnerId, $adminSecret);
	}

	public function doEntitledUsersEntryOwnerReport($outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('Name', 'Description', 'Tags', 'ID', 'UserID', 'CreatedAt', 'EntitledUsersEdit', 'EntitledUsersPublish', 'EntitledUsersView', 'ThumbnailUrl'));

		list($pager, $baseEntryFilter) = $this->actions->definePagerAndFilter("baseEntryFilter");

		$firstTry              = 1;
		$message               = 'Total number of entries for report: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$baseEntryList         = $this->actions->clientObject->doBaseEntryList($baseEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($baseEntryList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($baseEntryList->objects) . "\n\n";

			/* @var $currentEntry KalturaBaseEntry */
			foreach($baseEntryList->objects as $currentEntry) {
				$dataArray = array($currentEntry->name, $currentEntry->description, $currentEntry->tags, $currentEntry->id, $currentEntry->userId, $currentEntry->createdAt,
					$currentEntry->entitledUsersEdit, $currentEntry->entitledUsersPublish, $currentEntry->entitledUsersView, $currentEntry->thumbnailUrl);
				fputcsv($outputCsv, $dataArray);
			}

			//baseEntry.list - next iterations
			$baseEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$baseEntryList                                 = $this->actions->clientObject->doBaseEntryList($baseEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		echo "Finished printing entries of report." . "\n\n";
		fclose($outputCsv);
	}

	public function doCategoryEntryReport($outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('CategoryID', 'EntryID', 'FullIDs'));

		list($pager, $categoryEntryFilter) = $this->actions->definePagerAndFilter("categoryEntryFilter");

		$firstTry              = 1;
		$message               = 'Total number of category entries for report: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$categoryEntryList     = $this->actions->clientObject->doCategoryEntryList($categoryEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($categoryEntryList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Category entries per page: ' . count($categoryEntryList->objects) . "\n\n";

			/* @var $categoryEntry KalturaCategoryEntry */
			foreach($categoryEntryList->objects as $categoryEntry) {
				$dataArray = array($categoryEntry->categoryId, $categoryEntry->entryId, $categoryEntry->categoryFullIds);
				fputcsv($outputCsv, $dataArray);
			}

			//categoryEntry.list - next iterations
			$categoryEntryFilter->createdAtGreaterThanOrEqual = $categoryEntry->createdAt + 1;
			$categoryEntryList                                = $this->actions->clientObject->doCategoryEntryList($categoryEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		echo "Finished printing category entries of report." . "\n\n";
		fclose($outputCsv);
	}

	public function doCategoryUserPermissionsReport($inputPathCsv, $outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('CategoryID', 'UserId', 'PermissionNames'));

		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";

		list($pager, $categoryUserFilter) = $this->actions->definePagerAndFilter("categoryUserFilter");

		$categoryIdsArray = $this->actions->getAllIdsFromFile($inputPathCsv);
		echo 'Number of categories: ' . count($categoryIdsArray) . "\n";

		$i = 0;
		foreach($categoryIdsArray as $categoryId) {
			if(++$i % 50 == 0) {
				echo 'Category ' . $i . ' of ' . count($categoryIdsArray) . "\n";
			}

			$categoryUserFilter->categoryIdEqual = $categoryId;
			$categoryUserList                    = $this->actions->clientObject->doCategoryUserList($categoryUserFilter, $pager, "", $trialsExceededMessage, $firstTry);

			/* @var $categoryUser KalturaCategoryUser */
			foreach($categoryUserList->objects as $categoryUser) {
				$dataArray = array($categoryUser->categoryId, $categoryUser->userId, $categoryUser->permissionNames);
				fputcsv($outputCsv, $dataArray);
			}
		}
		echo "Finished printing category users of report." . "\n\n";
		fclose($outputCsv);
	}

	public function doCategoryListReport($outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('ID', 'Name', 'Owner', 'FullName', 'FullIDs', 'Description', 'Tags', 'Privacy', 'InheritanceType'));

		list($pager, $categoryFilter) = $this->actions->definePagerAndFilter("categoryFilter");

		$firstTry              = 1;
		$message               = 'Total number of categories for report: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$categoryList          = $this->actions->clientObject->doCategoryList($categoryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($categoryList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Categories per page: ' . count($categoryList->objects) . "\n\n";

			/* @var $currentCategory KalturaCategory */
			foreach($categoryList->objects as $currentCategory) {
				$dataArray = array($currentCategory->id, $currentCategory->name, $currentCategory->owner, $currentCategory->fullName, $currentCategory->fullIds, $currentCategory->description,
					$currentCategory->tags, $currentCategory->privacy, $currentCategory->inheritanceType);
				fputcsv($outputCsv, $dataArray);
			}

			//category.list - next iterations
			$categoryFilter->createdAtGreaterThanOrEqual = $currentCategory->createdAt + 1;
			$trialsExceededMessage                       = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$categoryList                                = $this->actions->clientObject->doCategoryList($categoryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		echo "Finished printing categories of report." . "\n\n";
		fclose($outputCsv);
	}

	public function doEntryLastPlayedAtCategories($outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('Name', 'EntryID', 'Type', 'Duration', 'CreatedAt', 'LastPlayedAt', 'Creator', 'Owner', 'Categories'));

		list($pager, $mediaEntryFilter) = $this->actions->definePagerAndFilter("mediaEntryFilter");

		$firstTry              = 1;
		$message               = 'Total number of entries for report: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$mediaList         = $this->actions->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($mediaList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($mediaList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {
				if($currentEntry->displayInSearch == KalturaEntryDisplayInSearchType::PARTNER_ONLY) {
					$mediaTypeString         = $this->actions->printingTypeOfEntry($currentEntry->mediaType);
					$categoryFullNamesArray = $this->actions->gettingCategoryFullNamesOfEntry($currentEntry->id);
					$categoryFullNamesString = implode(",", $categoryFullNamesArray);

					$dataArray = array($currentEntry->name, $currentEntry->id, $mediaTypeString, $currentEntry->duration, $currentEntry->createdAt, $currentEntry->lastPlayedAt,
						$currentEntry->creatorId, $currentEntry->userId, $categoryFullNamesString);
					fputcsv($outputCsv, $dataArray);
				}
			}

			//media.list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$mediaList                                 = $this->actions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		echo "Finished printing entries of report." . "\n\n";
		fclose($outputCsv);
	}

	public function doEntryCreatedAtCategoriesReport($outputPathCsv, $createdAtLessThan, $categoriesIdsNotContains) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'Description', 'Type', 'CreatedAt', 'CategoriesIds'));

		list($pager, $mediaEntryFilter) = $this->actions->definePagerAndFilter("mediaEntryFilter");
		$mediaEntryFilter->createdAtLessThanOrEqual = $createdAtLessThan;
		$mediaEntryFilter->categoriesIdsNotContains = $categoriesIdsNotContains;

		$firstTry              = 1;
		$message               = 'Total number of entries for report: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$mediaList         = $this->actions->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($mediaList->objects)) {
			echo 'Beginning of page: ' . ++$i . "\n";
			echo 'Entries per page: ' . count($mediaList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {
				$mediaType         = $this->actions->printingTypeOfEntry($currentEntry->mediaType);
				$categoryIdsArray = $this->actions->gettingCategoryIdsOfEntry($currentEntry->id);
				$categoryIdsString = implode(";", $categoryIdsArray);

				$dataArray = array($currentEntry->id, $currentEntry->name, $currentEntry->description, $mediaType, $currentEntry->createdAt, $categoryIdsString);
				fputcsv($outputCsv, $dataArray);
			}
			//media.list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$trialsExceededMessage                         = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$mediaList                                 = $this->actions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}
		echo "Finished printing entries of report." . "\n\n";
		fclose($outputCsv);
	}
}
