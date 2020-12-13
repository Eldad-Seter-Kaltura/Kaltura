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

		$session = $client->generateSession($this->partnerAdminSecret, NULL, 2, $this->partnerId, 86400, 'disableentitlements');
		$client->setKs($session);
		echo "Kaltura session (ks) for partner id " . $this->partnerId . " was created successfully: \n" . $client->getKs() . "\n";

		$this->client = $client;
	}

	public function doMediaGet($entryId, $trialsExceededMessage, $numberOfTrials) {
		$mediaEntry = new KalturaMediaEntry();

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return $mediaEntry;
		}

		try {
			$mediaEntry = $this->client->media->get($entryId);
		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			$mediaEntry = $this->doMediaGet($entryId, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $mediaEntry;
	}

	public function doMediaUpdate($entryId, $mediaEntry, $successMessage, $dataArray, $outCsv, $trialsExceededMessage, $numberOfTrials) {
		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return;
		}

		/* @var $client KalturaClient */
		try {
			$this->client->media->update($entryId, $mediaEntry);
			echo $successMessage;
			fputcsv($outCsv, $dataArray);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";

		} catch(KalturaClientException $e) {
			echo $e->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			$this->doMediaUpdate($entryId, $mediaEntry, $successMessage, $dataArray, $outCsv, $trialsExceededMessage, ++$numberOfTrials);
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
