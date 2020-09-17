<?php
require_once('Actions.php');


class ReportObject
{
	/* @var $actions Actions */
	private $actions;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->actions = new Actions($serviceUrl, $partnerId, $adminSecret);
	}

	public function doFirstReport($outputPathCsv) {
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

	public function doSecondReport($outputPathCsv) {
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

	public function doThirdReport($inputPathCsv, $outputPathCsv) {
	}

	public function doFourthReport($outputPathCsv) {
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
}
