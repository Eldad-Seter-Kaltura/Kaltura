<?php
require_once('ClientObject.php');


class EntryActions
{
	private $timeStampCreatedAt;
	private $timeStampEndDate;
	private $metadataProfileId;

	public ClientObject $clientObject;


	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClient();
	}

}
