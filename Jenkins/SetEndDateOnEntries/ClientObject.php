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

	public function startClient() {
		$config             = new KalturaConfiguration();
		$config->serviceUrl = $this->hostname;
		$client             = new KalturaClient($config);

		$session = $client->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 2*86400, 'disableentitlements');
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

	public function doMetadataProfileGet($metadataProfileId, $trialsExceededMessage, $numberOfTrials) {
		$metadataProfile = new KalturaMetadataProfile();

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return $metadataProfile;
		}

		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataProfile   = $metadataPlugin->metadataProfile->get($metadataProfileId);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $metadataProfile;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//new metadataProfile . get
			$metadataProfile = $this->doMetadataProfileGet($metadataProfileId, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $metadataProfile;
	}

	public function doMetadataAdd($profileId, $entryId, $xmlData, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return;
		}

		//metadata . add
		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataPlugin->metadata->add($profileId, KalturaMetadataObjectType::ENTRY, $entryId, $xmlData);
			echo $successMessage;

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			$this->doMetadataAdd($profileId, $entryId, $xmlData, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}
	}

	public function resetConnection() {
		$oldConfig = $this->client->getConfig();
		$newClient = new KalturaClient($oldConfig);

		$ks        = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 2*86400, 'disableentitlements');
		$newClient->setKs($ks);

		$this->client = $newClient;
	}

}
