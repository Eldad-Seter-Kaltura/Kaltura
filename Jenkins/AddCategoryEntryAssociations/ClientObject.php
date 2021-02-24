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
		$session = $newClient->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 7 * 86400, 'disableentitlements');
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

	public function doCategoryEntryAdd($categoryEntry, $successMessage, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return;
		}

		//categoryEntry . add
		/* @var $client KalturaClient */
		try {
			$this->client->categoryEntry->add($categoryEntry);
			echo $successMessage;

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->startClientOrRefreshKsIfNeeded("refresh");
			sleep(3);

			$this->doCategoryEntryAdd($categoryEntry, $successMessage, $trialsExceededMessage, ++$numberOfTrials);
		}
	}


}
