<?php
require_once('EntryAndFlavorActions.php');

class EntryCategoryObject
{

	/* @var $entryAndFlavorActions EntryAndFlavorActions */
	private $entryAndFlavorActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->entryAndFlavorActions = new EntryAndFlavorActions($serviceUrl, $partnerId, $adminSecret);
	}

//	public function doDryRun($outputPathCsv) {
//		$outputCsv = fopen($outputPathCsv, 'w');
//		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CreatedAt'));
//
//		list($pager, $mediaEntryFilter) = $this->entryAndFlavorActions->definePagerAndFilterForDryRun();
//
//		echo 'Printing source flavor of entries created at more than a year ago- ' . "\n\n";
//		$this->printSourceFlavorOfEntriesAccordingToFilter($mediaEntryFilter, $pager, $outputCsv);
//
//		fclose($outputCsv);
//	}

	public function addCategoryEntriesFromFile($inputEntryIdsCategoryIdsFile, $separator) {
		$entryIdCategoryIdArray = $this->entryAndFlavorActions->getAllEntryIdsCategoryIdsFromFile($inputEntryIdsCategoryIdsFile, $separator);

		$currentCount         = 0;
		$totalCount           = count($entryIdCategoryIdArray);
		$numberOfProgressBars = ($totalCount < 50) ? $totalCount : 50;
		$progressBarIncrement = ceil($totalCount / $numberOfProgressBars);
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);

		foreach($entryIdCategoryIdArray as $key => $value) {
			$currentCount++;
			if($currentCount % $progressBarIncrement == 0) {
				$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
			}

			echo 'Adding entry ' . $key . ' to category ' . $value . ":\n";
			$categoryEntry = new KalturaCategoryEntry();
			$categoryEntry->entryId = $key;
			$categoryEntry->categoryId = $value;

			$successMessage        = 'Entry ' . $key . ' was added to category ' . $value . "\n\n";
			$trialsExceededMessage = 'Exceeded number of trials for entry ' . $key . '. Moving on to next entry' . "\n\n";
			$this->entryAndFlavorActions->clientObject->doCategoryEntryAdd($categoryEntry, $successMessage, $trialsExceededMessage, 1);
		}
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
		echo "End of entries" . "\n";
	}

	private function calculateProgressBar(int $currentCount, int $progressBarIncrement, int $numberOfProgressBars, int $totalCount) {
		$progressIteration = ceil($currentCount / $progressBarIncrement);
		if($currentCount == $totalCount) {
			$progressIteration = $numberOfProgressBars;
		}
		$doneString = "";
		for($j = 0; $j < $progressIteration; $j++) {
			$doneString .= "=";
		}
		$remainingString = "";
		for($j = 0; $j < $numberOfProgressBars - ($progressIteration); $j++) {
			$remainingString .= "-";
		}
		$percent = $totalCount ? floor($currentCount / $totalCount * 100) : 100;
		echo "\n" . $currentCount . "/" . $totalCount . "\t" . "[" . $doneString . ">" . $remainingString . "]" . "\t" . $percent . "%" . "\n\n";
		return;
	}

}
