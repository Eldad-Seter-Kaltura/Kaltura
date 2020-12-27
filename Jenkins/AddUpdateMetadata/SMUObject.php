<?php
require_once('ClientObject.php');


class SMUObject
{
	/* @var $clientObject ClientObject */
	private $clientObject;


	public function __construct($serviceUrl, $partnerId, $adminSecret) {
		$this->clientObject = new ClientObject($serviceUrl, $partnerId, $adminSecret);
		$this->clientObject->startClientOrRefreshKsIfNeeded("start");
	}


	public function getFirstColumnFromCsvFile($inputFile): array {
		$outputArray = array();
		$inputHandle = fopen($inputFile, 'r');
		while($line = fgetcsv($inputHandle)) {
			$outputArray [] = $line[0];
		}
		fclose($inputHandle);
		return $outputArray;
	}

	public function definePagerAndFilterForDryRun() {
		$pager            = new KalturaFilterPager();
		$pager->pageSize  = 500;
		$pager->pageIndex = 1;         // Always getting first page by createdAt (10k handling)

		$mediaEntryFilter                 = new KalturaMediaEntryFilter();
		$mediaEntryFilter->statusIn       = KalturaEntryStatus::READY . "," . KalturaEntryStatus::IMPORT . "," . KalturaEntryStatus::PRECONVERT . "," . KalturaEntryStatus::PENDING . "," . KalturaEntryStatus::NO_CONTENT . "," . KalturaEntryStatus::ERROR_CONVERTING . "," . KalturaEntryStatus::ERROR_IMPORTING;
		$mediaEntryFilter->mediaTypeEqual = KalturaMediaType::VIDEO;
		$mediaEntryFilter->orderBy        = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

		return array($pager, $mediaEntryFilter);
	}

	public function gettingTypeOfEntry($mediaType) {
		switch($mediaType) {
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

	public function gettingStatusOfEntry($mediaStatus) {
		switch($mediaStatus) {
			case KalturaEntryStatus::READY:
				$status = "READY";
				break;
			case KalturaEntryStatus::IMPORT:
				$status = "IMPORT";
				break;
			case KalturaEntryStatus::PRECONVERT:
				$status = "PRECONVERT";
				break;
			case KalturaEntryStatus::PENDING:
				$status = "PENDING";
				break;
			case KalturaEntryStatus::NO_CONTENT:
				$status = "NO_CONTENT";
				break;
			case KalturaEntryStatus::ERROR_CONVERTING:
				$status = "ERROR_CONVERTING";
				break;
			case KalturaEntryStatus::ERROR_IMPORTING:
				$status = "ERROR_IMPORTING";
				break;
			default:
				$status = "OTHER";
				break;
		}
		return $status;
	}

	public function doDryRun($arrayLMSUsers, $consoleOutputCsv) {
		$outputCsv = fopen($consoleOutputCsv, 'w');
		fputcsv($outputCsv, array('EntryID', 'Name', 'MediaType', 'Status', 'ClassroomRecordings', 'Tags', 'LMSUser', 'UserID', 'CreatedAt'));

		list($pager, $mediaEntryFilter) = $this->definePagerAndFilterForDryRun();

		$message           = 'Total number of entries: ';
		$trialsExceededMsg = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
		$mediaListResponse = $this->clientObject->doMediaList($mediaEntryFilter, $pager, $message, $trialsExceededMsg, 1);

		$i = 0;
		while(count($mediaListResponse->objects)) {
			echo "Beginning of page: " . ++$i . "\n";
			echo "Count: " . count($mediaListResponse->objects) . "\n\n";

			/* @var $currentEntry KalturaMediaEntry */
			foreach($mediaListResponse->objects as $currentEntry) {
				$type   = $this->gettingTypeOfEntry($currentEntry->mediaType);
				$status = $this->gettingStatusOfEntry($currentEntry->status);

				$tags = explode(",", $currentEntry->tags);
				$hasTag = in_array("classroom_recordings", $tags);

				if($hasTag) {
					echo 'Entry ' . $currentEntry->id . ' has tag ' . 'classroom_recordings!' . "\n\n";
				}

				$isLMSUser = in_array(strtoupper($currentEntry->userId), $arrayLMSUsers) || in_array(strtolower($currentEntry->userId), $arrayLMSUsers);

				fputcsv($outputCsv, array($currentEntry->id, $currentEntry->name, $type, $status, $hasTag ? "y" : "n", $currentEntry->tags, $isLMSUser ? "y" : "n", $currentEntry->userId, $currentEntry->createdAt));
			}

			//media . list - next page ( = iteration)
			$mediaEntryFilter->createdAtGreaterThanOrEqual = $currentEntry->createdAt + 1;

			$trialsExceededMsg = 'Exceeded number of trials for this list. Moving on to next list' . "\n\n";
			$mediaListResponse = $this->clientObject->doMediaList($mediaEntryFilter, $pager, "", $trialsExceededMsg, 1);
		}
		fclose($outputCsv);
	}

	public function markLegacyContent($entryIdCsv, $inputFormatXml) {

		$targetMetadataProfileId = 5794461;     //EntryAdditionalInfo- Detail

		$keyFieldName       = "Key";
		$valueFieldName     = "Value";
		$detailFieldName    = "Detail";
		$instanceIdKey      = "InstanceId";
		$instanceIdValueLMS = 1057192;
		$instanceIdValueKMS = 505521;

		$fp = file($entryIdCsv);
		$currentCount         = 0;
		$totalCount = count($fp);
		$numberOfProgressBars = ($totalCount < 50) ? $totalCount : 50;
		$progressBarIncrement = ceil($totalCount / $numberOfProgressBars);
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);

		$entryIdCsvHandle = fopen($entryIdCsv, 'r');
		fgetcsv($entryIdCsvHandle);

		while($line = fgetcsv($entryIdCsvHandle)) {
			$currentCount++;
			if($currentCount % $progressBarIncrement == 0) {
				$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
			}

			$entryId = $line[0];
			$hasTag = $line[1];
			$isLMSUser = $line[2];
			$userId = $line[3];

			if($hasTag == "y") {
				echo 'Entry ' . $entryId . ' has tag ' . 'classroom_recordings - skipped!' . "\n\n";
				continue;
			}

			echo "This entry " . $entryId . " doesn't have tag classroom_recordings-\n";

			$metadataFilter                          = new KalturaMetadataFilter();
			$metadataFilter->objectIdEqual           = $entryId;
			$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
			$metadataFilter->metadataProfileIdEqual  = $targetMetadataProfileId;

			$trialsExceededMsg    = 'Exceeded number of trials for this entry. Moving on to next entry' . "\n\n";
			$metadataListResponse = $this->clientObject->doMetadataList($metadataFilter, $trialsExceededMsg, 1);
			if(!isset($metadataListResponse->totalCount)) {
				echo 'Error occurred for entry ' . $entryId . ' , probably exception- skipping to next entry-' . "\n";
				continue;
			}

			if($metadataListResponse->totalCount == 1) {
				//TODO: already has metadata, therefore Update
				/* @var $metadataObject KalturaMetadata */
				$metadataObject         = $metadataListResponse->objects[0];
				$oldXml                 = $metadataObject->xml;
				$targetMetadataObjectId = $metadataObject->id;

				if(strpos($oldXml, "InstanceId") !== FALSE) {
					echo 'Entry ' . $entryId . ' already has Instance Id! Skipping' . "\n\n";
					continue;
				}
				$oldXmlFormatted = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $oldXml)));

				echo "This entry " . $entryId . " doesn't have InstanceID, but already has metadata on Additional Info-\n";


				//TODO: update in the *Correct* way, (the xml)

				$newDoc = new DOMDocument();
				$newDoc->loadXML($inputFormatXml);    //shablona

				if($isLMSUser == "y") {
					echo 'This entry ' . $entryId . ' has user- ' . $userId . ' -in LMS list:' . "\n";
					echo 'Adding ' . '<Detail>' . '<Key>' . $instanceIdKey . '</Key>' .
						'<Value>' . $instanceIdValueLMS . '</Value>' . '</Detail>' . ' to XML' . "\n";

					$instanceIdValue = $instanceIdValueLMS;

				} else {
					echo 'This entry ' . $entryId . ' has user- ' . $userId . ' -not in LMS list:' . "\n";
					echo 'Adding ' . '<Detail>' . '<Key>' . $instanceIdKey . '</Key>' .
						'<Value>' . $instanceIdValueKMS . '</Value>' . '</Detail>' . ' to XML' . "\n";

					$instanceIdValue = $instanceIdValueKMS;
				}

				//build new xml
				$newDoc->getElementsByTagName("Value")->item(0)->nodeValue = $instanceIdValue;
				$newDocFormatXML                                           = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $newDoc->saveXML())));

				//THIS IS MERGE-
				$newDoc = $this->mergeEntryAdditionalInfo($oldXmlFormatted, $newDocFormatXML, $keyFieldName, $valueFieldName, $detailFieldName);

				$newDocMergedXML = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $newDoc->saveXML())));

				$successMessage          = "Metadata- Entry Additional info- Key: " . $instanceIdKey . " Value: " . $instanceIdValue . " -updated for entry " . $entryId .
					" and object id: " . $targetMetadataObjectId . "\n" . "New xml is: " . $newDocMergedXML . "\n";
				$trialsExceededMsgUpdate = 'Exceeded number of trials for this metadata object & entry. Moving on to next entry' . "\n\n";

				try {
					$this->clientObject->doMetadataUpdate($targetMetadataObjectId, $newDocMergedXML, $successMessage, $trialsExceededMsgUpdate, 1);

				} catch(KalturaException $apiException) {
					echo 'Error occurred with update of entry ' . $entryId . ' and object id ' . $targetMetadataObjectId . "-\n" .
						$apiException->getMessage() . "\n" . 'Not checking for child entry-' . "\n\n";
					continue;
				}

				$childEntryFilter                     = new KalturaBaseEntryFilter();
				$childEntryFilter->parentEntryIdEqual = $entryId;
				$pager            = new KalturaFilterPager();

				$childEntryResponse                   = $this->clientObject->doBaseEntryList($childEntryFilter, $pager, "", $trialsExceededMsg, 1);

				if(isset($childEntryResponse->totalCount) && $childEntryResponse->totalCount == 1) {
					/* @var $childEntry KalturaMediaEntry */
					$childEntry = $childEntryResponse->objects[0];

					$childMetadataFilter                          = new KalturaMetadataFilter();
					$childMetadataFilter->objectIdEqual           = $childEntry->id;
					$childMetadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
					$childMetadataFilter->metadataProfileIdEqual  = $targetMetadataProfileId;

					$childMetadataListResponse = $this->clientObject->doMetadataList($childMetadataFilter, $trialsExceededMsg, 1);

					if($childMetadataListResponse->totalCount == 1) {
						echo 'Child ' . $childEntry->id . ' already has metadata on this profile. Trying to update-' . "\n";

						/* @var $childMetadataObject KalturaMetadata */
						$childMetadataObject = $childMetadataListResponse->objects[0];
						$childXml            = $childMetadataObject->xml;
						$childTargetObjectId = $childMetadataObject->id;

						if(strpos($childXml, "InstanceId") !== FALSE) {
							echo 'Child ' . $childEntry->id . ' already has Instance Id! Skipping' . "\n\n";
							continue;
						}
						$childXmlFormatted = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $childXml)));

						//THIS IS MERGE-
						$newDoc          = $this->mergeEntryAdditionalInfo($childXmlFormatted, $newDocFormatXML, $keyFieldName, $valueFieldName, $detailFieldName);
						$newDocMergedXML = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $newDoc->saveXML())));

						$successMessage          = "Metadata- Entry Additional info- Key: " . $instanceIdKey . " Value: " . $instanceIdValue . " -updated for child entry " . $childEntry->id .
							" and object id: " . $childTargetObjectId . "\n" . "New xml is: " . $newDocMergedXML . "\n";
						$trialsExceededMsgUpdate = 'Exceeded number of trials for this metadata object & entry. Moving on to next entry' . "\n\n";

						try {
							$this->clientObject->doMetadataUpdate($childTargetObjectId, $newDocMergedXML, $successMessage, $trialsExceededMsgUpdate, 1);

						} catch(KalturaException $apiException) {
							echo 'Error occurred with update of child entry ' . $childEntry->id . ' and object id ' . $childTargetObjectId . "-\n" .
								$apiException->getMessage() . "\n\n";
						}

					} else {
						echo 'Child ' . $childEntry->id . ' does not have metadata on this profile. Adding-' . "\n";

						$successMsg = 'Metadata add of ' . $newDocFormatXML . ' succeeded for child entry ' . $childEntry->id . ' and profile id ' . $targetMetadataProfileId . "\n\n";
						try {
							$this->clientObject->doMetadataAdd($targetMetadataProfileId, $childEntry->id, $newDocFormatXML, $successMsg, $trialsExceededMsg, 1);
						} catch(KalturaException $apiException) {
							echo 'Metadata add of child did not succeed-' . "\n" . $apiException->getMessage() . "\n\n";
							continue;
						}
					}


				} else {
					echo 'Entry ' . $entryId . ' does not have child entry' . "\n\n";
				}

			}
			else {
				//TODO: metadata doesn't exist on this profile, therefore Add
				echo "This entry " . $entryId . " doesn't have metadata on Additional Info-\n";

				$newDoc = new DOMDocument();
				$newDoc->loadXML($inputFormatXml);    //shablona


				if($isLMSUser == "y") {
					echo 'This entry ' . $entryId . ' has user- ' . $userId . ' -in LMS list:' . "\n";
					echo 'Adding ' . '<Detail>' . '<Key>' . $instanceIdKey . '</Key>' .
						'<Value>' . $instanceIdValueLMS . '</Value>' . '</Detail>' . ' to XML' . "\n";

					$instanceIdValue = $instanceIdValueLMS;

				} else {
					echo 'This entry ' . $entryId . ' has user- ' . $userId . ' -not in LMS list:' . "\n";
					echo 'Adding ' . '<Detail>' . '<Key>' . $instanceIdKey . '</Key>' .
						'<Value>' . $instanceIdValueKMS . '</Value>' . '</Detail>' . ' to XML' . "\n";

					$instanceIdValue = $instanceIdValueKMS;

				}

				//build xml
				$newDoc->getElementsByTagName("Value")->item(0)->nodeValue = $instanceIdValue;

				//format the xml - erase version, whitespaces
				$newDocXML = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $newDoc->saveXML())));

				$successMsg = 'Metadata add of ' . $newDocXML . ' succeeded for entry ' . $entryId . ' and profile id ' . $targetMetadataProfileId . "\n";
				try {
					$this->clientObject->doMetadataAdd($targetMetadataProfileId, $entryId, $newDocXML, $successMsg, $trialsExceededMsg, 1);
				} catch(KalturaException $apiException) {
					echo 'Metadata add of parent did not succeed-' . "\n" . $apiException->getMessage() . "\n" . 'Skipping child entry-' . "\n\n";
					continue;
				}

				$childEntryFilter                     = new KalturaBaseEntryFilter();
				$childEntryFilter->parentEntryIdEqual = $entryId;
				$pager            = new KalturaFilterPager();

				$childEntryResponse                   = $this->clientObject->doBaseEntryList($childEntryFilter, $pager, "", $trialsExceededMsg, 1);

				if(isset($childEntryResponse->totalCount) && $childEntryResponse->totalCount == 1) {
					/* @var $childEntry KalturaMediaEntry */
					$childEntry = $childEntryResponse->objects[0];

					$successMsg = 'Metadata add of ' . $newDocXML . ' succeeded for child entry ' . $childEntry->id . ' and profile id ' . $targetMetadataProfileId . "\n";
					try {
						$this->clientObject->doMetadataAdd($targetMetadataProfileId, $childEntry->id, $newDocXML, $successMsg, $trialsExceededMsg, 1);
					} catch(KalturaException $apiException) {
						if($apiException->getCode() == "METADATA_ALREADY_EXISTS") {
							echo 'Child ' . $childEntry->id . ' already has metadata on this Additional info- Trying to update-' . "\n";

							$childMetadataFilter                          = new KalturaMetadataFilter();
							$childMetadataFilter->objectIdEqual           = $childEntry->id;
							$childMetadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
							$childMetadataFilter->metadataProfileIdEqual  = $targetMetadataProfileId;

							$childMetadataListResponse = $this->clientObject->doMetadataList($childMetadataFilter, $trialsExceededMsg, 1);

							/* @var $childMetadataObject KalturaMetadata */
							$childMetadataObject = $childMetadataListResponse->objects[0];
							$childXml            = $childMetadataObject->xml;
							$childTargetObjectId = $childMetadataObject->id;

							if(strpos($childXml, "InstanceId") !== FALSE) {
								echo 'Child ' . $childEntry->id . ' already has Instance Id! Skipping' . "\n\n";
								continue;
							}
							$childXmlFormatted = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $childXml)));

							//THIS IS MERGE-
							$newDoc          = $this->mergeEntryAdditionalInfo($childXmlFormatted, $newDocXML, $keyFieldName, $valueFieldName, $detailFieldName);
							$newDocMergedXML = str_replace(" ", "", str_replace("\n", "", str_replace('<?xml version="1.0"?>', '', $newDoc->saveXML())));

							$successMessage          = "Metadata- Entry Additional info- Key: " . $instanceIdKey . " Value: " . $instanceIdValue . " -updated for child entry " . $childEntry->id .
								" and object id: " . $childTargetObjectId . "\n" . "New xml is: " . $newDocMergedXML . "\n";
							$trialsExceededMsgUpdate = 'Exceeded number of trials for this metadata object & entry. Moving on to next entry' . "\n\n";

							try {
								$this->clientObject->doMetadataUpdate($childTargetObjectId, $newDocMergedXML, $successMessage, $trialsExceededMsgUpdate, 1);

							} catch(KalturaException $apiException) {
								echo 'Error occurred with update of child entry ' . $childEntry->id . ' and object id ' . $childTargetObjectId . "-\n" .
									$apiException->getMessage() . "\n\n";
							}

						} else {
							echo 'Other error occurred with child entry-' . "\n" . $apiException->getMessage() . "\n\n";
						}
					}

				} else {
					echo 'Entry ' . $entryId . ' does not have child entry' . "\n\n";
				}

			}

		}
		$this->calculateProgressBar($currentCount, $progressBarIncrement, $numberOfProgressBars, $totalCount);
		fclose($entryIdCsvHandle);
	}


	private function mergeEntryAdditionalInfo($oldXmlFormatted, $newXmlFormatted, string $keyFieldName, string $valueFieldName, string $detailFieldName): DOMDocument {
		$existingCompanyXml = new DOMDocument();
		$existingCompanyXml->loadXML($oldXmlFormatted);
		$oldDomXpath = new DOMXPath($existingCompanyXml);

		$newDoc = new DOMDocument();
		$newDoc->loadXML($newXmlFormatted);
		$newDomXpath = new DOMXPath($newDoc);


		/* @var $valueItem DOMNode */
		$valueItems = $oldDomXpath->query("./$valueFieldName");
		if($valueItems->length) {
			//add value, then key
			$valueItem = $valueItems->item(0);
			$elem      = $newDoc->createElement($valueFieldName);
			$text      = $newDoc->createTextNode($valueItem->nodeValue);
			$elem->appendChild($text);

			$firstItems = $newDomXpath->query("./$detailFieldName");
			$firstItem  = $firstItems->item(0);
			$newDoc->documentElement->insertBefore($elem, $firstItem);

			/* @var $keyItem DOMNode */
			$keyItems = $oldDomXpath->query("./$keyFieldName");
			if($keyItems->length) {
				$keyItem = $keyItems->item(0);
				$elem    = $newDoc->createElement($keyFieldName);
				$text    = $newDoc->createTextNode($keyItem->nodeValue);
				$elem->appendChild($text);

				$firstItems = $newDomXpath->query("./$valueFieldName");
				$firstItem  = $firstItems->item(0);
				$newDoc->documentElement->insertBefore($elem, $firstItem);
			}
		}

		/* @var $node DOMNode */
		$detailNodes = $existingCompanyXml->getElementsByTagName($detailFieldName);
		foreach($detailNodes as $node) {
			//add detail nodes
			/* @var $keys DOMNodeList */
			$keys      = $node->getElementsByTagName($keyFieldName);
			$singleKey = $keys->item(0);

			/* @var $values DOMNodeList */
			$values      = $node->getElementsByTagName($valueFieldName);
			$singleValue = $values->item(0);

			$elemGadol = $newDoc->createElement($detailFieldName);

			$elem1 = $newDoc->createElement($keyFieldName);
			$text1 = $newDoc->createTextNode($singleKey->nodeValue);
			$elem1->appendChild($text1);

			$elem2 = $newDoc->createElement($valueFieldName);
			$text2 = $newDoc->createTextNode($singleValue->nodeValue);
			$elem2->appendChild($text2);

			$elemGadol->appendChild($elem1);
			$elemGadol->appendChild($elem2);

			$newDoc->documentElement->appendChild($elemGadol);
		}
		return $newDoc;
	}

}
