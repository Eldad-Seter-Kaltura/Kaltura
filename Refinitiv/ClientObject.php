<?php
require_once('php7/KalturaClient.php');


class ClientObject
{
	/* @var $client KalturaClient */
	public $client;

	private $hostname;

	private $partnerId;

	private $partnerAdminSecret;

	public function __construct($m_hostname, $m_partnerID, $m_partnerAdminSecret) {
		$this->hostname           = $m_hostname;
		$this->partnerId          = $m_partnerID;
		$this->partnerAdminSecret = $m_partnerAdminSecret;
	}

	public function startClient() {
		$config             = new KalturaConfiguration();
		$config->serviceUrl = $this->hostname;
		$client             = new KalturaClient($config);

		$session = $client->generateSession($this->partnerAdminSecret, NULL, 2, $this->partnerId, 86400, 'disableentitlements');
		$client->setKs($session);
		echo "Kaltura session (ks) for partner id " . $this->partnerId . " was created successfully: \n" . $client->getKs() . "\n";

		$this->client = $client;
	}

	public function doMediaList($mediaEntryFilter, $pager, $message, $numberOfTrials, $outputCsv) {
		/* @var $mediaList KalturaMediaListResponse */

		if($numberOfTrials > 3) {
			fputcsv($outputCsv, array('Exceeded number of trials for this list. Moving on to next list'));
			return $mediaList;
		}

		try {
			$mediaList = $this->client->media->listAction($mediaEntryFilter, $pager);
			if($message) {
				echo $message . $mediaList->totalCount . "\n\n";
			}

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $mediaList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage()  . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$mediaList = $this->doMediaList($mediaEntryFilter, $pager, $message, ++$numberOfTrials, $outputCsv);
		}

		return $mediaList;
	}

	public function doCategoryEntryList($categoryEntryFilter, $numberOfTrials, $outputCsv) {
		/* @var $categoryEntryList KalturaCategoryEntryListResponse */

		if($numberOfTrials > 3) {
			fputcsv($outputCsv, array('Exceeded number of trials for this list. Going back to next list'));
			return $categoryEntryList;
		}

		try {
			$categoryEntryList = $this->client->categoryEntry->listAction($categoryEntryFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $categoryEntryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage()  . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$categoryEntryList = $this->doCategoryEntryList($categoryEntryFilter, ++$numberOfTrials, $outputCsv);
		}

		return $categoryEntryList;
	}

	public function doCategoryGet($categoryId, $numberOfTrials) {
		/* @var $category KalturaCategory */

		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for category ' . $categoryId . '. Moving on to next category' . "\n\n";
			return $category;
		}

		try {
			$category = $this->client->category->get($categoryId);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			$this->doCategoryGet($categoryId, ++$numberOfTrials);
		}

		return $category;
	}

	public function doMediaDelete($entryId, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
			return;
		}

		/* @var $client KalturaClient */
		try {
			$this->client->media->delete($entryId);
			echo 'Entry ' . $entryId . ' was deleted' . "\n\n";
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$this->doMediaDelete($entryId, ++$numberOfTrials);
		}

	}

	public function doFlavorAssetList($flavorAssetFilter, $numberOfTrials, $outputCsv) {
		/* @var $flavorAssetList KalturaFlavorAssetListResponse */

		if($numberOfTrials > 3) {
			fputcsv($outputCsv, array('Exceeded number of trials for this list. Going back to next list'));
			return $flavorAssetList;
		}

		/* @var $client KalturaClient */
		try {
			$flavorAssetList = $this->client->flavorAsset->listAction($flavorAssetFilter);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $flavorAssetList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$flavorAssetList = $this->doFlavorAssetList($flavorAssetFilter, ++$numberOfTrials, $outputCsv);
		}

		return $flavorAssetList;
	}

	public function doFlavorAssetDelete($flavorAssetId, $numberOfTrials) {
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
			$this->resetConnection();
			sleep(3);

			//retry
			$this->doFlavorAssetDelete($flavorAssetId, ++$numberOfTrials);
		}

	}

	public function doFlavorParamsGet($flavorParamId, $numberOfTrials) {
		/* @var $flavorParamObject KalturaFlavorParams */

		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for this flavor. Moving on to next flavor' . "\n\n";
			return $flavorParamObject;
		}

		try {
			$flavorParamObject = $this->client->flavorParams->get($flavorParamId);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$flavorParamObject = $this->doFlavorParamsGet($flavorParamId, ++$numberOfTrials);
		}
		return $flavorParamObject;
	}

	public function resetConnection() {
		$oldConfig = $this->client->getConfig();

		$newClient = new KalturaClient($oldConfig);
		$ks        = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 86400, 'disableentitlements');
		$newClient->setKs($ks);

		$this->client = $newClient;
	}

}
