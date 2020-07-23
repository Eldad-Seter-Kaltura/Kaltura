<?php
require_once('EntryActions.php');


class PopulateEndDateObject
{
	/* @var $entryActions EntryActions */
	private $entryActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->entryActions = new EntryActions($serviceUrl, $partnerId, $adminSecret);
	}

}
