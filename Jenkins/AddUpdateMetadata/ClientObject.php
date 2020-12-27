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

		$ks = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 86400, 'disableentitlements');
		$newClient->setKs($ks);

		if($action == "start") {
			echo "Kaltura session (ks) for partner id " . $this->partnerId . " was created successfully: \n" . $newClient->getKs() . "\n\n";
		} else {
			if($action == "refresh") {
				echo "Kaltura session (ks) for partner id " . $this->partnerId . " was refreshed: \n" . $newClient->getKs() . "\n\n";
			}
		}

		$this->client = $newClient;
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
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//retry
			$mediaList = $this->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $mediaList;
	}

	public function doBaseEntryList($baseEntryFilter, $pager, $message, $trialsExceededMessage, $numberOfTrials) {
		/* @var $baseEntryList KalturaBaseEntryListResponse */

		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return $baseEntryList;
		}

		try {
			$baseEntryList = $this->client->baseEntry->listAction($baseEntryFilter, $pager);
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
			$baseEntryList = $this->doBaseEntryList($baseEntryFilter, $pager, $message, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $baseEntryList;
	}

	public function doMetadataList($filter, $trialsExceededMessage, $numberOfTrials) {
		/* @var $metadataList KalturaMetadataListResponse */

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return $metadataList;
		}

		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataList   = $metadataPlugin->metadata->listAction($filter);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return new KalturaMetadataListResponse();

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//new metadata . list
			$metadataList = $this->doMetadataList($filter, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $metadataList;
	}

	public function doMetadataAdd($profileId, $entryId, $dataXML, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return;
		}

		//metadata . add
		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataPlugin->metadata->add($profileId, KalturaMetadataObjectType::ENTRY, $entryId, $dataXML);
			echo $successMessage;

		}  catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			//new metadata.add
			$this->doMetadataAdd($profileId, $entryId, $dataXML, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}
	}

	public function doMetadataUpdate($metadataObjectId, $newXml, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 3) {
			echo $trialsExceededMessage;
			return;
		}

		//metadata . update
		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataPlugin->metadata->update($metadataObjectId, $newXml);
			echo $successMessage;

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			$this->doMetadataUpdate($metadataObjectId, $newXml, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}
	}


}
