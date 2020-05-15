<?php


class ProdObject
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

	public function printAllFlavorsGoingToBeDeleted($client, $timestampThreeYearsAgo, $outputPathCsv) {
		$outputCsv = fopen($outputPathCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'CategoriesFullName', 'FlavorNames', 'CreatedAt', 'UpdatedAt', 'LastPlayedAt'));


		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		$mediaEntryFilter->createdAtLessThanOrEqual          = $timestampThreeYearsAgo;
		$mediaEntryFilter->updatedAtLessThanOrEqual          = $timestampThreeYearsAgo;
		$mediaEntryFilter->lastPlayedAtLessThanOrEqualOrNull = $timestampThreeYearsAgo;

		$flavorAssetFilter              = new KalturaFlavorAssetFilter();
		$flavorAssetFilter->statusEqual = KalturaFlavorAssetStatus::READY;

		$flavorParamsKeyToValueArrayRule5 = array();

		//"advance knowledge" from customer KMC -> Settings -> Transcoding (otherwise- api calls)

		$flavorParamsKeyToValueArrayRule5[1248522] = "SD/Small - WEB/MBL (H264/900)-MONO";
		$flavorParamsKeyToValueArrayRule5[487061]  = "SD/Small - WEB/MBL (H264/900)";
		$flavorParamsKeyToValueArrayRule5[1248502] = "Basic/Small - WEB/MBL (H264/400)-MONO";
		$flavorParamsKeyToValueArrayRule5[487041]  = "Basic/Small - WEB/MBL (H264/400)";
		$flavorParamsKeyToValueArrayRule5[2027202] = "Audio Only - English (Mono) (128 Kbps) (Download)";

		$this->printAllFlavorsRule5($mediaEntryFilter, $pager, $this->categoryArray, $flavorAssetFilter, $flavorParamsKeyToValueArrayRule5, $client, $outputCsv);

		$flavorParamsKeyToValueArrayOtherRules = array();

		$flavorParamsKeyToValueArrayOtherRules[1248532] = "SD/Large - WEB/MBL (H264/1500)-MONO";
		$flavorParamsKeyToValueArrayOtherRules[487071]  = "SD/Large - WEB/MBL (H264/1500)";
		$flavorParamsKeyToValueArrayOtherRules[1248502] = "Basic/Small - WEB/MBL (H264/400)-MONO";
		$flavorParamsKeyToValueArrayOtherRules[487041]  = "Basic/Small - WEB/MBL (H264/400)";
		$flavorParamsKeyToValueArrayOtherRules[2027202] = "Audio Only - English (Mono) (128 Kbps) (Download)";

		for($i = 0; $i < count($this->categoryArray); $i++) {
			$this->printAllFlavorsRule($i + 6, $mediaEntryFilter, $pager, $this->categoryArray[$i], $flavorAssetFilter, $flavorParamsKeyToValueArrayOtherRules, $client, $outputCsv);
		}

	}

	private function printAllFlavorsRule5($mediaEntryFilter, $pager, $categoryArray, $flavorAssetFilter, $flavorParamsArray, $client, $outputCsv) {

		$categoryString                             = implode(",", $categoryArray);
		$mediaEntryFilter->categoriesIdsNotContains = $categoryString;

		$this->printAllFlavors($mediaEntryFilter, $pager, $flavorAssetFilter, $flavorParamsArray, $client, $outputCsv);

	}

	private function printAllFlavorsRule($number, $mediaEntryFilter, $pager, $categoryId, $flavorAssetFilter, $flavorParamsArray, $client, $outputCsv) {

		echo 'Printing all flavors of rule ' . $number . "\n\n";

		$mediaEntryFilter->categoriesIdsMatchAnd = $categoryId;     //TODO: is this the right filter?
		$this->printAllFlavors($mediaEntryFilter, $pager, $flavorAssetFilter, $flavorParamsArray, $client, $outputCsv);

	}

	private function printAllFlavors($mediaEntryFilter, $pager, $flavorAssetFilter, $flavorParamsArray, $client, $outputCsv) {

		try {
			$mediaList = $client->media->listAction($mediaEntryFilter, $pager);
			echo 'Total number of entries: ' . $mediaList->totalCount . "\n\n";
		} catch(Exception $e) {
			die($e->getMessage());
		}

		while(count($mediaList->objects)) {
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

				$categoryEntryFilter               = new KalturaCategoryEntryFilter();
				$categoryEntryFilter->entryIdEqual = $currentEntry->id;
				$categoryEntryList                 = $this->doCategoryEntryList($categoryEntryFilter, $client, 1, $outputCsv);   //TODO: can return multiple results (categories)

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

				//getting flavors..

				$flavorAssetFilter->entryIdEqual = $currentEntry->id;

				$flavorAssetList = $this->doFlavorAssetList($flavorAssetFilter, $client, 1, $outputCsv);

				$flavorParamsRule = array_keys($flavorParamsArray);

				$flavorAssetArrayWithoutSrcAndOthers = array();
				/* @var $flavorAsset KalturaFlavorAsset */
				foreach($flavorAssetList->objects as $flavorAsset) {
					$flavorParam = (int)($flavorAsset->flavorParamsId);
					if($flavorParam != 0) {
						$isEqual = FALSE;
						foreach($flavorParamsRule as $flavorParamRule) {
							if($flavorParam == $flavorParamRule) {
								$isEqual = TRUE;
							}
						}
						//flavorparam != all of those
						if(!$isEqual) {
							//key-value
							$flavorAssetArrayWithoutSrcAndOthers[$flavorAsset->id] = $flavorAsset;
						}
					}
				}

				//get only the IDs
				$flavorAssetIds  = array_map(function ($object) {
					return $object->id;
				}, $flavorAssetArrayWithoutSrcAndOthers);
				$flavorsToDelete = implode(" ", $flavorAssetIds);

				//now we're ready to delete
				foreach($flavorAssetIds as $flavorAssetId) {
					//$this->doFlavorAssetDelete($flavorAssetId, $client, 1);
				}

				//get params ids for printing
				$flavorParamsIds = array_map(function ($object) {
					return $object->flavorParamsId;
				},
					$flavorAssetArrayWithoutSrcAndOthers);

				$flavorNamesArray = array();
				foreach($flavorParamsIds as $flavorIdToDelete) {
					$flavorNamesArray[] = $flavorParamsArray[$flavorIdToDelete];
				}
				$flavorNamesToDelete = implode(",", $flavorNamesArray);

				//printing..
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $categoriesFullNameString, $flavorNamesToDelete, $currentEntry->createdAt, $currentEntry->updatedAt,
					$currentEntry->lastPlayedAt));

			}

			fputcsv($outputCsv, array('=====', '=====', '=====', '=====', '=====', '====='));

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$this->doMediaList($mediaEntryFilter, $pager, $client, 1, $outputCsv);

		}
	}


	private function doMediaList($mediaEntryFilter, $pager, $client, $numberOfTrials, $outputCsv) {
		/* @var $mediaList KalturaMediaListResponse */

		if($numberOfTrials > 3) {
			fputcsv($outputCsv, array('Exceeded number of trials for this list. Moving on to next list'));
			return $mediaList;
		}

		/* @var $client KalturaClient */
		try {
			$mediaList = $client->media->listAction($mediaEntryFilter, $pager);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $mediaList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
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

		/* @var $client KalturaClient */
		try {
			$categoryEntryList = $client->categoryEntry->listAction($categoryEntryFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $categoryEntryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
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

		/* @var $client KalturaClient */
		try {
			$category = $client->category->get($categoryId);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$newClient = $this->resetConnection($client);
			sleep(3);

			//retry
			$this->doCategoryGet($categoryId, $newClient, ++$numberOfTrials);
		}

		return $category;
	}

	private function doFlavorAssetList($flavorAssetFilter, $client, $numberOfTrials, $outputCsv) {
		/* @var $flavorAssetList KalturaFlavorAssetListResponse */

		if($numberOfTrials > 3) {
			fputcsv($outputCsv, array('Exceeded number of trials for this list. Going back to next list'));
			return $flavorAssetList;
		}

		/* @var $client KalturaClient */
		try {
			$flavorAssetList = $client->flavorAsset->listAction($flavorAssetFilter);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $flavorAssetList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$newClient = $this->resetConnection($client);
			sleep(3);

			//retry
			$flavorAssetList = $this->doFlavorAssetList($flavorAssetFilter, $newClient, ++$numberOfTrials, $outputCsv);
		}

		return $flavorAssetList;
	}

	private function doFlavorAssetDelete($flavorAssetId, $client, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for this flavor. Moving on to next flavor' . "\n\n";
			return;
		}

		/* @var $client KalturaClient */
		try {
			$client->flavorAsset->delete($flavorAssetId);
			echo 'Flavor asset ' . $flavorAssetId . ' was deleted' . "\n\n";
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$newClient = $this->resetConnection($client);
			sleep(3);

			//retry
			$this->doFlavorAssetDelete($flavorAssetId, $newClient, ++$numberOfTrials);
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
