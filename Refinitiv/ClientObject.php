<?php
require_once('php7/KalturaClient.php');


class ClientObject
{
	/* @var $client KalturaClient */
	private $client;

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

	public function doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $mediaList KalturaMediaListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
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
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$mediaList = $this->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $mediaList;
	}

	public function doCategoryEntryList($categoryEntryFilter, $trialsExceededMessage, $numberOfTrials) {
		/* @var $categoryEntryList KalturaCategoryEntryListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $categoryEntryList;
		}

		try {
			$categoryEntryList = $this->client->categoryEntry->listAction($categoryEntryFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $categoryEntryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$categoryEntryList = $this->doCategoryEntryList($categoryEntryFilter, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $categoryEntryList;
	}

	public function doCategoryGet($categoryId, $trialsExceededMessage, $numberOfTrials) {
		/* @var $category KalturaCategory */

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
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

			$this->doCategoryGet($categoryId, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $category;
	}

	public function doMediaDelete($entryId, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return;
		}

		/* @var $client KalturaClient */
		try {
			$this->client->media->delete($entryId);
			echo $successMessage;
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//retry
			$this->doMediaDelete($entryId, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}

	}

	public function doFlavorAssetList($flavorAssetFilter, $trialsExceededMessage, $numberOfTrials) {
		/* @var $flavorAssetList KalturaFlavorAssetListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
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
			$flavorAssetList = $this->doFlavorAssetList($flavorAssetFilter, $trialsExceededMessage, ++$numberOfTrials);
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

	public function doFlavorParamsGet($flavorParamId, $trialsExceededMessage, $numberOfTrials) {
		/* @var $flavorParamObject KalturaFlavorParams */

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
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
			$flavorParamObject = $this->doFlavorParamsGet($flavorParamId, $trialsExceededMessage, ++$numberOfTrials);
		}
		return $flavorParamObject;
	}

	public function doMetadataProfileList($metadataProfileFilter, $numberOfTrials) {
		/* @var $metadataProfileList KalturaMetadataListResponse */

		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for this page. Moving on to next page' . "\n\n";
			return $metadataProfileList;
		}

		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataProfileList   = $metadataPlugin->metadataProfile->listAction($metadataProfileFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $metadataProfileList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//new metadataProfile . list
			$metadataProfileList = $this->doMetadataProfileList($metadataProfileFilter, ++$numberOfTrials);
		}

		return $metadataProfileList;
	}

	public function doScheduledTaskProfileList($scheduledTaskProfileFilter, $numberOfTrials) {
		/* @var $scheduledTaskProfileList KalturaScheduledTaskProfileListResponse */

		if($numberOfTrials > 2) {
			echo 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			return $scheduledTaskProfileList;
		}

		try {
			$scheduledTaskPlugin = KalturaScheduledTaskClientPlugin::get($this->client);
			$scheduledTaskProfileList   = $scheduledTaskPlugin->scheduledTaskProfile->listAction($scheduledTaskProfileFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $scheduledTaskProfileList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//new scheduledTaskProfile . list
			$scheduledTaskProfileList = $this->doScheduledTaskProfileList($scheduledTaskProfileFilter, ++$numberOfTrials);
		}

		return $scheduledTaskProfileList;
	}


	public function doMetadataAdd($profileId, $entryId, $newFieldXML, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return;
		}

		//metadata . add
		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataPlugin->metadata->add($profileId, KalturaMetadataObjectType::ENTRY, $entryId, $newFieldXML);
			echo $successMessage;

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			$this->doMetadataAdd($profileId, $entryId, $newFieldXML, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}
	}


	public function resetConnection() {
		$oldConfig = $this->client->getConfig();

		$newClient = new KalturaClient($oldConfig);
		$ks        = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 86400, 'disableentitlements');
		$newClient->setKs($ks);

		$this->client = $newClient;
	}

}
