<?php
require_once __DIR__ . '/vendor/autoload.php';

use Kaltura\Client\Client as KalturaClient;
use Kaltura\Client\Configuration as KalturaConfiguration;
use Kaltura\Client\Enum\SessionType as KalturaSessionType;

use Kaltura\Client\ApiException as KalturaApiException;

use Kaltura\Client\Type\MediaEntry as KalturaMediaEntry;

use Kaltura\Client\Type\FilterPager as KalturaFilterPager;
use Kaltura\Client\Type\MediaEntryFilter as KalturaMediaFilter;

use Kaltura\Client\Enum\MediaType as KalturaMediaType;
use Kaltura\Client\Enum\EntryStatus as KalturaEntryStatus;
use Kaltura\Client\Enum\MediaEntryOrderBy as KalturaMediaEntryOrderBy;

use Kaltura\Client\Type\FlavorAsset as KalturaFlavorAsset;
use Kaltura\Client\Type\FlavorAssetFilter as KalturaFlavorAssetFilter;
use Kaltura\Client\Enum\FlavorAssetStatus as KalturaFlavorStatus;


define('SERVICE_URL', "http://www.kaltura.com");
define('ADMIN_SECRET', "");     //MMU Secret
define('USER_ID', "");
define('PARTNER_ID', 0);                                  //MMU
define('EXPIRY', 86400);
define('PRIVILEGES', '');

define('PAGE_SIZE', 500);


$config = new KalturaConfiguration();
$config->setServiceUrl(SERVICE_URL);
$config->setVerifySSL(FALSE);

$client = new KalturaClient($config);

$ks = $client->generateSessionV2(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID, EXPIRY, PRIVILEGES);

echo "Kaltura session (ks) was generated successfully: " . $ks . "\n\n";
$client->setKs($ks);


$mediaEntryFilter                                    = new KalturaMediaFilter();
$mediaEntryFilter->mediaTypeIn                       = KalturaMediaType::VIDEO . "," . KalturaMediaType::AUDIO;
$mediaEntryFilter->statusEqual                       = KalturaEntryStatus::READY;
$mediaEntryFilter->lastPlayedAtLessThanOrEqualOrNull = 1546300800;                  //01.01.2019
$mediaEntryFilter->createdAtLessThanOrEqual          = 1483228800;                  //01.01.2017
$mediaEntryFilter->createdAtGreaterThanOrEqual       = 1385734837;
$mediaEntryFilter->orderBy                           = KalturaMediaEntryOrderBy::CREATED_AT_ASC;

$pager            = new KalturaFilterPager();
$pager->pageSize  = PAGE_SIZE;
$pager->pageIndex = 1;                              //TODO: Always getting the first page

$flavorAssetFilter              = new KalturaFlavorAssetFilter();
$flavorAssetFilter->statusEqual = KalturaFlavorStatus::READY;


$wetRun       = fopen('mmu_wet_run.csv', 'w');
$wetRunDetail = fopen('mmu_wet_run_detail.csv', 'w');

$infoArray  = array("These", "are", "the", "entries");
$info2Array = array("with", "flavors", "that", "were", "deleted");
$dashArray  = array("=====", "=====", "=====", "=====", "=====", "=====");

fputcsv($wetRun, $infoArray);
fputcsv($wetRun, $info2Array);
fputcsv($wetRun, $dashArray);

fputcsv($wetRunDetail, $infoArray);
fputcsv($wetRunDetail, $info2Array);
fputcsv($wetRunDetail, $dashArray);


//TODO: flavor params key to value array
//so that we have in advance the mapping of id to name

//"advance knowledge" from customer KMC -> Settings -> Transcoding -> Default profile
$flavorParamsKeyToValueArray = array();

$flavorParamsKeyToValueArray[1] = "HD";
$flavorParamsKeyToValueArray[2] = "High - Large";
$flavorParamsKeyToValueArray[3] = "Standard - Large";
$flavorParamsKeyToValueArray[4] = "Standard - Small";
$flavorParamsKeyToValueArray[5] = "Basic - Small";
$flavorParamsKeyToValueArray[6] = "HQ MP4 for Export";
$flavorParamsKeyToValueArray[7] = "Editable";

$flavorParamsKeyToValueArray[301951] = "Mobile (H264) - Basic";
$flavorParamsKeyToValueArray[301961] = "Mobile (H264) - Standard";
$flavorParamsKeyToValueArray[301971] = "iPad";
$flavorParamsKeyToValueArray[301991] = "Mobile (3GP)";

$flavorParamsKeyToValueArray[487041] = "Basic/Small - WEB/MBL (H264/400)";
$flavorParamsKeyToValueArray[487051] = "Basic/Small - WEB/MBL (H264/600)";
$flavorParamsKeyToValueArray[487061] = "SD/Small - WEB/MBL (H264/900)";
$flavorParamsKeyToValueArray[487071] = "SD/Large - WEB/MBL (H264/1500)";
$flavorParamsKeyToValueArray[487081] = "HD/720 - WEB (H264/2500)";
$flavorParamsKeyToValueArray[487091] = "HD/1080 - WEB (H264/4000)";
$flavorParamsKeyToValueArray[487111] = "WebM";

$flavorParamsKeyToValueArray[2609422] = "Audio Description - Eng";
$flavorParamsKeyToValueArray[2916682] = "English";

$flavorParamsKeyToValueArray[512411] = "SWF for KMS4";
$flavorParamsKeyToValueArray[528891] = "SWF for KMS4.5";

//======================================================

$i              = 0;
$totalSavedSize = 0;

//======================================================

while(TRUE) {

	// media . list
	try {
		$mediaList = $client->getMediaService()->listAction($mediaEntryFilter, $pager);
	} catch(KalturaApiException $apiException) {
		echo "This is media . list API exception! \n";
		echo $apiException->getMessage();
		die;
	}


	if(count($mediaList->objects) == 0) {
		echo "Stopped running - end of results! \n";
		break;
	}

	$i++;

	$columnArray = array("EntryID", "Type", "Name", "FlavorAssetIDs", "FlavorsSize", "LastPlayedAt", "CreatedAt");
	fputcsv($wetRun, $columnArray);
	$column2Array = array("UserID", "EntryID", "Type", "Name", "FlavorNames");
	fputcsv($wetRunDetail, $column2Array);

	$pageSavedSize = 0;

	$j = 0;
	/* @var $mediaEntry KalturaMediaEntry */
	foreach($mediaList->objects as $mediaEntry) {
		$flavor_params_array = explode(",", $mediaEntry->flavorParamsIds);
		//more than 2 flavors
		if(count($flavor_params_array) > 2) {

			$j++;

			//current entry
			$flavorAssetFilter->entryIdEqual = $mediaEntry->id;

			//flavorAsset . list
			try {
				$flavorAssetList = $client->getFlavorAssetService()->listAction($flavorAssetFilter);
			} catch(KalturaApiException $apiException) {
				echo "This is flavorAsset . list API exception! \n";
				echo $apiException->getMessage();
				die;
			}

			$flavorAssetArrayWithoutSrc = array();
			$flavorAssetSizeArray       = array();
			/* @var $flavorAsset KalturaFlavorAsset */
			foreach($flavorAssetList->objects as $flavorAsset) {
				if((int)($flavorAsset->flavorParamsId) != 0) {
					//key-value
					$flavorAssetArrayWithoutSrc[$flavorAsset->id] = $flavorAsset;
				}
				$flavorAssetSizeArray[$flavorAsset->id] = $flavorAsset->size;
			}

			$maxBitRate = 0;
			$flavorId   = "";
			foreach($flavorAssetArrayWithoutSrc as $flavorObject) {
				$flavorBitRate = (int)($flavorObject->bitrate);
				if($flavorBitRate > $maxBitRate) {
					$maxBitRate = $flavorBitRate;
					//save flavor id
					$flavorId = $flavorObject->id;
				}
			}

			//$flavorId here points to the max

			//remove max by flavorId
			unset($flavorAssetArrayWithoutSrc[$flavorId]);

			$flavorAssetArrayWithoutSrcAndHighest = $flavorAssetArrayWithoutSrc;

			//get only the IDs
			$flavorAssetIds  = array_map(function ($object) {
				return $object->id;
			},
				$flavorAssetArrayWithoutSrcAndHighest);
			$flavorsToDelete = implode(" ", $flavorAssetIds);

			//get params ids for later
			$flavorParamsIds = array_map(function ($object) {
				return $object->flavorParamsId;
			},
				$flavorAssetArrayWithoutSrcAndHighest);


			//now we have the flavors - Delete them:

			$flavorsSize = 0;
			foreach($flavorAssetIds as $flavorAssetToDelete) {
				// flavorAsset . delete
				try {
					$client->getFlavorAssetService()->delete($flavorAssetToDelete);
				} catch(KalturaApiException $apiException) {
					echo "This is flavorAsset . delete API exception! \n";
					echo $apiException->getMessage();
				}

				$flavorsSize += $flavorAssetSizeArray[$flavorAssetToDelete];
			}

			$pageSavedSize  += $flavorsSize;
			$totalSavedSize += $flavorsSize;

			//print what was deleted

			$mediaInfo = array($mediaEntry->id, $mediaEntry->mediaType, $mediaEntry->name, $flavorsToDelete, $flavorsSize,
				$mediaEntry->lastPlayedAt, $mediaEntry->createdAt);
			fputcsv($wetRun, $mediaInfo);

			$flavorNamesToDelete = "";
			foreach($flavorParamsIds as $flavorIdToDelete) {
				$flavorNamesToDelete .= $flavorParamsKeyToValueArray[$flavorIdToDelete] . ", ";
			}
			$flavorNamesTrim = rtrim($flavorNamesToDelete, ", ");

			$mediaInfoDetail = array($mediaEntry->userId, $mediaEntry->id, $mediaEntry->mediaType, $mediaEntry->name, $flavorNamesTrim);
			fputcsv($wetRunDetail, $mediaInfoDetail);
		}
	}

	fputcsv($wetRun, $dashArray);
	fputcsv($wetRunDetail, $dashArray);

	echo "Size per page " . $i . ": " . $pageSavedSize . ". Total saved size: " . $totalSavedSize . "\n";

	$mediaEntryFilter->createdAtGreaterThanOrEqual = $mediaEntry->createdAt + 1;

}

echo "i is " . $i . "\n";
echo "Total saved size: " . $totalSavedSize . "\n";

fclose($wetRun);

fclose($wetRunDetail);
