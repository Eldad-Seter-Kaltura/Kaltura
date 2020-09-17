<?php
require_once('ClientObject.php');


class Actions
{
	/* @var $clientObject ClientObject */
	public $clientObject;

	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClientOrRefreshKsIfNeeded("start");
	}

	public function definePagerAndFilter($type) {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$filter = null;
		switch($type) {
			case "baseEntryFilter":
				$filter              = new KalturaBaseEntryFilter();
				$filter->orderBy     = KalturaBaseEntryOrderBy::CREATED_AT_ASC;
				break;
			case "categoryEntryFilter":
				$filter = new KalturaCategoryEntryFilter();
				$filter->orderBy = KalturaCategoryEntryOrderBy::CREATED_AT_ASC;
				break;
			case "categoryUserFilter":
				$filter = new KalturaCategoryUserFilter();
				break;
			case "categoryFilter":
				$filter = new KalturaCategoryFilter();
				$filter->orderBy = KalturaCategoryOrderBy::CREATED_AT_ASC;
				break;
		}


		return array($pager, $filter);
	}


}
