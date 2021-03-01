<?php
require_once('EntryAndFlavorActions.php');

class EntryCategoryObject
{

	/* @var $entryAndFlavorActions EntryAndFlavorActions */
	private $entryAndFlavorActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->entryAndFlavorActions = new EntryAndFlavorActions($serviceUrl, $partnerId, $adminSecret);
	}


	public function addCategoryEntriesFromFile($inputEntryIdsCategoryIdsFile, $separator, $outputPathCsv) {

		$outputCsv = fopen($outputPathCsv, 'w');

		list($linesArray, $count) = $this->entryAndFlavorActions->getAllLinesAndCountFromFile($inputEntryIdsCategoryIdsFile);

		$currentCount         = 0;
		$totalCount           = $count;
		$numberOfProgressBars = ($totalCount < 50) ? $totalCount : 50;
		$progressBarIncrement = ceil($totalCount / $numberOfProgressBars);
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);

		foreach($linesArray as $line) {
			$currentCount++;
			if($currentCount % $progressBarIncrement == 0) {
				$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
			}

			$entryCategoryLine = explode($separator, $line);
			$entryId = $entryCategoryLine[0];
			$categoryId = $entryCategoryLine[1];

			echo 'Adding entry ' . $entryId . ' to category ' . $categoryId . ":\n";
			$categoryEntry = new KalturaCategoryEntry();
			$categoryEntry->entryId = $entryId;
			$categoryEntry->categoryId = $categoryId;

			$successMessage        = 'Entry ' . $entryId . ' was added to category ' . $categoryId . "\n\n";
			$trialsExceededMessage = 'Exceeded number of trials for entry ' . $entryId . '. Moving on to next entry' . "\n\n";
			$this->entryAndFlavorActions->clientObject->doCategoryEntryAdd($categoryEntry, $successMessage, $trialsExceededMessage, 1);
		}
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
		echo "End of entries" . "\n";
		fclose($outputCsv);
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
