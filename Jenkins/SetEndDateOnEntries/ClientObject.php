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

}
