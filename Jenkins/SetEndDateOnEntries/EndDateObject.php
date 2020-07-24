<?php
require_once('EntryActions.php');


class EndDateObject
{
	/* @var $entryActions EntryActions */
	private $entryActions;

	public function __construct($serviceUrl, $partnerId, $adminSecret, $metadataProfileId, $metadataProfileFieldName, $timeStampEndDate, $timeStampCreatedAt) {
		$this->entryActions = new EntryActions($serviceUrl, $partnerId, $adminSecret, $metadataProfileId, $metadataProfileFieldName, $timeStampEndDate, $timeStampCreatedAt);
	}

	public function doDryRun($outputCsvPath) {
		$outputCsv = fopen($outputCsvPath, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'OwnerID', 'CreatedAt'));

		//1. get metadata profile field name & build end date xml
		$metadataProfileId        = $this->entryActions->getMetadataProfileId();
		$metadataProfileFieldName = $this->entryActions->getMetadataProfileFieldName();

		$metadataProfileFieldNames = $this->getMetadataProfileFieldNames($metadataProfileId);
		if(in_array($metadataProfileFieldName, $metadataProfileFieldNames)) {
			$timeStampEndDate = $this->entryActions->getTimeStampEndDate();
			$xmlEndDate       = "<metadata>" . "<" . $metadataProfileFieldName . ">" . $timeStampEndDate . "</" . $metadataProfileFieldName . ">" . "</metadata>";
			echo 'End date to be added: ' . $xmlEndDate . "\n";
		} else {
			die('Error in field name' . "\n");
		}

		//2. print all entries affected

		list($pager, $mediaEntryFilter) = $this->entryActions->definePagerAndFilter();

		$firstTry              = 1;
		$message               = 'Total number of entries: ';
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$mediaList             = $this->entryActions->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMessage, $firstTry);

		$i = 0;
		while(count($mediaList->objects)) {
			echo "Beginning of page: " . ++$i . "\n";
			echo "Count: " . count($mediaList->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaList->objects as $currentEntry) {
				$currentEntryMediaType = $this->entryActions->gettingTypeOfEntry($currentEntry);
				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $currentEntryMediaType, $currentEntry->userId, $currentEntry->createdAt));
			}

			echo "Last entry: " . $currentEntry->id . "\n";
			echo "End of page\n\n";

			//media . list - next iterations
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;
			$mediaList                                     = $this->entryActions->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMessage, $firstTry);
		}

		fclose($outputCsv);
		echo 'End of dry run' . "\n";
	}

	private function getMetadataProfileFieldNames($metadataProfileId) {
		//Get the XSD Fields in the correct order (metadataProfile . get)
		$firstTry              = 1;
		$trialsExceededMessage = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$metadataProfile       = $this->entryActions->clientObject->doMetadataProfileGet($metadataProfileId, $trialsExceededMessage, $firstTry);

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

}
