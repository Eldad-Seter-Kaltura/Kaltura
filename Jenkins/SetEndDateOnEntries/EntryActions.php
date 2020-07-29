<?php
require_once('ClientObject.php');


class EntryActions
{
	/* @var $clientObject ClientObject */
	public $clientObject;

	private $metadataProfileId;
	private $metadataProfileFieldName;
	private $metadataProfileFieldValue;
	private $createdAtBeforeString;
	private $createdAtAfterString;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $m_metadataProfileId, $m_metadataProfileFieldName, $m_metadataProfileFieldValue, $m_createdAtBeforeString, $m_createdAtAfterString) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClient();

		$this->metadataProfileId         = $m_metadataProfileId;
		$this->metadataProfileFieldName  = $m_metadataProfileFieldName;
		$this->metadataProfileFieldValue = $m_metadataProfileFieldValue;
		$this->createdAtBeforeString     = $m_createdAtBeforeString;
		$this->createdAtAfterString      = $m_createdAtAfterString;
	}

	private function getMetadataProfileFieldNames() {
		//Get the XSD Fields in the correct order (metadataProfile . get)
		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$metadataProfile       = $this->clientObject->doMetadataProfileGet($this->metadataProfileId, $trialsExceededMessage, $firstTry);

		//Get all Element tags from the metadataProfile XSD
		$xsdElement        = new SimpleXMLElement ($metadataProfile->xsd);
		$path              = "/xsd:schema/xsd:element/xsd:complexType/xsd:sequence/xsd:element/@name";
		$fieldNamesElement = $xsdElement->xpath($path);

		$xsdFields = array();
		foreach($fieldNamesElement as $fieldNameElement) {
			$xsdFields[] = strval($fieldNameElement[0]);
		}

		return $xsdFields;
	}

	public function getMetadataProfileFieldNameAndBuildEndDateXml(): array {

		$metadataProfileFieldNames = $this->getMetadataProfileFieldNames();
		if(in_array($this->metadataProfileFieldName, $metadataProfileFieldNames)) {
			$endDate          = DateTime::createFromFormat('Y-m-d H:i', $this->metadataProfileFieldValue);
			$timeStampEndDate = $endDate->getTimestamp();
			echo "Time stamp end date: " . $endDate->format('U = Y-m-d H:i:s') . "\n";

			$xmlEndDate = "<metadata>" . "<" . $this->metadataProfileFieldName . ">" . $timeStampEndDate . "</" . $this->metadataProfileFieldName . ">" . "</metadata>";
			echo 'End date to be added: ' . $xmlEndDate . "\n";
		} else {
			die('Error in field name' . "\n");
		}
		return array($this->metadataProfileId, $this->metadataProfileFieldName, $timeStampEndDate, $xmlEndDate);
	}

	public function definePagerAndFilter() {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter              = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusEqual = KalturaEntryStatus::READY;
		$mediaEntryFilter->orderBy     = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		$dateCreatedAtBefore      = DateTime::createFromFormat('Y-m-d H:i', $this->createdAtBeforeString);
		$timeStampCreatedAtBefore = $dateCreatedAtBefore->getTimestamp();
		echo "Time stamp created at before: " . $dateCreatedAtBefore->format('U = Y-m-d H:i:s') . "\n";

		$dateCreatedAtAfter      = DateTime::createFromFormat('Y-m-d H:i', $this->createdAtAfterString);
		$timeStampCreatedAtAfter = $dateCreatedAtAfter->getTimestamp();
		echo "Time stamp created at after: " . $dateCreatedAtAfter->format('U = Y-m-d H:i:s') . "\n";

		//TODO: createdAt set date
		$mediaEntryFilter->createdAtLessThanOrEqual    = $timeStampCreatedAtBefore;
		$mediaEntryFilter->createdAtGreaterThanOrEqual = $timeStampCreatedAtAfter;

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
