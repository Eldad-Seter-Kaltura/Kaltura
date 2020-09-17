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
	}

	public function doThirdReport($inputPathCsv, $outputPathCsv) {
	}

	public function doFourthReport($outputPathCsv) {
		list($pager, $categoryFilter) = $this->actions->definePagerAndFilter("categoryFilter");

		$firstTry              = 1;
		$message               = 'Total number of entries for report: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
//		$categoryList          = $this->actions->clientObject->doCategoryList($categoryFilter, $pager, $message, $trialsExceededMessage, $firstTry);
	}
}
