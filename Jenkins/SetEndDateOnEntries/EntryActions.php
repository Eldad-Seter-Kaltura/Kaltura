<?php
require_once('ClientObject.php');


class EntryActions
{
	/* @var $clientObject ClientObject */
	public $clientObject;

	private $metadataProfileId;
	private $metadataProfileFieldName;
	private $timeStampEndDate;
	private $timeStampCreatedAt;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_metadataProfileId, $m_metadataProfileFieldName, $m_timeStampEndDate, $m_timeStampCreatedAt) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClient();

		$this->metadataProfileId  = $m_metadataProfileId;
		$this->metadataProfileFieldName = $m_metadataProfileFieldName;
		$this->timeStampEndDate   = $m_timeStampEndDate;
		$this->timeStampCreatedAt = $m_timeStampCreatedAt;
	}

	public function getMetadataProfileId() {
		return $this->metadataProfileId;
	}

	public function getMetadataProfileFieldName() {
		return $this->metadataProfileFieldName;
	}

	public function getTimeStampEndDate() {
		return $this->timeStampEndDate;
	}

	public function definePagerAndFilter() {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		$mediaEntryFilter->createdAtLessThanOrEqual          = $this->timeStampCreatedAt;

		return array($pager, $mediaEntryFilter);
	}

	public function gettingTypeOfEntry(KalturaMediaEntry $currentEntry) {
		$type = $currentEntry->mediaType;
		switch($type) {
			case KalturaMediaType::VIDEO:
				$type = "VIDEO";
				break;
			case KalturaMediaType::IMAGE:
				$type = "IMAGE";
				break;
			case KalturaMediaType::AUDIO:
				$type = "AUDIO";
				break;
			default:
				$type = "OTHER";
				break;
		}
		return $type;
	}

}
