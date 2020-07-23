<?php
require_once('ClientObject.php');


class EntryActions
{
	/* @var $clientObject ClientObject */
	public $clientObject;

	private $metadataProfileId;
	private $timeStampEndDate;
	private $timeStampCreatedAt;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_metadataProfileId, $m_timeStampEndDate, $m_timeStampCreatedAt) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClient();

		$this->metadataProfileId  = $m_metadataProfileId;
		$this->timeStampEndDate   = $m_timeStampEndDate;
		$this->timeStampCreatedAt = $m_timeStampCreatedAt;
	}

	public function getMetadataProfileId() {
		return $this->metadataProfileId;
	}

	public function getTimeStampEndDate() {
		return $this->timeStampEndDate;
	}

}
