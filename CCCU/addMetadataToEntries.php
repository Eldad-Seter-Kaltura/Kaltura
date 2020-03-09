<?php
require_once('php5/KalturaClient.php');

class MetadataToEntries {

	public $hostname;
	public $partnerId;
	public $partnerAdminSecret;
	public $metadataProfileId;
	public $lastCreatedAt;


	function __construct($mhostname, $mpartnerId, $mpartnerAdminSecret, $mmetadataProfileId, $mlastCreatedAt = null)
	{
		$this->hostname = $mhostname;
		$this->partnerId = $mpartnerId;
		$this->partnerAdminSecret = $mpartnerAdminSecret;
		$this->metadataProfileId = $mmetadataProfileId;
		$this->lastCreatedAt = $mlastCreatedAt;
		echo "last created at: " . $mlastCreatedAt . "\n";
	}

	public function add () {

		$config = new KalturaConfiguration();
		$config->serviceUrl = $this->hostname;
		$client = new KalturaClient($config);

		$session = $client->generateSessionV2($this->partnerAdminSecret, null, 2, $this->partnerId, 86400, '*,disableentitlement');
		$client->setKs($session);
		echo "Kaltura session (ks) was generated successfully: " . $session . "\n";

		$metadataPlugin = KalturaMetadataClientPlugin::get($client);
		$pager = new KalturaFilterPager();
		$pager->pageSize = 500;
		$pager->pageIndex = 1;

		$filter              = new KalturaMediaEntryFilter();
		$filter->statusEqual = KalturaEntryStatus::READY;
		$filter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		if ($this->lastCreatedAt) {
			$filter->createdAtGreaterThanOrEqual = $this->lastCreatedAt;
		}

		$csvfile = fopen('CCCU.csv', 'w');
		fputcsv($csvfile, array("EntryID", "Name", "CreatedAt"));
		
		$existingEntries = $client->media->listAction($filter, $pager);
		echo "Total no. of objects is: " . $existingEntries->totalCount . "\n";

		while (count($existingEntries->objects)) {

			/* @var $entry KalturaMediaEntry */
			foreach ($existingEntries->objects as $entry) {

				try {
					$metadataPlugin->metadata->add($this->metadataProfileId, KalturaMetadataObjectType::ENTRY, $entry->id, file_get_contents('schedulingEndDate.xml'));
				}
				catch (Exception $e)
				{
					var_dump ('Error occurred when attempting to add metadata for entry [' . $entry->id . ']');
				}
				fputcsv($csvfile, array($entry->id, $entry->name, $entry->createdAt));
			}
			fputcsv($csvfile, array("====", "====", "===="));
			echo "page is: " . $pager->pageIndex . "\n";
			var_dump('Last handled createdAt: ' . $entry->createdAt);

			$pager->pageIndex++;
			$existingEntries = $client->media->listAction($filter, $pager);

		}

		fclose($csvfile);
		return $entry->createdAt;
	}

}
