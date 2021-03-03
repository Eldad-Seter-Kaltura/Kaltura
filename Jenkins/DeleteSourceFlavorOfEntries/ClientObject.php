<?php
require_once('KalturaGeneratedAPIClientsPHP/KalturaClient.php');


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

	public function startClientOrRefreshKsIfNeeded($action) {
		$config             = new KalturaConfiguration();
		$config->serviceUrl = $this->hostname;

		$newClient = new KalturaClient($config);
		$newClient->setClientTag("kmcng");
		$session = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 7 * 86400, 'all:*,disableentitlement');
		$newClient->setKs($session);

		if($action == "start") {
			echo "Kaltura session (ks) for partner id " . $this->partnerId . " was created successfully: \n" . $newClient->getKs() . "\n\n";
		} else {
			if($action == "refresh") {
				echo "Kaltura session (ks) for partner id " . $this->partnerId . " was refreshed: \n" . $newClient->getKs() . "\n\n";
			}
		}

		$this->client = $newClient;
	}


	public function doBaseEntryList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $baseEntryList KalturaBaseEntryListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $baseEntryList;
		}

		try {
			$baseEntryList = $this->client->baseEntry->listAction($mediaEntryFilter, $pager);
			if($message) {
				echo $message . $baseEntryList->totalCount . "\n\n";
			}

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $baseEntryList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$baseEntryList = $this->doBaseEntryList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $baseEntryList;
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
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$flavorAssetList = $this->doFlavorAssetList($flavorAssetFilter, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $flavorAssetList;
	}

	public function doFlavorAssetDelete($flavorAssetId, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return;
		}

		/* @var $client KalturaClient */
		try {
			$this->client->flavorAsset->delete($flavorAssetId);
			echo $successMessage;
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$this->doFlavorAssetDelete($flavorAssetId, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}

	}


	public function doMetadataProfileList($metadataProfileFilter, $trialsExceededMessage, $numberOfTrials) {
		/* @var $metadataProfileList KalturaMetadataListResponse */

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return $metadataProfileList;
		}

		try {
			$metadataPlugin      = KalturaMetadataClientPlugin::get($this->client);
			$metadataProfileList = $metadataPlugin->metadataProfile->listAction($metadataProfileFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $metadataProfileList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//new metadataProfile . list
			$metadataProfileList = $this->doMetadataProfileList($metadataProfileFilter, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $metadataProfileList;
	}

	public function doScheduledTaskProfileList($scheduledTaskProfileFilter, $trialsExceededMessage, $numberOfTrials) {
		/* @var $scheduledTaskProfileList KalturaScheduledTaskProfileListResponse */

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return $scheduledTaskProfileList;
		}

		try {
			$scheduledTaskPlugin      = KalturaScheduledTaskClientPlugin::get($this->client);
			$scheduledTaskProfileList = $scheduledTaskPlugin->scheduledTaskProfile->listAction($scheduledTaskProfileFilter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $scheduledTaskProfileList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//new scheduledTaskProfile . list
			$scheduledTaskProfileList = $this->doScheduledTaskProfileList($scheduledTaskProfileFilter, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $scheduledTaskProfileList;
	}

	public function doMetadataAdd($profileId, $entryId, $xml, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return;
		}

		//metadata . add
		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataPlugin->metadata->add($profileId, KalturaMetadataObjectType::ENTRY, $entryId, $xml);
			echo $successMessage;

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			$this->doMetadataAdd($profileId, $entryId, $xml, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}
	}

}
