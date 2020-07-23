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

		$session = $client->generateSession($this->partnerAdminSecret, NULL, KalturaSessionType::ADMIN, $this->partnerId, 86400, 'disableentitlements');
		$client->setKs($session);
		echo "Kaltura session (ks) for partner id " . $this->partnerId . " was created successfully: \n" . $client->getKs() . "\n";

		$this->client = $client;
	}

	public function doMetadataProfileListFields($metadataProfileId, $trialsExceededMessage, $numberOfTrials) {
		/* @var $metadataProfileFieldList KalturaMetadataProfileFieldListResponse */

		if($numberOfTrials > 2) {
			echo $trialsExceededMessage;
			return $metadataProfileFieldList;
		}

		try {
			$metadataPlugin = KalturaMetadataClientPlugin::get($this->client);
			$metadataProfileFieldList   = $metadataPlugin->metadataProfile->listFields($metadataProfileId);

		} catch(KalturaException $apiException) {
			echo $apiException->getMessage() . "\n\n";
			return $metadataProfileFieldList;

		} catch(KalturaClientException $clientException) {
			echo 'Client exception occured. ' . $clientException->getMessage() . "\n\n";
			$this->resetConnection();
			sleep(3);

			//new metadataProfile . list
			$metadataProfileFieldList = $this->doMetadataProfileListFields($metadataProfileId, $trialsExceededMessage, ++$numberOfTrials);
		}

		return $metadataProfileFieldList;
	}

}
