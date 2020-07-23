<?php
require_once('ClientObject.php');


class EntryActions
{
	private $timeStampCreatedAt;
	private $timeStampEndDate;
	private $metadataProfileId;

	/* @var $clientObject ClientObject */
	public $clientObject;


	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClient();
	}

}
