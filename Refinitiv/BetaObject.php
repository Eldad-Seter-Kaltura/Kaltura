<?php
require_once('php7/KalturaClient.php');


class BetaObject
{
	private $hostname;

	private $partnerId;

	private $partnerAdminSecret;

	private $categoryArray;

	public function __construct($m_hostname, $m_partnerID, $m_partnerAdminSecret, $m_categoryArray) {
		$this->hostname           = $m_hostname;
		$this->partnerId          = $m_partnerID;
		$this->partnerAdminSecret = $m_partnerAdminSecret;
		$this->categoryArray      = $m_categoryArray;
	}

	public function startClient() {
		$config             = new KalturaConfiguration();
		$config->serviceUrl = $this->hostname;
		$client             = new KalturaClient($config);

		$session = $client->generateSession($this->partnerAdminSecret, NULL, 2, $this->partnerId, 86400, 'disableentitlements');
		$client->setKs($session);
		echo "Kaltura session (ks) for partner id " . $this->partnerId . " was created successfully: \n" . $client->getKs() . "\n";

		return $client;
	}

	public function printAllEntriesGoingToBeDeleted($client, $timestampSixMonthsAgo, $outputPathCsv) {

		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));


		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		$mediaEntryFilter->createdAtLessThanOrEqual          = $timestampSixMonthsAgo;
		$mediaEntryFilter->updatedAtLessThanOrEqual          = $timestampSixMonthsAgo;
		$mediaEntryFilter->lastPlayedAtLessThanOrEqualOrNull = $timestampSixMonthsAgo;

		$mediaEntryFilter->mediaTypeIn = KalturaMediaType::VIDEO . "," . KalturaMediaType::IMAGE . "," . KalturaMediaType::AUDIO;

		$mediaEntryFilter->categoriesIdsNotContains = $this->categoryArray[0] . "," . $this->categoryArray[1];

		/* @var $client KalturaClient */

		try {
			$mediaList = $client->media->listAction($mediaEntryFilter, $pager);
			echo 'Total number of entries: ' . $mediaList->totalCount . "\n\n";
		} catch(Exception $e) {
			die($e->getMessage());
		}

		while(count($mediaList->objects)) {

			//FOR EACH ENTRY
			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {
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

				//getting categories of entry..

				$categoryEntryFilter = new KalturaCategoryEntryFilter();
				$categoryEntryFilter->entryIdEqual = $currentEntry->id;
				$categoryEntryList = $this->doCategoryEntryList($categoryEntryFilter, $client, 1, $outputCsv);   //TODO: can return multiple results (categories)

				$categoriesOfEntry = array();
				if(count($categoryEntryList->objects)) {
					foreach($categoryEntryList->objects as $categoryEntry) {
						/* @var $categoryEntry KalturaCategoryEntry */
						$categoriesOfEntry[] = $categoryEntry->categoryId;
					}
				}
//				$categoriesOfEntryString = implode(",", $categoriesOfEntry);

				$categoriesFullName = array();
				foreach($categoriesOfEntry as $categoryId) {
					$category = $this->doCategoryGet($categoryId, $client, 1);
					if($category) {
						$categoriesFullName[] = $category->fullName;
					}
					//TODO: is there a better way to do this?
				}
				$categoriesFullNameString = implode(",", $categoriesFullName);

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $categoriesFullNameString, $currentEntry->createdAt, $currentEntry->updatedAt, $currentEntry->lastPlayedAt));

				//now can delete entry-
				//$this->doMediaDelete($currentEntry->id, $client, 1);
			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));


			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$mediaList = $this->doMediaList($mediaEntryFilter, $pager, $client, 1, $outputCsv);

		}

	}


	private function doMediaList($mediaEntryFilter, $pager, $client, $numberOfTrials, $outputCsv) {
		/* @var $mediaList KalturaMediaListResponse */

		if($numberOfTrials > 3) {
			fputcsv($outputCsv, array('Exceeded number of trials for this list. Moving on to next list'));
			return $mediaList;
		}

		/* @var $client KalturaClient*/
		try {
			$mediaList = $client->media->listAction($mediaEntryFilter, $pager);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $mediaList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage()  . "\n\n";
			$newClient = $this->resetConnection($client);
			sleep(3);

			//retry
			$mediaList = $this->doMediaList($mediaEntryFilter, $pager, $newClient, ++$numberOfTrials, $outputCsv);
		}

		return $mediaList;
	}

	private function doCategoryEntryList($categoryEntryFilter, $client, $numberOfTrials, $outputCsv) {
		/* @var $categoryEntryList KalturaCategoryEntryListResponse */

		if($numberOfTrials > 3) {
			fputcsv($outputCsv, array('Exceeded number of trials for this list. Going back to next list'));
			return $categoryEntryList;
		}

		/* @var $client KalturaClient*/
		try {
			$categoryEntryList = $client->categoryEntry->listAction($categoryEntryFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $categoryEntryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage()  . "\n\n";
			$newClient = $this->resetConnection($client);
			sleep(3);

			//retry
			$categoryEntryList = $this->doCategoryEntryList($categoryEntryFilter, $newClient, ++$numberOfTrials, $outputCsv);
		}

		return $categoryEntryList;
	}

	private function doCategoryGet($categoryId, $client, $numberOfTrials) {
		/* @var $category KalturaCategory */

		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for category ' . $categoryId . '. Moving on to next category' . "\n\n";
			return $category;
		}

		try {
			$category = $client->category->get($categoryId);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$newClient = $this->resetConnection($client);
			sleep(3);

			$this->doCategoryGet($categoryId, $newClient, ++$numberOfTrials);
		}

		return $category;
	}

	private function doMediaDelete($entryId, $client, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
			return;
		}

		/* @var $client KalturaClient */
		try {
			$client->media->delete($entryId);
			echo 'Entry ' . $entryId . ' was deleted' . "\n\n";
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$newClient = $this->resetConnection($client);
			sleep(3);

			//retry
			$this->doMediaDelete($entryId, $newClient, ++$numberOfTrials);
		}

	}


	private function resetConnection($oldClient) {
		/* @var $oldClient KalturaClient */
		$oldConfig = $oldClient->getConfig();

		$newClient = new KalturaClient($oldConfig);
		$ks        = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 86400, 'disableentitlements');
		$newClient->setKs($ks);

		return $newClient;
	}

}
